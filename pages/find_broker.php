<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/kyc_functions.php';

$kyc_status = getKycStatus($pdo, $user['id']);
if (!$kyc_status || $kyc_status['status'] !== 'verified') {
    $_SESSION['show_kyc_popup'] = true;
    header('Location: /wallet');
    exit;
}

require_once __DIR__ . '/../src/functions.php';
check_auth(); // Ensure user is logged in

$search_result = null;
$search_query = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search_query = trim($_POST['broker_identifier'] ?? '');
    if (!empty($search_query)) {
        $search_result = findBrokerStatus($pdo, $search_query);
    } else {
        $search_result = 'empty_query';
    }
}

require_once __DIR__ . '/../assets/template/intro-template.php';
?>

<style>
    /* General Styles */
    body {
        font-family: 'Inter', sans-serif;
        background-color: var(--bg-primary);
        color: var(--text-primary);
        transition: background-color 0.3s, color 0.3s;
    }

    .find-broker-container {
        max-width: 600px;
        margin: 2rem auto;
        padding: 2rem;
        border-radius: 15px;
        text-align: center;
        /* Glassmorphism effect */
        background-color: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.18);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
    }

    html[data-theme="dark"] .find-broker-container {
        background-color: rgba(27, 33, 39, 0.5); /* Darker glassmorphism */
        border: 1px solid rgba(27, 33, 39, 0.6);
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5);
    }

    .page-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: var(--text-primary);
    }

    .search-form {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .search-input {
        flex-grow: 1;
        padding: 0.8rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background-color: var(--bg-secondary);
        color: var(--text-primary);
        font-size: 1rem;
        transition: border-color 0.3s, background-color 0.3s;
    }

    .search-input::placeholder {
        color: var(--text-secondary);
    }

    .search-input:focus {
        outline: none;
        border-color: var(--accent-color);
    }

    .search-button {
        padding: 0.8rem 1.5rem;
        background-color: var(--accent-color);
        color: var(--accent-text);
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 600;
        transition: background-color 0.3s;
    }

    .search-button:hover {
        background-color: #0a6edc; /* Slightly darker accent */
    }

    /* Result Modal/Section */
    .result-modal-overlay {
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
    .result-modal-overlay.visible {
        opacity: 1;
        visibility: visible;
    }
    .result-modal-content {
        border-radius: 24px;
        padding: 2.5rem;
        width: 90%;
        max-width: 380px;
        text-align: center;
        transform: scale(0.9);
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    html[data-theme="light"] .result-modal-content {
        background: rgba(255, 255, 255, 0.75);
        border: 1px solid rgba(255, 255, 255, 1);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
    }
    html[data-theme="dark"] .result-modal-content {
         background: rgba(30, 41, 59, 0.6);
         border: 1px solid rgba(255, 255, 255, 0.15);
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
        margin-bottom: 1.5rem;
    }

    /* Animation Styles */
    .animation-container {
        width: 100px;
        height: 100px;
        margin: 0 auto 1rem;
    }

    .checkmark__circle {
        stroke-dasharray: 166;
        stroke-dashoffset: 166;
        stroke-width: 2;
        stroke-miterlimit: 10;
        stroke: #28a745; /* Green for success */
        fill: none;
        animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }
    .checkmark {
        transform-origin: 50% 50%;
        stroke-dasharray: 48;
        stroke-dashoffset: 48;
        stroke-width: 2;
        stroke-miterlimit: 10;
        stroke: #28a745;
        fill: none;
        animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
    }

    .warning-circle {
        stroke-dasharray: 166;
        stroke-dashoffset: 166;
        stroke-width: 2;
        stroke-miterlimit: 10;
        stroke: #ffc107; /* Yellow for warning */
        fill: none;
        animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }
    .warning-line {
        transform-origin: 50% 50%;
        stroke-dasharray: 48;
        stroke-dashoffset: 48;
        stroke-width: 2;
        stroke-miterlimit: 10;
        stroke: #ffc107;
        fill: none;
        animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
    }

    .error-circle {
        stroke-dasharray: 166;
        stroke-dashoffset: 166;
        stroke-width: 2;
        stroke-miterlimit: 10;
        stroke: #dc3545; /* Red for error */
        fill: none;
        animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }
    .error-x-mark {
        transform-origin: 50% 50%;
        stroke-dasharray: 48;
        stroke-dashoffset: 48;
        stroke-width: 2;
        stroke-miterlimit: 10;
        stroke: #dc3545;
        fill: none;
        animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
    }

    @keyframes stroke {
        100% { stroke-dashoffset: 0; }
    }

    .close-modal-btn {
        padding: 0.8rem 1.5rem;
        background-color: var(--accent-color);
        color: var(--accent-text);
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 600;
        transition: background-color 0.3s;
        width: 100%;
    }
    .close-modal-btn:hover {
        background-color: #0a6edc;
    }
</style>

<main>
    <div class="find-broker-container">
        <h1 class="page-title">Find Your Broker</h1>
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Enter a username or partner code to check their certification status.</p>

        <form class="search-form" method="POST">
            <input type="text" name="broker_identifier" class="search-input" placeholder="Broker Username or Partner Code" value="<?php echo htmlspecialchars($search_query); ?>" required>
            <button type="submit" class="search-button">Search</button>
        </form>
    </div>

    <!-- Result Modal -->
    <div class="result-modal-overlay" id="resultModal">
        <div class="result-modal-content">
            <div class="animation-container" id="animation-container">
                <?php if ($search_result === 'certified_broker'): ?>
                    <svg class="checkmark-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                        <path class="checkmark" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>
                <?php elseif ($search_result === 'not_certified_broker' || $search_result === 'empty_query'): ?>
                    <svg class="warning-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="warning-circle" cx="26" cy="26" r="25" fill="none"/>
                        <path class="warning-line" fill="none" d="M26 13L26 35 M26 38L26 39" stroke-linecap="round"/>
                    </svg>
                <?php elseif ($search_result === 'not_found'): ?>
                    <svg class="error-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="error-circle" cx="26" cy="26" r="25" fill="none"/>
                        <path class="error-x-mark" fill="none" d="M16 16 36 36 M36 16 16 36"/>
                    </svg>
                <?php endif; ?>
            </div>
            <h3 class="modal-title" id="modal-title"></h3>
            <p class="modal-text" id="modal-text"></p>
            <button class="close-modal-btn" id="close-modal-btn">Close</button>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const resultModal = document.getElementById('resultModal');
        const modalTitle = document.getElementById('modal-title');
        const modalText = document.getElementById('modal-text');
        const closeModalBtn = document.getElementById('close-modal-btn');

        const successSound = new Audio('../assets/sound/new-notification-07-210334.mp3');
        const errorSound = new Audio('../assets/sound/error-call.mp3'); // Changed to error-call.mp3
        const noticeSound = new Audio('../assets/sound/notice.mp3');

        successSound.preload = 'auto';
        errorSound.preload = 'auto';
        noticeSound.preload = 'auto';

        const searchResult = <?php echo json_encode($search_result); ?>;

        if (searchResult) {
            let title = '';
            let text = '';
            let soundToPlay = null;
            let vibratePattern = null;

            switch (searchResult) {
                case 'certified_broker':
                    title = 'Certified Broker!';
                    text = 'This user is a certified broker.';
                    soundToPlay = successSound;
                    vibratePattern = 200; // Short vibration
                    break;
                case 'not_certified_broker':
                    title = 'Not a Certified Broker.';
                    text = 'This user is not a certified broker.';
                    soundToPlay = noticeSound;
                    vibratePattern = [100, 50, 100]; // Two short vibrations
                    break;
                case 'not_found':
                    title = 'Broker Not Found.';
                    text = 'No user found with the provided credentials.';
                    soundToPlay = errorSound; // Changed to errorSound
                    vibratePattern = [100, 50, 100, 50, 100]; // Three short vibrations
                    break;
                case 'empty_query':
                    title = 'Search Query Empty';
                    text = 'Please enter a username or partner code to search.';
                    soundToPlay = errorSound;
                    vibratePattern = 100;
                    break;
            }

            modalTitle.textContent = title;
            modalText.textContent = text;
            resultModal.classList.add('visible');

            // Play sound and vibrate
            if (soundToPlay) {
                soundToPlay.play().catch(e => console.error("Sound play failed:", e));
            }
            if (vibratePattern && window.navigator && window.navigator.vibrate) {
                navigator.vibrate(vibratePattern);
            }
        }

        closeModalBtn.addEventListener('click', () => {
            resultModal.classList.remove('visible');
        });
    });
</script>

<?php
require_once __DIR__ . '/../assets/template/end-template.php';
?>
