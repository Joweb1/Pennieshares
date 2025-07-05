<?php
// --- Constants for Fund Distribution ---
define('GENERATIONAL_POT_ALLOCATION', 10.00);
define('PAYOUT_PER_GENERATION_EVENT_FROM_POT', 2.00); // 10 / 5 generations
define('SHARED_POT_ALLOCATION', 5.00);
define('COMPANY_PROFIT_ALLOCATION', 3.00);
define('FIXED_ALLOCATIONS_TOTAL', GENERATIONAL_POT_ALLOCATION + SHARED_POT_ALLOCATION + COMPANY_PROFIT_ALLOCATION); // 10 + 5 + 3 = 18

define('CHILDREN_PER_ASSET', 3);
define('MAX_GENERATIONS_PAYOUT_DEPTH', 5);


// --- Helper Functions ---
function getAssetTypes($pdo) {
    return $pdo->query("SELECT * FROM asset_types ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
}

function getCompanyFunds($pdo) {
    return $pdo->query("SELECT * FROM company_funds WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
}

// --- New Function to Check and Mark Expired Assets ---
function checkAndMarkExpiredAssets($pdo) {
    $now = date('Y-m-d H:i:s');
    // Select assets that have an expiry date, are not already completed, not already marked manually expired, and whose expiry date is in the past
    $stmt = $pdo->prepare("UPDATE assets 
                          SET is_manually_expired = 1 
                          WHERE expires_at IS NOT NULL 
                          AND expires_at < :now 
                          AND is_completed = 0
                          AND is_manually_expired = 0");
    $stmt->execute([':now' => $now]);
    return $stmt->rowCount(); // Returns the number of assets newly marked as expired
}


// --- Core Logic Functions ---
function findEligibleParent($pdo) {
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT a.id, a.generation FROM assets a
                          WHERE a.is_completed = 0 
                          AND a.is_manually_expired = 0 -- Added this check
                          AND (a.expires_at IS NULL OR a.expires_at > :now)
                          AND a.children_count < :children_per_asset
                          ORDER BY datetime(a.created_at) ASC, a.id ASC 
                          LIMIT 1");
    $stmt->bindValue(':now', $now, PDO::PARAM_STR);
    $stmt->bindValue(':children_per_asset', CHILDREN_PER_ASSET, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAncestorsForPayout($pdo, $childAssetId, $maxDepth = MAX_GENERATIONS_PAYOUT_DEPTH) {
    $ancestors = []; 
    $currentAssetId = $childAssetId;
    $depth = 0;
    $now = date('Y-m-d H:i:s'); // For checking if ancestor is currently expired

    while ($depth < $maxDepth) {
        $stmt = $pdo->prepare("SELECT parent_id FROM assets WHERE id = ?");
        $stmt->execute([$currentAssetId]);
        $parentId = $stmt->fetchColumn();

        if ($parentId) {
            $ancestorDetailsStmt = $pdo->prepare("
                SELECT a.id, a.asset_type_id, at.payout_cap, a.is_completed, a.is_manually_expired, a.expires_at, a.total_generational_received
                FROM assets a
                JOIN asset_types at ON a.asset_type_id = at.id
                WHERE a.id = ?
            ");
            $ancestorDetailsStmt->execute([$parentId]);
            $ancestorDetail = $ancestorDetailsStmt->fetch(PDO::FETCH_ASSOC);
            if ($ancestorDetail) {
                // Determine if effectively expired now, even if not yet marked by checkAndMarkExpiredAssets
                $ancestorDetail['is_currently_expired'] = ($ancestorDetail['expires_at'] && $ancestorDetail['expires_at'] < $now) || $ancestorDetail['is_manually_expired'] == 1;
                $ancestors[] = $ancestorDetail;
            }
            $currentAssetId = $parentId;
            $depth++;
        } else {
            break; 
        }
    }
    return $ancestors; 
}


// --- buyAsset Function (Updated with expiration check and logging) ---
function buyAsset($pdo, $userId, $assetTypeId, $numAssetsToBuy = 1) {
    global $pdo;
    $overallResults = ['purchases' => [], 'summary' => [], 'expired_check_count' => 0];
    $now = date('Y-m-d H:i:s');

    // Perform expiration check before any purchases
    $expiredCount = checkAndMarkExpiredAssets($pdo);
    $overallResults['expired_check_count'] = $expiredCount;

    $assetTypeStmt = $pdo->prepare("SELECT * FROM asset_types WHERE id = ?");
    $assetTypeStmt->execute([$assetTypeId]);
    $assetType = $assetTypeStmt->fetch(PDO::FETCH_ASSOC);

    if (!$assetType) {
        throw new Exception("Invalid Asset Type ID {$assetTypeId}.");
    }

    $totalCost = $numAssetsToBuy * $assetType['price'];

    // Check user wallet balance (already debited by calling function)

    for ($count = 0; $count < $numAssetsToBuy; $count++) {
        $currentPurchaseResult = [
            'asset_id' => null, 'message' => '', 'parent_update' => '',
            'company_profit_log' => '', 'reservation_direct_log' => '',
            'generational_payouts_log' => [], 'shared_payouts_log' => []
        ];

        $expires_at = null;
        if ($assetType['duration_months'] > 0) {
            $dt = new DateTime($now);
            $dt->add(new DateInterval("P{$assetType['duration_months']}M"));
            $expires_at = $dt->format('Y-m-d H:i:s');
        }

        $eligibleParentInfo = findEligibleParent($pdo);
        $parentId = null;
        $newAssetGeneration = 1;
        if ($eligibleParentInfo) {
            $parentId = $eligibleParentInfo['id'];
            $newAssetGeneration = $eligibleParentInfo['generation'] + 1;
        }
        $stmt = $pdo->prepare("INSERT INTO assets (user_id, asset_type_id, parent_id, generation, created_at, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $assetTypeId, $parentId, $newAssetGeneration, $now, $expires_at]);
        $newAssetId = $pdo->lastInsertId();
        $currentPurchaseResult['asset_id'] = $newAssetId;
        $currentPurchaseResult['message'] = "Asset #{$newAssetId} ({$assetType['name']}) created for user #{$userId}. Expires: ".($expires_at ?? 'Never').".";

        if ($parentId) {
            $pdo->prepare("UPDATE assets SET children_count = children_count + 1 WHERE id = ?")->execute([$parentId]);
            $currentPurchaseResult['parent_update'] = "Parent Asset #{$parentId} children_count incremented.";
        }
        
        // Payout logic
        $assetPrice = $assetType['price'];
        $remainingAmount = $assetPrice;

        // 1. Company Profit Allocation
        $companyProfitAmount = $assetPrice * (COMPANY_PROFIT_ALLOCATION / 100);
        $pdo->prepare("UPDATE company_funds SET total_company_profit = total_company_profit + ? WHERE id = 1")->execute([$companyProfitAmount]);
        $remainingAmount -= $companyProfitAmount;
        $currentPurchaseResult['company_profit_log'] = "Company profit increased by ₦" . number_format($companyProfitAmount, 2) . ".";

        // 2. Generational Pot Allocation
        $generationalPotAmount = $assetPrice * (GENERATIONAL_POT_ALLOCATION / 100);
        $pdo->prepare("UPDATE company_funds SET total_generational_pot = total_generational_pot + ? WHERE id = 1")->execute([$generationalPotAmount]);
        $remainingAmount -= $generationalPotAmount;
        $currentPurchaseResult['generational_pot_log'] = "Generational pot increased by ₦" . number_format($generationalPotAmount, 2) . ".";

        // 3. Shared Pot Allocation
        $sharedPotAmount = $assetPrice * (SHARED_POT_ALLOCATION / 100);
        $pdo->prepare("UPDATE company_funds SET total_shared_pot = total_shared_pot + ? WHERE id = 1")->execute([$sharedPotAmount]);
        $remainingAmount -= $sharedPotAmount;
        $currentPurchaseResult['shared_pot_log'] = "Shared pot increased by ₦" . number_format($sharedPotAmount, 2) . ".";

        // 4. Reservation Fund Allocation (remaining amount after fixed allocations)
        $reservationFundAmount = $remainingAmount; // This should be the remainder after fixed allocations
        $pdo->prepare("UPDATE company_funds SET total_reservation_fund = total_reservation_fund + ? WHERE id = 1")->execute([$reservationFundAmount]);
        $currentPurchaseResult['reservation_fund_log'] = "Reservation fund increased by ₦" . number_format($reservationFundAmount, 2) . ".";

        // Generational Payouts
        $ancestors = getAncestorsForPayout($pdo, $newAssetId);
        foreach ($ancestors as $depth => $ancestor) {
            if ($depth >= MAX_GENERATIONS_PAYOUT_DEPTH) break;

            // Check if ancestor is active and not completed/expired
            if ($ancestor['is_completed'] == 0 && $ancestor['is_currently_expired'] == 0) {
                $payoutAmount = PAYOUT_PER_GENERATION_EVENT_FROM_POT;

                // Check payout cap
                $potentialTotalEarned = $ancestor['total_generational_received'] + $payoutAmount;
                if ($ancestor['payout_cap'] > 0 && $potentialTotalEarned > $ancestor['payout_cap']) {
                    $payoutAmount = $ancestor['payout_cap'] - $ancestor['total_generational_received'];
                    if ($payoutAmount < 0) $payoutAmount = 0; // Ensure payout is not negative
                }

                if ($payoutAmount > 0) {
                    // Deduct from generational pot
                    $pdo->prepare("UPDATE company_funds SET total_generational_pot = total_generational_pot - ? WHERE id = 1")->execute([$payoutAmount]);
                    // Credit ancestor asset
                    $pdo->prepare("UPDATE assets SET total_generational_received = total_generational_received + ? WHERE id = ?")->execute([$payoutAmount, $ancestor['id']]);
                    // Credit user wallet
                    $ancestorUserIdStmt = $pdo->prepare("SELECT user_id FROM assets WHERE id = ?");
                    $ancestorUserIdStmt->execute([$ancestor['id']]);
                    $ancestorUserId = $ancestorUserIdStmt->fetchColumn();
                    if ($ancestorUserId) {
                            $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")->execute([$payoutAmount, $ancestorUserId]);
                            // Log generational payout as asset_profit
                            $logStmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)");
                            $assetName = getAssetBranding($ancestor['asset_type_id'])['name'];
                            $logStmt->execute([$ancestorUserId, 'asset_profit', $payoutAmount, 'Profit from ' . $assetName]);
                        }

                    // Log payout
                    $pdo->prepare("INSERT INTO payouts (receiving_asset_id, triggering_asset_id, amount, payout_type, created_at) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$ancestor['id'], $newAssetId, $payoutAmount, 'generational', $now]);
                    $currentPurchaseResult['generational_payouts_log'][] = "Asset #{$ancestor['id']} received ₦" . number_format($payoutAmount, 2) . " (Gen. " . ($depth + 1) . ").";

                    // Mark asset as completed if cap reached
                    $updatedTotalGenerationalReceivedStmt = $pdo->prepare("SELECT total_generational_received FROM assets WHERE id = ?");
                    $updatedTotalGenerationalReceivedStmt->execute([$ancestor['id']]);
                    $updatedTotalGenerationalReceived = $updatedTotalGenerationalReceivedStmt->fetchColumn();

                    if ($ancestor['payout_cap'] > 0 && $updatedTotalGenerationalReceived >= $ancestor['payout_cap']) {
                        $pdo->prepare("UPDATE assets SET is_completed = 1 WHERE id = ?")->execute([$ancestor['id']]);
                        $currentPurchaseResult['generational_payouts_log'][] = "Asset #{$ancestor['id']} marked completed (cap reached).";
                    }
                }
            }
        }

        // Shared Payouts: Distribute SHARED_POT_ALLOCATION among all active assets
        $activeAssetsStmt = $pdo->prepare("SELECT a.id, a.user_id, at.name as asset_type_name FROM assets a JOIN asset_types at ON a.asset_type_id = at.id WHERE a.is_completed = 0 AND a.is_manually_expired = 0");
        $activeAssetsStmt->execute();
        $activeAssets = $activeAssetsStmt->fetchAll(PDO::FETCH_ASSOC);

        $numActiveAssets = count($activeAssets);
        if ($numActiveAssets > 0) {
            $fractionalSharedPayout = SHARED_POT_ALLOCATION / $numActiveAssets;
            foreach ($activeAssets as $activeAsset) {
                // Credit asset's total_shared_received
                $pdo->prepare("UPDATE assets SET total_shared_received = total_shared_received + ? WHERE id = ?")->execute([$fractionalSharedPayout, $activeAsset['id']]);
                
                // Credit user wallet
                $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")->execute([$fractionalSharedPayout, $activeAsset['user_id']]);

                // Log shared payout as asset_profit
                $logStmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)");
                $logStmt->execute([$activeAsset['user_id'], 'asset_profit', $fractionalSharedPayout, 'Profit from ' . $activeAsset['asset_type_name']]);

                $currentPurchaseResult['shared_payouts_log'][] = "Asset #{$activeAsset['id']} received ₦" . number_format($fractionalSharedPayout, 2) . " (Shared).";
            }
        } else {
            $currentPurchaseResult['shared_payouts_log'][] = "No active assets to distribute shared payout.";
        }

        $overallResults['purchases'][] = $currentPurchaseResult;
    }

    $overallResults['summary'][] = "Successfully purchased {$numAssetsToBuy} of '{$assetType['name']}'. Total Cost: SV" . number_format($totalCost, 2) . ".";
    
    return $overallResults;
}


// --- Functions for User Dashboard ---
function getUserAssets($pdo, $userId) {
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("
        SELECT a.*, at.name as asset_type_name, at.payout_cap as type_payout_cap,
               (a.total_generational_received + a.total_shared_received) as total_earned,
               CASE 
                   WHEN a.is_completed = 1 THEN 'Completed'
                   WHEN a.is_manually_expired = 1 THEN 'Expired'
                   WHEN (a.expires_at IS NOT NULL AND a.expires_at < :now) THEN 'Expired'
                   ELSE 'Active'
               END as current_status
        FROM assets a
        JOIN asset_types at ON a.asset_type_id = at.id
        WHERE a.user_id = :user_id
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([':user_id' => $userId, ':now' => $now]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserPayouts($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT p.*, ta.id as triggering_asset_display_id, tu.name as triggering_username
        FROM payouts p
        JOIN assets ra ON p.receiving_asset_id = ra.id -- Payouts TO the user's assets
        JOIN assets ta ON p.triggering_asset_id = ta.id
        JOIN users tu ON ta.user_id = tu.id
        WHERE ra.user_id = :user_id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- Functions for Chart Data ---
function getOverallIncomeStats($pdo) {
    $companyFunds = getCompanyFunds($pdo);
    $payoutsTotals = $pdo->query("
        SELECT 
            SUM(CASE WHEN payout_type = 'generational' THEN amount ELSE 0 END) as total_generational,
            SUM(CASE WHEN payout_type = 'shared' THEN amount ELSE 0 END) as total_shared
        FROM payouts
    ")->fetch(PDO::FETCH_ASSOC);

    return [
        'company_profit' => $companyFunds['total_company_profit'],
        'reservation_fund' => $companyFunds['total_reservation_fund'],
        'total_generational_paid' => $payoutsTotals['total_generational'] ?? 0,
        'total_shared_paid' => $payoutsTotals['total_shared'] ?? 0
    ];
}

function getAssetStatusDistribution($pdo) {
    $now = date('Y-m-d H:i:s');
    // This query is a bit complex to correctly categorize expired vs active
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN is_completed = 0 AND (is_manually_expired = 1 OR (expires_at IS NOT NULL AND expires_at < :now)) THEN 1 ELSE 0 END) as expired_count,
            SUM(CASE WHEN is_completed = 0 AND is_manually_expired = 0 AND (expires_at IS NULL OR expires_at >= :now) THEN 1 ELSE 0 END) as active_count
        FROM assets
    ");
    $stmt->execute([':now' => $now]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAssetBranding($assetTypeId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT name, image_link, category FROM asset_types WHERE id = ?");
    $stmt->execute([$assetTypeId]);
    $assetData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($assetData) {
        return ['name' => $assetData['name'], 'image' => $assetData['image_link'] ?? '', 'category' => $assetData['category'] ?? 'General'];
    } else {
        return ['name' => 'Unknown Asset', 'image' => '', 'category' => '' ];
    }
}

function addAssetType($pdo, $name, $price, $payoutCap, $durationMonths, $imageLink = null, $category = null) {
    try {
        $reservationContribution = $price - FIXED_ALLOCATIONS_TOTAL;
        $stmt = $pdo->prepare("INSERT INTO asset_types (name, price, payout_cap, duration_months, reservation_fund_contribution, image_link, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $price, $payoutCap, $durationMonths, $reservationContribution, $imageLink, $category]);
        return true;
    } catch (PDOException $e) {
        error_log("Error adding asset type: " . $e->getMessage());
        return false;
    }
}

function markAssetExpired($pdo, $assetId) {
    try {
        $stmt = $pdo->prepare("UPDATE assets SET is_manually_expired = 1 WHERE id = ?");
        $stmt->execute([$assetId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error marking asset as expired: " . $e->getMessage());
        return false;
    }
}

function markAssetCompleted($pdo, $assetId) {
    try {
        $stmt = $pdo->prepare("UPDATE assets SET is_completed = 1, completed_at = datetime('now') WHERE id = ?");
        $stmt->execute([$assetId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error marking asset as completed: " . $e->getMessage());
        return false;
    }
}

function deleteAssetType($pdo, $assetTypeId) {
    try {
        // First, set any assets linked to this type to NULL or delete them
        // Depending on desired behavior, you might want to delete associated assets
        // For now, let's assume setting asset_type_id to NULL for associated assets
        $pdo->prepare("UPDATE assets SET asset_type_id = NULL WHERE asset_type_id = ?")->execute([$assetTypeId]);

        $stmt = $pdo->prepare("DELETE FROM asset_types WHERE id = ?");
        $stmt->execute([$assetTypeId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error deleting asset type: " . $e->getMessage());
        return false;
    }
}

?>
