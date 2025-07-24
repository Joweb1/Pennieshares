<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/kyc_functions.php';

if (!$user['is_admin']) {
    header('Location: /dashboard');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kycId = filter_input(INPUT_POST, 'kyc_id', FILTER_VALIDATE_INT);
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if (isset($_POST['approve'])) {
        if ($kycId && $userId) {
            if (updateKycStatus($pdo, $kycId, 'verified')) {
                // Optionally update user status in `users` table if not already handled by updateKycStatus
                // $pdo->prepare("UPDATE users SET status = 2 WHERE id = ?")->execute([$userId]);
                $message = "KYC submission #{$kycId} approved successfully!";
            } else {
                $message = "Error approving KYC submission #{$kycId}.";
            }
        } else {
            $message = "Error: Invalid KYC ID or User ID for approval.";
        }
    } elseif (isset($_POST['reject'])) {
        if ($kycId && $userId) {
            if (updateKycStatus($pdo, $kycId, 'rejected')) {
                $message = "KYC submission #{$kycId} rejected successfully!";
            } else {
                $message = "Error rejecting KYC submission #{$kycId}.";
            }
        } else {
            $message = "Error: Invalid KYC ID or User ID for rejection.";
        }
    }
    header('Location: /admin_kyc');
    exit;
}

$submissions = getKycSubmissions($pdo);

require_once __DIR__ . '/../assets/template/intro-template.php';
?>

<style>
    .verification-container {
        max-width: 1200px; /* Increased width to accommodate more info */
        margin: 2rem auto;
        padding: 1.5rem;
        background-color: var(--bg-secondary);
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        animation: fadeIn 0.5s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    h1 {
        color: var(--text-primary);
        text-align: center;
        margin-bottom: 1.5rem;
        font-size: 2.2rem;
        font-weight: 700;
        background: linear-gradient(45deg, var(--accent-color), #60efff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .message {
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        text-align: center;
        animation: slideIn 0.4s ease-out;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .message.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .kyc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Adjusted for more content */
        gap: 1.5rem;
    }

    .kyc-card {
        background-color: var(--bg-tertiary);
        border-radius: 10px;
        padding: 1.2rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        display: flex;
        flex-direction: column;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .kyc-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    .user-info-section, .document-section {
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }

    .document-section {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: center;
        align-items: flex-start;
    }

    .user-info-section h3, .document-section h3 {
        font-size: 1.1rem;
        color: var(--text-primary);
        margin-bottom: 0.8rem;
    }

    .user-detail-item {
        display: flex;
        justify-content: space-between;
        padding: 0.3rem 0;
        border-bottom: 1px dashed var(--border-color);
    }

    .user-detail-item:last-child {
        border-bottom: none;
    }

    .user-detail-label {
        font-weight: 600;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .user-detail-value {
        color: var(--text-primary);
        font-size: 0.9rem;
        text-align: right;
    }

    .document-preview-item {
        flex: 1 1 calc(50% - 1rem); /* Two items per row */
        max-width: calc(50% - 1rem);
        background-color: var(--bg-secondary);
        border-radius: 8px;
        padding: 0.8rem;
        text-align: center;
        border: 1px solid var(--border-color);
    }

    .document-preview-item img, .document-preview-item .pdf-icon-preview {
        max-width: 100%;
        height: 150px; /* Fixed height for consistency */
        object-fit: contain; /* Ensures entire image is visible */
        border-radius: 4px;
        margin-bottom: 0.5rem;
        border: 1px solid var(--border-color);
    }

    .document-preview-item .pdf-icon-preview {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: var(--accent-color);
        background-color: var(--bg-tertiary);
    }

    .document-preview-item p {
        font-size: 0.8rem;
        color: var(--text-secondary);
        word-break: break-all;
    }

    .kyc-status-badge {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-top: 0.5rem;
    }

    .status-pending { background-color: #fff3cd; color: #856404; }
    .status-verified { background-color: #d4edda; color: #155724; }
    .status-rejected { background-color: #f8d7da; color: #721c24; }

    .card-actions {
        margin-top: 1rem;
        display: flex;
        gap: 0.8rem;
        justify-content: center;
    }

    .action-btn {
        padding: 0.7rem 1.2rem;
        border: none;
        border-radius: 25px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .approve-btn {
        background-color: #28a745; /* Green */
        color: white;
    }

    .approve-btn:hover {
        background-color: #218838;
        transform: translateY(-2px);
    }

    .reject-btn {
        background-color: #dc3545; /* Red */
        color: white;
    }

    .reject-btn:hover {
        background-color: #c82333;
        transform: translateY(-2px);
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--text-secondary);
        font-size: 1.1rem;
        background-color: var(--bg-tertiary);
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .empty-state i {
        font-size: 3rem;
        color: var(--accent-color);
        margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
        .kyc-grid {
            grid-template-columns: 1fr;
        }
        .document-preview-item {
            flex: 1 1 100%;
            max-width: 100%;
        }
    }
</style>

<div class="verification-container">
    <h1>KYC Submissions</h1>

    <?php if ($message): ?>
        <div class="message <?php echo (strpos($message, 'Error') !== false) ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($submissions)): ?>
        <div class="empty-state">
            <i class="material-icons-outlined">check_circle_outline</i>
            <p>No KYC submissions pending review at the moment. All clear!</p>
        </div>
    <?php else: ?>
        <div class="kyc-grid">
            <?php foreach ($submissions as $submission): ?>
                <div class="kyc-card">
                    <div class="user-info-section">
                        <h3>User Information</h3>
                        <div class="user-detail-item"><span class="user-detail-label">Username:</span><span class="user-detail-value"><?= htmlspecialchars($submission['username']) ?></span></div>
                        <div class="user-detail-item"><span class="user-detail-label">Full Name:</span><span class="user-detail-value"><?= htmlspecialchars($submission['full_name'] ?? '') ?></span></div>
                        <div class="user-detail-item"><span class="user-detail-label">Email:</span><span class="user-detail-value"><?= htmlspecialchars($submission['email'] ?? '') ?></span></div>
                        <div class="user-detail-item"><span class="user-detail-label">Phone:</span><span class="user-detail-value"><?= htmlspecialchars($submission['phone'] ?? '') ?></span></div>
                        <div class="user-detail-item"><span class="user-detail-label">Date of Birth:</span><span class="user-detail-value"><?= htmlspecialchars($submission['dob'] ?? '') ?></span></div>
                        <div class="user-detail-item"><span class="user-detail-label">Address:</span><span class="user-detail-value"><?= htmlspecialchars($submission['address'] ?? '') ?></span></div>
                        <div class="user-detail-item"><span class="user-detail-label">State:</span><span class="user-detail-value"><?= htmlspecialchars($submission['state'] ?? '') ?></span></div>
                        <div class="user-detail-item"><span class="user-detail-label">BVN:</span><span class="user-detail-value"><?= htmlspecialchars($submission['bvn'] ?? '') ?></span></div>
                        <div class="user-detail-item"><span class="user-detail-label">NIN:</span><span class="user-detail-value"><?= htmlspecialchars($submission['nin'] ?? '') ?></span></div>
                        <div class="user-detail-item"><span class="user-detail-label">Submission Status:</span><span class="kyc-status-badge status-<?= htmlspecialchars($submission['status'] ?? '') ?>"><?= htmlspecialchars(ucfirst($submission['status'] ?? '')) ?></span></div>
                    </div>

                    <div class="document-section">
                        <h3>Uploaded Documents</h3>
                        <?php
                            $docs = [
                                'Identity Document' => $submission['passport_path'], // Now holds either passport or national ID
                                'Proof of Address' => $submission['proof_of_address_path'],
                                'Selfie' => $submission['selfie_path']
                            ];
                            foreach ($docs as $label => $path):
                                if ($path):
                                    $isPdf = (strtolower(pathinfo($path, PATHINFO_EXTENSION)) == 'pdf');
                        ?>
                                    <div class="document-preview-item">
                                        <?php if ($isPdf): ?>
                                            <div class="pdf-icon-preview"><span class="material-icons-outlined">picture_as_pdf</span></div>
                                            <p><?= htmlspecialchars($label) ?></p>
                                            <a href="/<?= htmlspecialchars($path) ?>" target="_blank">View PDF</a>
                                        <?php else: ?>
                                            <img src="/<?= htmlspecialchars($path) ?>" alt="<?= htmlspecialchars($label) ?>">
                                            <p><?= htmlspecialchars($label) ?></p>
                                        <?php endif; ?>
                                    </div>
                        <?php
                                endif;
                            endforeach;
                        ?>
                    </div>

                    <div class="card-actions">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="kyc_id" value="<?= $submission['id'] ?>">
                            <input type="hidden" name="user_id" value="<?= $submission['user_id'] ?>">
                            <button type="submit" name="approve" class="action-btn approve-btn">
                                Approve KYC
                            </button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to reject this KYC submission?');">
                            <input type="hidden" name="kyc_id" value="<?= $submission['id'] ?>">
                            <input type="hidden" name="user_id" value="<?= $submission['user_id'] ?>">
                            <button type="submit" name="reject" class="action-btn reject-btn">
                                Reject KYC
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../assets/template/end-template.php'; ?>