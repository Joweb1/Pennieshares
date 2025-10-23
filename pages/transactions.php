<?php
require_once __DIR__ . '/../src/functions.php';
check_auth();

$loggedInUser = $_SESSION['user'];
$loggedInUserId = $loggedInUser['id'];

// Pagination settings
$transactions_per_page = 12;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $transactions_per_page;

// Filter settings
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Fetch paginated transactions with filter
$transactions = getPaginatedWalletTransactions($pdo, $loggedInUserId, $transactions_per_page, $offset, $filter_type);

// Get total transaction count for pagination with filter
$total_transactions = getTotalWalletTransactionCount($pdo, $loggedInUserId, $filter_type);
$total_pages = ceil($total_transactions / $transactions_per_page);

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

    .status-badge.debit,
    .status-badge.payout {
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

    html[data-theme="dark"] .status-badge.debit,
    html[data-theme="dark"] .status-badge.payout {
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

    /* --- Export Modal Styles --- */
    .export-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.4s ease, visibility 0.4s ease;
    }
    .export-modal-overlay.visible {
        opacity: 1;
        visibility: visible;
    }
    .export-modal-content {
        border-radius: 24px;
        padding: 1.5rem;
        width: 90%;
        max-width: 800px; /* Increased max-width for wider popup */
        text-align: center;
        transform: scale(0.9);
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        display: flex;
        flex-direction: column;
        height: 90vh; /* Changed from max-height to height */
        overflow: hidden; /* Hide overflow of the modal content itself */
    }
    html[data-theme="light"] .export-modal-content {
        background: rgba(255, 255, 255, 0.75);
        border: 1px solid rgba(255, 255, 255, 1);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
    }
    html[data-theme="dark"] .export-modal-content {
         background: rgba(30, 41, 59, 0.6);
         border: 1px solid rgba(255, 255, 255, 0.15);
    }

    /* --- Modal States --- */
    .modal-state { display: none; flex-grow: 1; }
    .modal-state.active { display: flex; flex-direction: column; }

    /* Processing Animation */
    .processing-animation .spinner {
        width: 80px;
        height: 80px;
        border: 6px solid rgba(var(--accent-color-rgb), 0.2);
        border-top-color: var(--accent-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 1.5rem;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .modal-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }
    .modal-text {
        font-size: 1rem;
        color: var(--text-secondary);
    }

    /* History Display State */
    .history-display-content {
        text-align: left;
        padding: 0.5rem 0; /* Reduced padding */
        margin-bottom: 0.5rem; /* Reduced margin */
        border-top: 1px solid var(--border-color);
        border-bottom: 1px solid var(--border-color);
    }
    .history-scroll-wrapper {
        flex-grow: 1; /* Allows it to take available space */
        overflow-y: auto; /* Makes this specific container scrollable */
        padding: 0 1rem; /* Add some horizontal padding */
        height: 65vh; /* Explicitly set height for scrolling */
    }
    .history-display-content h3 {
        font-size: 0.7rem; /* Adjusted font size */
        margin-top: 0.35rem;
        margin-bottom: 0.18rem;
        color: var(--text-primary);
    }
    .history-display-content p {
        font-size: 0.6rem; /* Adjusted font size */
        color: var(--text-secondary);
        margin-bottom: 0.08rem;
    }
    .history-display-content table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0.35rem;
    }
    .history-display-content th, .history-display-content td {
        border: 1px solid var(--border-color);
        padding: 2px; /* Adjusted padding */
        font-size: 0.6rem; /* Adjusted font size */
        text-align: left;
    }
    .history-display-content th {
        background-color: var(--bg-tertiary);
        font-weight: 600;
    }
    .history-display-content .company-info, .history-display-content .user-info {
        text-align: center;
        margin-bottom: 0.35rem; /* Adjusted margin */
    }
    .history-display-content .company-logo {
        max-width: 55px; /* Adjusted logo size */
        margin-bottom: 0.15rem;
    }
    .history-display-content .page-break {
        page-break-before: always;
        margin-top: 0.7rem; /* Adjusted margin */
        text-align: center;
        color: var(--text-secondary);
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
            <a href="?type=all" class="tab-link <?php echo ($filter_type == 'all') ? 'active' : ''; ?>" data-filter="all">All</a>
            <a href="?type=debit" class="tab-link <?php echo ($filter_type == 'debit') ? 'active' : ''; ?>" data-filter="debit">Debits</a>
            <a href="?type=credit" class="tab-link <?php echo ($filter_type == 'credit') ? 'active' : ''; ?>" data-filter="credit">Credits</a>
            <a href="?type=payout" class="tab-link <?php echo ($filter_type == 'payout') ? 'active' : ''; ?>" data-filter="payout">Payouts</a>
            <a href="?type=asset_profit" class="tab-link <?php echo ($filter_type == 'asset_profit') ? 'active' : ''; ?>" data-filter="asset_profit">Asset Profits</a>
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
                                    <?php echo ($transaction['type'] === 'debit' || $transaction['type'] === 'transfer_out' || $transaction['type'] === 'payout') ? '-' : '+'; ?>SV<?php echo number_format(abs($transaction['amount']), 2); ?>
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

        <!-- Pagination Controls -->
        <div class="pagination" style="margin-top: 20px; text-align: center;">
            <?php if ($total_pages > 1): ?>
                <?php
                $range = 2; // Number of pages to show around the current page
                $start_page = max(1, $current_page - $range);
                $end_page = min($total_pages, $current_page + $range);
                ?>

                <?php if ($current_page > 1): ?>
                    <a href="?page=<?php echo $current_page - 1; ?><?php echo ($filter_type !== 'all') ? '&type=' . $filter_type : ''; ?>" style="padding: 8px 15px; border: 1px solid var(--border-color); border-radius: 5px; margin-right: 5px; text-decoration: none; color: var(--text-primary);">Previous</a>
                <?php endif; ?>

                <?php if ($start_page > 1): ?>
                    <a href="?page=1<?php echo ($filter_type !== 'all') ? '&type=' . $filter_type : ''; ?>" style="padding: 8px 15px; border: 1px solid var(--border-color); border-radius: 5px; margin-right: 5px; text-decoration: none; color: var(--text-primary);">1</a>
                    <?php if ($start_page > 2): ?>
                        <span style="padding: 8px 15px; margin-right: 5px;">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo ($filter_type !== 'all') ? '&type=' . $filter_type : ''; ?>" style="padding: 8px 15px; border: 1px solid var(--border-color); border-radius: 5px; margin-right: 5px; text-decoration: none; color: <?php echo ($i == $current_page) ? 'var(--accent-color)' : 'var(--text-primary)'; ?>; font-weight: <?php echo ($i == $current_page) ? 'bold' : 'normal'; ?>;"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span style="padding: 8px 15px; margin-right: 5px;">...</span>
                    <?php endif; ?>
                    <a href="?page=<?php echo $total_pages; ?><?php echo ($filter_type !== 'all') ? '&type=' . $filter_type : ''; ?>" style="padding: 8px 15px; border: 1px solid var(--border-color); border-radius: 5px; margin-right: 5px; text-decoration: none; color: var(--text-primary);"><?php echo $total_pages; ?></a>
                <?php endif; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?><?php echo ($filter_type !== 'all') ? '&type=' . $filter_type : ''; ?>" style="padding: 8px 15px; border: 1px solid var(--border-color); border-radius: 5px; margin-left: 5px; text-decoration: none; color: var(--text-primary);">Next</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const baseUrl = '<?= BASE_URL ?>';
        // --- Transaction Filtering ---
        const tabsContainer = document.getElementById('tabs-container');
        const tableRows = document.querySelectorAll('#history-table-body tr');

        tabsContainer.addEventListener('click', (e) => {
            const targetTab = e.target.closest('.tab-link');
            if (!targetTab) return;

            e.preventDefault(); // Prevent default link behavior
            const filter = targetTab.dataset.filter;
            window.location.href = `?type=${filter}`; // Redirect to apply server-side filter
        });

        // --- Export Button ---
        // --- Export Button ---
        const exportBtn = document.getElementById('export-btn');
        const exportModal = document.getElementById('exportModal');
        const exportProcessingState = document.getElementById('exportProcessingState');
        const exportHistoryState = document.getElementById('exportHistoryState');
        const transactionHistoryContent = document.getElementById('transactionHistoryContent');
        const closeExportModalBtn = document.getElementById('closeExportModalBtn');
        const downloadPdfBtn = document.getElementById('downloadPdfBtn');

        exportBtn.addEventListener('click', async () => {
            // Show processing modal
            exportModal.classList.add('visible');
            exportProcessingState.classList.add('active');
            exportHistoryState.classList.remove('active');
            document.body.style.overflow = 'hidden'; // Prevent main page scrolling

            try {
                const response = await fetch('./api/generate_transaction_history'); // Removed limit parameter
                const data = await response.json();

                if (data.html) {
                    transactionHistoryContent.innerHTML = data.html;
                    exportProcessingState.classList.remove('active');
                    exportHistoryState.classList.add('active');
                } else {
                    transactionHistoryContent.innerHTML = '<p style="color: red;">Error generating history.</p>';
                    exportProcessingState.classList.remove('active');
                    exportHistoryState.classList.add('active');
                }
            } catch (error) {
                console.error('Error fetching transaction history:', error);
                transactionHistoryContent.innerHTML = '<p style="color: red;">Failed to load history. Please try again.</p>';
                exportProcessingState.classList.remove('active');
                exportHistoryState.classList.add('active');
            }
        });

        closeExportModalBtn.addEventListener('click', () => {
            exportModal.classList.remove('visible');
            document.body.style.overflow = ''; // Restore main page scrolling
        });

        downloadPdfBtn.addEventListener('click', async () => {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Define margins
            const margin = 10; // 10mm margin on each side
            const bottomMargin = 10; // 10mm bottom margin
            const pdfWidth = doc.internal.pageSize.getWidth();
            const pdfHeight = doc.internal.pageSize.getHeight();
            const contentWidth = pdfWidth - (margin * 2);
            const usablePageHeight = pdfHeight - (margin + bottomMargin); // Usable height for content

            // Ensure the content is fully rendered and visible for html2canvas
            const originalDisplay = exportHistoryState.style.display;
            exportHistoryState.style.display = 'block'; // Ensure it's laid out

            try {
                const canvas = await html2canvas(transactionHistoryContent, {
                    backgroundColor: '#ffffff',
                    scale: 3,
                    useCORS: true,
                    logging: true
                });
                
                const imgData = canvas.toDataURL('image/png');
                console.log('Generated imgData length:', imgData.length);

                const imgHeight = canvas.height * contentWidth / canvas.width; // Calculate height based on new contentWidth
                const ratio = contentWidth / canvas.width;
                const canvasHeight = canvas.height;

                let heightLeft = canvasHeight;
                let position = 0;

                doc.addImage(imgData, 'PNG', margin, margin, contentWidth, imgHeight);
                heightLeft -= usablePageHeight / ratio;

                while (heightLeft > 0) {
                    position = -usablePageHeight + heightLeft * ratio; // Calculate position for next page
                    doc.addPage();
                    doc.addImage(imgData, 'PNG', margin, position, contentWidth, imgHeight);
                    heightLeft -= usablePageHeight / ratio;
                }
                doc.save('transaction-history.pdf');
            } catch (error) {
                console.error('Error during PDF generation:', error);
                alert('Failed to generate PDF. Please try again or check console for details.');
            } finally {
                exportHistoryState.style.display = originalDisplay; // Restore original display
            }
        });

        // --- Update localStorage on page visit ---
        const currentTotalCount = <?php echo json_encode($total_transactions); ?>;
        // Only update if user is logged in
        <?php if (isset($_SESSION['user']['id'])): ?>
        const userId = <?php echo json_encode($_SESSION['user']['id']); ?>;
        localStorage.setItem(`lastReadTransactions_${userId}`, currentTotalCount);
        <?php endif; ?>
    });
</script>

<?php
require_once __DIR__ . '/../assets/template/end-template.php';
?>

<!-- Export Modal -->
<div class="export-modal-overlay" id="exportModal">
    <div class="export-modal-content">
        <!-- Processing State -->
        <div class="modal-state" id="exportProcessingState">
            <div class="processing-animation">
                <div class="spinner"></div>
            </div>
            <h3 class="modal-title">Generating Transaction History</h3>
            <p class="modal-text">Please wait while we prepare your export.</p>
        </div>

        <!-- Transaction History Display State -->
        <div class="modal-state" id="exportHistoryState">
            <h3 class="modal-title">Transaction History</h3>
            <div class="history-scroll-wrapper">
                <div class="history-display-content" id="transactionHistoryContent">
                    <!-- History will be loaded here via AJAX -->
                </div>
            </div>
            <button class="btn btn-primary btn-full-width" id="downloadPdfBtn">Download PDF</button>
            <button class="btn btn-full-width close-modal-btn" id="closeExportModalBtn">Close</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
