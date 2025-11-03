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
    if (isset($_POST['action']) && $_POST['action'] === 'unverify') {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

        if ($userId) {
            try {
                // Update user status to unverified (e.g., status 1)
                $stmt = $pdo->prepare("UPDATE users SET status = 1 WHERE id = :user_id");
                $stmt->execute([':user_id' => $userId]);

                $message = "User has been un-verified successfully!";

            } catch (PDOException $e) {
                $message = "Error un-verifying user: " . $e->getMessage();
            }
        } else {
            $message = "Error: Invalid user ID for un-verification.";
        }
    }
}

// Get search query
$searchEmail = filter_input(INPUT_GET, 'search_email', FILTER_SANITIZE_EMAIL);

// Get verified users
try {
    $sql = "
        SELECT id as user_id, username, email, status
        FROM users
        WHERE status = 2
    ";
    $params = [];

    if ($searchEmail) {
        $sql .= " AND email LIKE :search_email";
        $params[':search_email'] = '%' . $searchEmail . '%';
    }

    $sql .= " ORDER BY username ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $verifiedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$pageTitle = "User Un-verification Center";
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
    }

    h1 {
        color: var(--text-primary);
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .message {
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        text-align: center;
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

    .user-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
    }

    .user-card {
        background-color: var(--bg-tertiary);
        border-radius: 10px;
        padding: 1.2rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .user-details h3 {
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }

    .user-details p {
        color: var(--text-secondary);
        font-size: 0.9rem;
        margin-bottom: 0.3rem;
    }

    .card-actions {
        margin-top: 1rem;
    }

    .action-btn {
        padding: 0.7rem 1.2rem;
        border: none;
        border-radius: 25px;
        font-weight: 600;
        cursor: pointer;
    }

    .unverify-btn {
        background-color: #dc3545; /* Red */
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--text-secondary);
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .search-form {
        display: flex;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .search-wrapper {
        position: relative;
        flex-grow: 1;
    }

    .search-wrapper i {
        position: absolute;
        top: 50%;
        left: 15px;
        transform: translateY(-50%);
        color: var(--text-secondary);
        font-size: 24px;
    }

    .search-form input {
        width: 100%;
        padding: 0.8rem 0.8rem 0.8rem 50px;
        border: 1px solid var(--border-color);
        border-radius: 25px;
        background-color: var(--bg-tertiary);
        color: var(--text-primary);
        transition: all 0.3s ease;
    }

    .search-form input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(var(--primary-color-rgb), 0.2);
    }

    .search-form button {
        padding: 0.8rem 1.2rem;
        border: none;
        background-color: var(--primary-color);
        color: white;
        border-radius: 25px;
        cursor: pointer;
        margin-left: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
    }

    .search-form button i {
        font-size: 20px;
    }

    .user-count {
        text-align: right;
        margin-bottom: 1rem;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-secondary);
    }
</style>

<div class="verification-container">
    <h1>User Un-verification Center</h1>

    <?php if ($message): ?>
        <div class="message <?php echo (strpos($message, 'Error') !== false) ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="user-count">
        <p>Total Verified Users: <?= count($verifiedUsers) ?></p>
    </div>

    <form method="GET" class="search-form">
        <div class="search-wrapper">
            <i class="material-icons-outlined">search</i>
            <input type="text" name="search_email" placeholder="Search by email..." value="<?= htmlspecialchars($searchEmail ?? '') ?>" onchange="this.form.submit()">
        </div>
        <button type="submit" style="display: none;">Search</button>
    </form>

    <?php if (empty($verifiedUsers)): ?>
        <div class="empty-state">
            <i class="material-icons-outlined">remove_circle_outline</i>
            <p>No verified users to display.</p>
        </div>
    <?php else: ?>
        <div class="user-grid">
            <?php foreach ($verifiedUsers as $user): ?>
                <div class="user-card">
                    <div class="user-details">
                        <h3><?= htmlspecialchars($user['username']) ?></h3>
                        <p><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    
                    <div class="card-actions">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to un-verify this user? They will lose access to licensed features.');">
                            <input type="hidden" name="action" value="unverify">
                            <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                            <button type="submit" class="action-btn unverify-btn">
                                Un-verify User
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../assets/template/end-template.php'; ?>
