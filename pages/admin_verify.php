<?php
require_once __DIR__ . '/../src/functions.php';

// Admin Access Check
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_admin'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access Denied: You do not have administrative privileges.");
}

$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'verify') {
        $proofId = filter_input(INPUT_POST, 'proof_id', FILTER_VALIDATE_INT);
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

        if ($proofId && $userId) {
            try {
                // Update user status
                $stmt = $pdo->prepare("UPDATE users SET status = 2 WHERE id = :user_id");
                $stmt->execute([':user_id' => $userId]);

                // Update payment proof status
                $stmt = $pdo->prepare("UPDATE payment_proofs SET status = 2 WHERE id = :proof_id");
                $stmt->execute([':proof_id' => $proofId]);

                $message = "User verified and proof marked as verified successfully!";

                // Send email to admin about payment proof upload
                $user = getUserByIdOrName($pdo, $userId);
                $admin_data = [
                    'username' => $user['username']
                ];
                sendNotificationEmail('payment_proof_admin', $admin_data, 'nahjonah00@gmail.com', 'New Payment Proof Uploaded');
            } catch (PDOException $e) {
                $message = "Error verifying: " . $e->getMessage();
            }
        } else {
            $message = "Error: Invalid proof ID or user ID for verification.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_proof') {
        $proofId = filter_input(INPUT_POST, 'proof_id', FILTER_VALIDATE_INT);

        if ($proofId) {
            if (deletePaymentProof($pdo, $proofId)) {
                $message = "Payment proof deleted successfully!";
            } else {
                $message = "Error: Failed to delete payment proof.";
            }
        } else {
            $message = "Error: Invalid proof ID for deletion.";
        }
    }
}

// Get pending verifications
try {
    $stmt = $pdo->prepare("
        SELECT u.id as user_id, u.username, u.email, p.id as proof_id, p.file_path, p.uploaded_at, p.status
        FROM users u
        JOIN payment_proofs p ON u.id = p.user_id
        WHERE p.status = 1
        ORDER BY p.uploaded_at DESC
    ");
    $stmt->execute();
    $pendingProofs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$pageTitle = "User Verification Center";
include __DIR__ . '/../assets/template/intro-template.php';
?>

<style>
    .verification-container {
        max-width: 900px;
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

    .verification-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
    }

    .verification-card {
        background-color: var(--bg-tertiary);
        border-radius: 10px;
        padding: 1.2rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .verification-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    .proof-image {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin-bottom: 1rem;
        border: 1px solid var(--border-color);
        cursor: pointer;
        transition: transform 0.2s ease;
    }

    .proof-image:hover {
        transform: scale(1.02);
    }

    .user-details h3 {
        margin-bottom: 0.5rem;
        color: var(--text-primary);
        font-size: 1.3rem;
    }

    .user-details p {
        color: var(--text-secondary);
        font-size: 0.9rem;
        margin-bottom: 0.3rem;
    }

    .user-details small {
        color: var(--text-secondary);
        font-size: 0.8rem;
        opacity: 0.8;
    }

    .card-actions {
        margin-top: 1rem;
        display: flex;
        gap: 0.8rem;
    }

    .action-btn {
        padding: 0.7rem 1.2rem;
        border: none;
        border-radius: 25px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .verify-btn {
        background-color: #28a745; /* Green */
        color: white;
    }

    .verify-btn:hover {
        background-color: #218838;
        transform: translateY(-2px);
    }

    .delete-btn {
        background-color: #dc3545; /* Red */
        color: white;
    }

    .delete-btn:hover {
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
</style>

<div class="verification-container">
    <h1>User Verification Center</h1>

    <?php if ($message): ?>
        <div class="message <?php echo (strpos($message, 'Error') !== false) ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($pendingProofs)): ?>
        <div class="empty-state">
            <i class="material-icons-outlined">check_circle_outline</i>
            <p>No pending verifications at the moment. All clear!</p>
        </div>
    <?php else: ?>
        <div class="verification-grid">
            <?php foreach ($pendingProofs as $proof): ?>
                <div class="verification-card">
                    <img src="<?= htmlspecialchars($proof['file_path']) ?>" 
                         class="proof-image" 
                         alt="Payment proof for <?= htmlspecialchars($proof['username']) ?>">
                    
                    <div class="user-details">
                        <h3><?= htmlspecialchars($proof['username']) ?></h3>
                        <p><?= htmlspecialchars($proof['email']) ?></p>
                        <small>Uploaded: <?= date('M j, Y H:i', strtotime($proof['uploaded_at'])) ?></small>
                    </div>
                    
                    <div class="card-actions">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="verify">
                            <input type="hidden" name="proof_id" value="<?= $proof['proof_id'] ?>">
                            <input type="hidden" name="user_id" value="<?= $proof['user_id'] ?>">
                            <button type="submit" class="action-btn verify-btn">
                                Verify
                            </button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this payment proof? This action cannot be undone.');">
                            <input type="hidden" name="action" value="delete_proof">
                            <input type="hidden" name="proof_id" value="<?= $proof['proof_id'] ?>">
                            <button type="submit" class="action-btn delete-btn">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../assets/template/end-template.php'; ?>