<?php
require_once __DIR__ . '/../src/functions.php';
check_auth();

$loggedInUser = $_SESSION['user'];
$loggedInUserId = $loggedInUser['id'];

// Fetch transactions for the logged-in user
function getUserTransactions($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$transactions = getUserTransactions($pdo, $loggedInUserId);

require_once __DIR__ . '/../assets/template/intro-template.php';
?>

<style>
    /* --- CSS Variables and Theme Setup --- */
    :root {
        --font-primary: 'Inter', 'Noto Sans', sans-serif;
        --accent-color: #0c7ff2; /* A more vibrant blue */
        --accent-text: #ffffff;

        /* Light Theme Variables */
        --bg-primary-light: #f4f7fa;
        --bg-secondary-light: #ffffff;
        --bg-tertiary-light: #e9eef2;
        --text-primary-light: #111418;
        --text-secondary-light: #5a6470;
        --border-color-light: #dde3e9;
        
        /* Dark Theme Variables */
        --bg-primary-dark: #111418;
        --bg-secondary-dark: #1b2127;
        --bg-tertiary-dark: #283039;
        --text-primary-dark: #ffffff;
        --text-secondary-dark: #9cabba;
        --border-color-dark: #3b4754;

        /* Default to Light Theme */
        --bg-primary: var(--bg-primary-light);
        --bg-secondary: var(--bg-secondary-light);
        --bg-tertiary: var(--bg-tertiary-light);
        --text-primary: var(--text-primary-light);
        --text-secondary: var(--text-secondary-light);
        --border-color: var(--border-color-light);

        --select-arrow: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' fill='%239cabba' viewBox='0 0 256 256'%3e%3cpath d='M215.39,92.61,132.61,175.39a8,8,0,0,1-11.22,0L41.61,92.61A8,8,0,0,1,52.83,81.39H203.17a8,8,0,0,1,11.22,11.22Z'%3e%3c/path%3e%3c/svg%3e");
    }

    html[data-theme="dark"] {
        --bg-primary: var(--bg-primary-dark);
        --bg-secondary: var(--bg-secondary-dark);
        --bg-tertiary: var(--bg-tertiary-dark);
        --text-primary: var(--text-primary-dark);
        --text-secondary: var(--text-secondary-dark);
        --border-color: var(--border-color-dark);
    }

    /* --- CSS Reset and Base Styles --- */
    *,
    *::before,
    *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: var(--font-primary);
        background-color: var(--bg-primary);
        color: var(--text-primary);
        min-height: 100vh;
        font-size: 15px;
        line-height: 1.5;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    a {
        text-decoration: none;
        color: inherit;
        transition: color 0.2s ease;
    }

    button {
        font-family: inherit;
        cursor: pointer;
        border: none;
        background: none;
        color: inherit;
    }

    .icon {
        width: 22px;
        height: 22px;
        stroke-width: 1.8;
        display: inline-block;
        vertical-align: middle;
    }

    /* --- Main Layout --- */
    .page-container {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .main-content {
        flex-grow: 1;
        display: flex;
        justify-content: center;
        padding: 1.5rem 1rem;
    }

    .content-wrapper {
        width: 100%;
        max-width: 960px;
    }

    /* --- Content Section --- */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding: 0 0.25rem;
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 900;
    }

    .btn {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 38px;
        padding: 0 1rem;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 500;
        background-color: var(--bg-tertiary);
        gap: 0.5rem;
        transition: background-color 0.2s;
    }

    .btn:hover {
        background-color: var(--border-color);
    }

    .btn.btn-primary {
        background-color: var(--accent-color);
        color: var(--accent-text);
    }

    .btn.btn-primary:hover {
        opacity: 0.9;
    }

    /* --- Tabs and Filters --- */
    .tabs-nav {
        display: flex;
        gap: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 1.5rem;
        padding: 0 0.25rem;
    }

    .tab-link {
        padding: 0.75rem 0.25rem;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        color: var(--text-secondary);
        font-weight: 700;
        font-size: 0.85rem;
        transition: color 0.2s, border-color 0.2s;
    }

    .tab-link:hover {
        color: var(--text-primary);
    }

    .tab-link.active {
        color: var(--text-primary);
        border-bottom-color: var(--text-primary);
    }

    .filter-controls {
        margin-bottom: 1.5rem;
        padding: 0 0.25rem;
    }

    .form-select {
        width: 100%;
        max-width: 280px;
        height: 48px;
        padding: 0 1rem;
        background-color: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-primary);
        font-size: 0.9rem;
        -webkit-appearance: none;
        appearance: none;
        background-image: var(--select-arrow);
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1.1rem;
    }

    .form-select:focus {
        outline: none;
        border-color: var(--accent-color);
    }

    /* --- History Table --- */
    .table-container {
        overflow-x: auto;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        background-color: var(--bg-secondary);
    }

    .history-table {
        width: 100%;
        border-collapse: collapse;
    }

    .history-table th,
    .history-table td {
        padding: 1rem 1.25rem;
        text-align: left;
        white-space: nowrap;
        font-size: 0.85rem;
    }

    .history-table thead {
        background-color: var(--bg-tertiary);
    }

    .history-table th {
        font-weight: 500;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .history-table tbody tr {
        border-top: 1px solid var(--border-color);
        transition: background-color 0.2s;
    }

    .history-table tbody tr:hover {
        background-color: var(--bg-tertiary);
    }

    .history-table td {
        color: var(--text-secondary);
    }

    .history-table td.text-main {
        color: var(--text-primary);
        font-weight: 500;
    }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 16px;
        font-weight: 500;
        font-size: 0.8rem;
        min-width: 90px;
        text-align: center;
    }

    .status-badge.credit {
        background-color: rgba(34, 197, 94, 0.1);
        color: #22c55e;
    }

    .status-badge.debit {
        background-color: rgba(234, 179, 8, 0.1);
        color: #eab308;
    }

    .status-badge.transfer_in {
        background-color: rgba(34, 197, 94, 0.1);
        color: #22c55e;
    }

    .status-badge.transfer_out {
        background-color: rgba(234, 179, 8, 0.1);
        color: #eab308;
    }

    .status-badge.asset_profit {
        background-color: rgba(34, 197, 94, 0.1);
        color: #22c55e;
    }

    html[data-theme="dark"] .status-badge.credit {
        background-color: rgba(74, 222, 128, 0.15);
        color: #4ade80;
    }

    html[data-theme="dark"] .status-badge.debit {
        background-color: rgba(252, 211, 77, 0.15);
        color: #facc15;
    }

    html[data-theme="dark"] .status-badge.transfer_in {
        background-color: rgba(74, 222, 128, 0.15);
        color: #4ade80;
    }

    html[data-theme="dark"] .status-badge.transfer_out {
        background-color: rgba(252, 211, 77, 0.15);
        color: #facc15;
    }

    html[data-theme="dark"] .status-badge.asset_profit {
        background-color: rgba(74, 222, 128, 0.15);
        color: #4ade80;
    }

    /* --- Desktop & Tablet Styles --- */
    @media (min-width: 820px) {
        .main-header {
            padding: 0.75rem 2rem;
        }

        .header-brand h2,
        .user-profile-name {
            display: block;
        }

        .header-nav-desktop {
            display: flex;
            align-items: center;
            gap: 2.5rem;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .header-nav-desktop a {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .header-nav-desktop a:hover {
            color: var(--text-primary);
        }

        #burger-menu {
            display: none;
        }

        .main-content {
            padding: 2.5rem;
        }

        .page-title {
            font-size: 2rem;
        }

        .btn {
            height: 40px;
        }
    }
</style>

<main>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Transactions</h1>
            <button class="btn" id="export-btn">
                <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                <span>Export</span>
            </button>
        </div>

        <div class="tabs-nav" id="tabs-container">
            <a href="#" class="tab-link active" data-filter="all">All</a>
            <a href="#" class="tab-link" data-filter="debit">Debits</a>
            <a href="#" class="tab-link" data-filter="credit">Credits</a>
            <a href="#" class="tab-link" data-filter="payout">Payouts</a>
            <a href="#" class="tab-link" data-filter="asset_profit">Asset Profits</a>
        </div>

        <div class="filter-controls">
            <select class="form-select" aria-label="Filter by asset">
                <option value="all">All Assets</option>
                <!-- Asset options will be dynamically loaded here if needed -->
            </select>
        </div>
        
        <div class="table-container">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody id="history-table-body">
                    <?php foreach ($transactions as $transaction): ?>
                        <tr data-type="<?php echo htmlspecialchars($transaction['type']); ?>">
                            <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($transaction['created_at']))); ?></td>
                            <td class="text-main"><?php echo htmlspecialchars(ucwords(str_replace(['_', 'transfer_in', 'transfer_out'], [' ', 'credit', 'payout'], $transaction['type']))); ?></td>
                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                            <td>
                                <span class="status-badge <?php echo htmlspecialchars($transaction['type']); ?>">
                                    <?php echo ($transaction['type'] === 'debit' || $transaction['type'] === 'transfer_out') ? '-' : '+'; ?>₦<?php echo number_format(abs($transaction['amount']), 2); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="4" style="text-align: center;">No transactions found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- Transaction Filtering ---
        const tabsContainer = document.getElementById('tabs-container');
        const tableRows = document.querySelectorAll('#history-table-body tr');

        tabsContainer.addEventListener('click', (e) => {
            e.preventDefault();
            const targetTab = e.target.closest('.tab-link');
            if (!targetTab) return;

            // Update active tab style
            tabsContainer.querySelector('.active').classList.remove('active');
            targetTab.classList.add('active');

            const filter = targetTab.dataset.filter;
            
            // Filter table rows
            tableRows.forEach(row => {
                const rowType = row.dataset.type;
                if (filter === 'all' || filter === rowType) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // --- Export Button ---
        document.getElementById('export-btn').addEventListener('click', () => {
            alert('Exporting transactions...');
        });
    });
</script>

<?php
require_once __DIR__ . '/../assets/template/end-template.php';
?>