<?php
require_once __DIR__ . '/../src/init.php';

$page_title = "Partners";
require_once __DIR__ . '/../assets/template/intro-template.php';

// Fetch partners (users who have the current user's partner code as their referral)
$stmt = $pdo->prepare("SELECT * FROM users WHERE referral = ?");
$stmt->execute([$user['partner_code']]);
$partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_partners = count($partners);
$activated_partners = 0;
foreach ($partners as $partner) {
    if ($partner['status'] == 2) { // Assuming status 2 means activated
        $activated_partners++;
    }
}

$referral_link = "https://pennieshares.com/register?partnercode=" . htmlspecialchars($user['partner_code']);

?>

<style type="text/css">
    /* --- CSS VARIABLES & THEME SETUP --- */
    :root {
    --font-primary: Inter, "Noto Sans", sans-serif;
    
    /* Light Theme */
    --color-background: #f8fafc;
    --color-background-alt: #ffffff;
    --color-background-muted: #e7edf4;
    --color-text-primary: #0d141c;
    --color-text-secondary: #49739c;
    --color-text-on-primary: #f8fafc;
    --color-border: #cedbe8;
    --color-primary: #0c7ff2;
    --color-primary-hover: #0a69c4;
    }
    
    html[data-theme='dark'] {
    --color-background: #0d141c;
    --color-background-alt: #1a232e;
    --color-background-muted: #2c3847;
    --color-text-primary: #f0f4f8;
    --color-text-secondary: #a0b3c6;
    --color-text-on-primary: #f8fafc;
    --color-border: #3a495c;
    --color-primary: #1d90f5;
    --color-primary-hover: #3ca0ff;
    }
    
    /* --- BASE & RESET STYLES --- */
    * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    }
    
    body {
    font-family: var(--font-primary);
    background-color: var(--color-background);
    color: var(--color-text-primary);
    transition: background-color 0.3s, color 0.3s;
    overflow-x: hidden;
    }
    
    h1, h2, h3 {
    font-weight: 700;
    letter-spacing: -0.015em;
    line-height: 1.2;
    }
    
    a {
    color: inherit;
    text-decoration: none;
    }
    
    button {
    font-family: inherit;
    border: none;
    background: none;
    cursor: pointer;
    }
    
    /* --- LAYOUT --- */
    .page-container {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    transition: transform 0.3s ease-in-out;
    }
    
    .main-content {
    flex-grow: 1;
    display: flex;
    justify-content: center;
    padding: 1.25rem;
    }
    
    .content-wrapper {
    width: 100%;
    max-width: 960px;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    }
    
    /* --- HEADER --- */
    .header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1.5rem;
    border-bottom: 1px solid var(--color-border);
    background-color: var(--color-background-alt);
    position: sticky;
    top: 0;
    z-index: 99;
    transition: background-color 0.3s, border-color 0.3s;
    }
    
    .header-left, .header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
    }
    
    .logo-mobile {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--color-text-primary);
    }
    .logo-mobile svg { width: 1.25rem; height: 1.25rem; }
    .logo-mobile h2 { font-size: 1.1rem; }
    
    .user-profile {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    }
    .user-profile img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    }
    .user-profile span {
    font-size: 0.9rem;
    font-weight: 500;
    }
    
    /* --- SIDEBAR NAVIGATION --- */
    .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 260px;
    height: 100vh;
    background-color: var(--color-background-alt);
    border-right: 1px solid var(--color-border);
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    transform: translateX(-100%);
    transition: transform 0.3s ease-in-out, background-color 0.3s, border-color 0.3s;
    z-index: 1000;
    }
    .sidebar.is-open {
    transform: translateX(0);
    }
    .sidebar-header {
    margin-bottom: 2rem;
    }
    .logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--color-text-primary);
    }
    .logo svg { width: 1.5rem; height: 1.5rem; }
    .logo h2 { font-size: 1.25rem; font-weight: 700; }
    
    .sidebar-nav {
    list-style: none;
    flex-grow: 1;
    }
    .sidebar-nav li a {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--color-text-secondary);
    transition: background-color 0.2s, color 0.2s;
    }
    .sidebar-nav li a:hover,
    .sidebar-nav li a:focus {
    background-color: var(--color-background-muted);
    color: var(--color-text-primary);
    }
    .sidebar-nav li a svg {
    transition: transform 0.2s;
    }
    .sidebar-nav li a:hover svg {
    transform: scale(1.1);
    }
    
    .btn-help {
    width: 100%;
    }
    
    .sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.4);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s, visibility 0.3s;
    z-index: 999;
    }
    .sidebar-overlay.is-open {
    opacity: 1;
    visibility: visible;
    }
    
    
    /* --- THEME TOGGLE --- */
    #theme-toggle .sun-icon { display: none; }
    #theme-toggle .moon-icon { display: block; }
    html[data-theme='dark'] #theme-toggle .sun-icon { display: block; }
    html[data-theme='dark'] #theme-toggle .moon-icon { display: none; }
    
    
    /* --- GENERIC COMPONENTS --- */
    .icon-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    color: var(--color-text-secondary);
    border-radius: 50%;
    transition: background-color 0.2s, color 0.2s;
    }
    .icon-btn:hover {
    background-color: var(--color-background-muted);
    color: var(--color-text-primary);
    }
    #burger-menu {
    display: none;
    }
    
    .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0 1rem;
    height: 40px;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 700;
    transition: background-color 0.2s, color 0.2s, box-shadow 0.2s;
    white-space: nowrap;
    }
    .btn-primary {
    background-color: var(--color-primary);
    color: var(--color-text-on-primary);
    }
    .btn-primary:hover {
    background-color: var(--color-primary-hover);
    box-shadow: 0 4px 12px rgba(12, 127, 242, 0.2);
    }
    .btn-secondary {
    background-color: var(--color-background-muted);
    color: var(--color-text-primary);
    }
    .btn-secondary:hover {
    background-color: var(--color-border);
    }
    
    .card {
    flex: 1 1 160px;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid var(--color-border);
    transition: border-color 0.3s, background-color 0.3s;
    background-color: var(--color-background-alt);
    }
    .card p {
    font-size: 1rem;
    font-weight: 500;
    }
    .card span {
    font-size: 1.5rem;
    font-weight: 700;
    }
    
    .form-input {
    width: 100%;
    height: 56px;
    padding: 0 1rem;
    background-color: var(--color-background);
    color: var(--color-text-primary);
    border: 1px solid var(--color-border);
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s, background-color 0.2s;
    }
    .form-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 2px rgba(12, 127, 242, 0.2);
    }
    
    /* --- PAGE-SPECIFIC STYLES --- */
    .page-title h1 {
    font-size: 2rem;
    }
    .page-title p {
    color: var(--color-text-secondary);
    font-size: 0.9rem;
    margin-top: 0.25rem;
    }
    
    .stats-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    }
    
    .referral-link-section {
    max-width: 480px;
    }
    .input-with-icon {
    position: relative;
    display: flex;
    align-items: center;
    }
    .input-with-icon .form-input {
    padding-right: 50px;
    }
    .input-with-icon .copy-btn {
    position: absolute;
    right: 4px;
    width: 48px;
    height: 48px;
    }
    .copy-feedback {
    position: absolute;
    background-color: var(--color-text-primary);
    color: var(--color-background-alt);
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    opacity: 0;
    transition: opacity 0.2s;
    pointer-events: none;
    }
    .copy-btn.copied .copy-feedback {
    opacity: 1;
    }
    
    .action-buttons {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    }
    
    .table-container {
    overflow: hidden;
    border: 1px solid var(--color-border);
    border-radius: 8px;
    background-color: var(--color-background-alt);
    container-type: inline-size;
    }
    .table-wrapper {
    overflow-x: auto;
    }
    table {
    width: 100%;
    border-collapse: collapse;
    }
    th, td {
    padding: 1rem;
    text-align: left;
    font-size: 0.875rem;
    white-space: nowrap;
    }
    th {
    font-weight: 500;
    color: var(--color-text-primary);
    }
    td {
    color: var(--color-text-secondary);
    }
    tbody tr {
    border-top: 1px solid var(--color-border);
    }
    tbody td.col-name {
    color: var(--color-text-primary);
    }
    .status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 16px;
    font-size: 0.8rem;
    font-weight: 500;
    width: 100%;
    max-width: 100px;
    text-align: center;
    }
    .status-confirmed {
    background-color: #d1fae5;
    color: #065f46;
    }
    .status-pending {
    background-color: #fee2e2;
    color: #991b1b;
    }
    html[data-theme='dark'] .status-confirmed {
    background-color: #064e3b;
    color: #a7f3d0;
    }
    html[data-theme='dark'] .status-pending {
    background-color: #7f1d1d;
    color: #fecaca;
    }
    
    
    @container (max-width: 480px) { .col-bonus { display: none; } }
    @container (max-width: 360px) { .col-asset { display: none; } }
    @container (max-width: 240px) { .col-date { display: none; } }
    
    .accordion-container {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    }
    .accordion-item {
    border: 1px solid var(--color-border);
    border-radius: 8px;
    padding: 0.5rem 1rem;
    background-color: var(--color-background-alt);
    }
    .accordion-item summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    padding: 0.5rem 0;
    font-weight: 500;
    }
    .accordion-item summary::-webkit-details-marker {
    display: none;
    }
    .accordion-item .caret-icon {
    transition: transform 0.2s;
    }
    .accordion-item[open] > summary .caret-icon {
    transform: rotate(180deg);
    }
    .accordion-item p {
    padding-bottom: 0.5rem;
    color: var(--color-text-secondary);
    line-height: 1.6;
    }
    
    /* --- RESPONSIVE STYLES --- */
    @media (max-width: 768px) {
    .header {
    padding: 0.75rem 1rem;
    }
    .user-profile span {
    display: none;
    }
    .main-content {
    padding: 1.5rem 1rem;
    }
    #burger-menu {
    display: flex;
    }
    .logo-mobile {
    display: none;
    }
    }
    
</style>
<div class="content-wrapper">
    <div class="page-title">
        <h1>Partners</h1>
        <p>Invite your friends to partner with you on Pennieshares and stand a chance to become a broker</p>
    </div>

    <h3>Partner Summary</h3>
    <div class="stats-grid">
        <div class="card">
            <p>Total Partner</p>
            <span><?= $total_partners ?></span>
        </div>
        <div class="card">
            <p>Activated Partners</p>
            <span><?= $activated_partners ?></span>
        </div>
    </div>

    <h3>Partnering Link</h3>
    <div class="referral-link-section">
        <div class="input-with-icon">
            <input id="referral-link-input" class="form-input" readonly value="<?= $referral_link ?>" />
            <button id="copy-btn" class="icon-btn copy-btn" aria-label="Copy partner codec">
                <svg class="copy-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M216,32H88a8,8,0,0,0-8,8V80H40a8,8,0,0,0-8,8V216a8,8,0,0,0,8,8H168a8,8,0,0,0,8-8V176h40a8,8,0,0,0,8-8V40A8,8,0,0,0,216,32ZM160,208H48V96H160Zm48-48H176V88a8,8,0,0,0-8-8H96V48H208Z"></path></svg>
                <span class="copy-feedback">Copied!</span>
            </button>
        </div>
    </div>

    <div class="action-buttons">
        <button id="share-btn" class="btn btn-primary">
            <span class="material-icons-outlined">share</span>
            <span>Share</span>
        </button>
        <a href="/terms" class="btn btn-secondary">
            <span class="material-icons-outlined">description</span>
            <span>View Terms</span>
        </a>
    </div>

    <h3>Partner History</h3>
    <div class="table-container">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th class="col-name">Partner Name/Partnering Code</th>
                        <th class="col-date">Date Joined</th>
                        <th class="col-status">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($partners as $partner): ?>
                        <tr>
                            <td class="col-name"><?= htmlspecialchars($partner['fullname']) ?> / #<?= htmlspecialchars($partner['partner_code']) ?></td>
                            <td class="col-date"><?= date('Y-m-d', strtotime($partner['created_at'])) ?></td>
                            <td class="col-status">
                                <span class="status-badge <?= $partner['status'] == 2 ? 'status-confirmed' : 'status-pending' ?>">
                                    <?= $partner['status'] == 2 ? 'Activated' : 'Not Activated' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
        
    <h3>Get Partners</h3>
    <div class="accordion-container">
        <details class="accordion-item" open>
            <summary>
                <span>How to get partners</span>
                <svg class="caret-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256"><path d="M213.66,101.66l-80,80a8,8,0,0,1-11.32,0l-80-80A8,8,0,0,1,53.66,90.34L128,164.69l74.34-74.35a8,8,0,0,1,11.32,11.32Z"></path></svg>
            </summary>
            <p>
                1. Share your unique partner code with friends.<br>
                2. Your friend signs up and verify their account.<br>
                3. You now stand a chance to become a broker.
            </p>
        </details>
    </div>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', () => {
    // --- COPY TO CLIPBOARD ---
    const copyBtn = document.getElementById('copy-btn');
    const referralLinkInput = document.getElementById('referral-link-input');
    
    if (copyBtn && referralLinkInput) {
        copyBtn.addEventListener('click', () => {
            navigator.clipboard.writeText(referralLinkInput.value).then(() => {
                if (!copyBtn.classList.contains('copied')) {
                    copyBtn.classList.add('copied');
                    setTimeout(() => {
                        copyBtn.classList.remove('copied');
                    }, 2000);
                }
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        });
    }

    // --- SHARE FUNCTIONALITY ---
    const shareBtn = document.getElementById('share-btn');
    if (shareBtn && navigator.share) {
        shareBtn.addEventListener('click', () => {
            const referralLink = document.getElementById('referral-link-input').value;
            navigator.share({
                title: 'Join me on Pennieshares!',
                text: 'Join me on Pennieshares and start your investment journey.',
                url: referralLink
            }).then(() => {
                console.log('Thanks for sharing!');
            }).catch(console.error);
        });
    }
});
</script>

<?php
require_once __DIR__ . '/../assets/template/end-template.php';
?>
