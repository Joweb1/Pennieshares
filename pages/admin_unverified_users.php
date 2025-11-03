<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

if (!$user['is_admin']) {
    header('Location: /dashboard');
    exit;
}

$message = '';

// Handle Verify User Account form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_user_account') {
    $userIdToVerify = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if ($userIdToVerify) {
        if (verifyUserAccount($pdo, $userIdToVerify)) {
            $message = "User account verified successfully.";
        } else {
            $message = "Error: Failed to verify user account.";
        }
    } else {
        $message = "Error: Invalid User ID.";
    }
}


$search_query = trim($_GET['search'] ?? '');
$unverified_users = getUnverifiedUsers($pdo, $search_query);

require_once __DIR__ . '/../assets/template/intro-template.php';
?>

<style>
    .unverified-users-container {
        width: 95vw;
        max-width: 1200px;
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

    .table-responsive {
        overflow-x: auto;
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    table {
        width: 100%;
        border-collapse: collapse;
        border-radius: 8px;
        overflow: hidden; /* Ensures rounded corners apply to table */
    }
    th, td {
        border: 1px solid var(--border-color);
        padding: 12px 15px;
        text-align: left;
        vertical-align: middle;
        color: var(--text-secondary);
    }
    th {
        background-color: var(--bg-tertiary);
        color: var(--text-primary);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9em;
    }
    tr:nth-child(even) {
        background-color: var(--bg-tertiary);
    }
    tr:hover {
        background-color: var(--border-color);
    }
    .action-btn {
        padding: 0.5rem 1rem;
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
        table, thead, tbody, th, td, tr {
            display: block;
        }
        thead tr {
            position: absolute;
            top: -9999px;
            left: -9999px;
        }
        tr {
            border: 1px solid var(--border-color);
            margin-bottom: 1rem;
            border-radius: 8px;
        }
        td {
            border: none;
            border-bottom: 1px solid var(--border-color);
            position: relative;
            padding-left: 50%;
            text-align: right;
        }
        td:before {
            position: absolute;
            top: 6px;
            left: 6px;
            width: 45%;
            padding-right: 10px;
            white-space: nowrap;
            content: attr(data-label);
            font-weight: 600;
            color: var(--text-primary);
            text-align: left;
        }
    }
</style>

<div class="unverified-users-container">
    <h1>Unverified Users</h1>

    <?php if ($message): ?>
        <div class="message <?php echo (strpos($message, 'Error') !== false) ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="user-count">
        <p>Total Unverified Users: <?= count($unverified_users) ?></p>
    </div>

    <form method="GET" class="search-form">
        <div class="search-wrapper">
            <i class="material-icons-outlined">search</i>
            <input type="text" name="search" placeholder="Search by email..." value="<?= htmlspecialchars($search_query ?? '') ?>" onchange="this.form.submit()">
        </div>
        <button type="submit" style="display: none;">Search</button>
    </form>

    <?php if (empty($unverified_users)): ?>
        <div class="empty-state">
            <i class="material-icons-outlined">check_circle_outline</i>
            <p>No unverified users found.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unverified_users as $user): ?>
                        <tr>
                            <td data-label="User ID"><?php echo $user['id']; ?></td>
                            <td data-label="Username"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td data-label="Full Name"><?php echo htmlspecialchars($user['fullname']); ?></td>
                            <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td data-label="Actions">
                                <form method="post" style="display:inline-block;">
                                    <input type="hidden" name="action" value="verify_user_account">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="action-btn verify-btn">
                                        Verify
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../assets/template/end-template.php'; ?>
