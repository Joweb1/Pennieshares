<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

require_once __DIR__ . '/../src/functions.php';
// check_auth();

$user = $_SESSION['user'];

if ($user['status'] == 2) {
// Redirect to payment page
    header("Location: wallet");
    exit;
}        
// Handle retry action
if (isset($_GET['action']) && $_GET['action'] === 'retry') {
    deletePaymentProofForUser($pdo, $user['id']);
    header("Location: payment"); // Redirect to the clean payment page
    exit;
}

// Check for existing, pending payment proof
$stmt = $pdo->prepare("SELECT id FROM payment_proofs WHERE user_id = ? AND status = 1");
$stmt->execute([$user['id']]);
$proofExists = $stmt->fetch() ? true : false;

$error = '';
$success = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $fileExtension = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
        $target_file = $target_dir . uniqid('proof_' . $user['id'] . '_', true) . '.' . $fileExtension;
        
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            try {
                $stmt = $pdo->prepare("SELECT file_path FROM payment_proofs WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $existingProof = $stmt->fetch();
                
                if ($existingProof && file_exists($existingProof['file_path'])) {
                    unlink($existingProof['file_path']);
                }
                
                $stmt = $pdo->prepare("
                INSERT INTO payment_proofs (user_id, file_path, status)
                VALUES (:user_id, :file_path, 1)
                ON CONFLICT(user_id) DO UPDATE SET
                file_path = excluded.file_path,
                uploaded_at = CURRENT_TIMESTAMP,
                status = 1
                ");
                
                $stmt->execute([
                    ':user_id' => $user['id'],
                    ':file_path' => $target_file
                ]);
                
                header("Location: payment?upload_success=true");
                exit;
                
            } catch (PDOException $e) {
                $error = "Error saving payment proof: " . $e->getMessage();
            }
        } else {
            $error = "Error moving uploaded file.";
        }
    } else {
        $error = "Error uploading file. Code: " . $_FILES['file']['error'];
    }
}

$initialStep = 1;
if (isset($_GET['upload_success'])) {
    $initialStep = 3;
} elseif ($proofExists) {
    $initialStep = 4;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Payment Process</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?display=swap&family=Inter:wght@400;500;600;700&family=Noto+Sans:wght@400;500;700;900">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <style>
        :root {
            --primary-color: #0c7ff2;
            --primary-light: #e0f0ff;
            --primary-dark: #0d47a1;
            --secondary-color: #4caf50;
            --warning-color: #ff9800;
            --header-bg: #ffffff;
            --body-bg: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-dark: #0d141c;
            --text-medium: #4a5568;
            --text-light: #718096;
            --progress-bg: #e2e8f0;
            --progress-active: #0c7ff2;
            --button-primary: #0c7ff2;
            --button-primary-hover: #0d47a1;
            --button-secondary: #e2e8f0;
            --button-secondary-hover: #d1d9e6;
            --button-text: #ffffff;
            --input-bg: #f1f5f9;
            --placeholder-color: #94a3b8;
            --upload-border: #cbd5e1;
            --success-color: #4caf50;
            --error-color: #ef4444;
            --loading-overlay-bg: rgba(255, 255, 255, 0.2); /* Whitish glassmorphism */
            --loading-text-color: #0d141c; /* Dark text for light mode */
            --loading-icon-color: #0d47a1; /* Darker blue for icon in light mode */
            --loading-paragraph-color: red;
        }

        html[data-theme='dark'] {
            --primary-color: #3b82f6;
            --primary-light: #1e3a8a;
            --primary-dark: #60a5fa;
            --secondary-color: #4ade80;
            --warning-color: #f59e0b;
            --header-bg: #111827;
            --body-bg: #0d141c;
            --card-bg: #111827;
            --border-color: #374151;
            --text-dark: #f9fafb;
            --text-medium: #9ca3af;
            --text-light: #6b7280;
            --progress-bg: #374151;
            --progress-active: #3b82f6;
            --button-primary: #3b82f6;
            --button-primary-hover: #60a5fa;
            --button-secondary: #1f2937;
            --button-secondary-hover: #374151;
            --button-text: #ffffff;
            --input-bg: #1f2937;
            --placeholder-color: #6b7280;
            --upload-border: #4b5563;
            --success-color: #4ade80;
            --error-color: #f87171;
            --loading-overlay-bg: rgba(0, 0, 0, 0.4); /* Darkish glassmorphism */
            --loading-text-color: #f9fafb; /* Light text for dark mode */
            --loading-icon-color: #ffc107; /* Yellowish orange for icon in dark mode */
            --loading-paragraph-color: #ff5252;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Noto Sans', sans-serif;
        }

        body {
            background-color: var(--body-bg);
            min-height: 100vh;
            color: var(--text-dark);
            transition: background-color 0.3s, color 0.3s;
        }
        
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--loading-overlay-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            color: white;
            text-align: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        #loading-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        #loading-overlay .loading-content {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px 0 rgba( 31, 38, 135, 0.37 );
            border: 1px solid rgba( 255, 255, 255, 0.18 );
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            margin: 0 20px; /* Reduced horizontal margin */
        }

        #loading-overlay p {
            margin-top: 1rem;
            font-size: 0.84rem; /* 30% reduction from 1.2rem */
            color: var(--loading-paragraph-color);
        }

        .modern-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .loading-icon {
            font-size: 2.1rem; /* 30% reduction from 3rem */
            color: var(--loading-icon-color);
            margin-bottom: 10px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        .container {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
            padding: 1.25rem;
        }

        .breadcrumb {
            display: flex;
            gap: 0.5rem;
            padding: 1rem 1rem 0.5rem;
            font-size: 1rem;
            font-weight: 500;
        }

        .breadcrumb a {
            color: #4b739b;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb a:hover {
            color: var(--primary-color);
        }

        .breadcrumb span {
            color: var(--text-dark);
        }

        .title {
            font-size: 1.75rem;
            font-weight: 700;
            text-align: center;
            margin: 0.5rem 0 1.5rem;
            background: linear-gradient(45deg, var(--primary-dark), var(--text-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
        }

        .title::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: var(--primary-dark);
            border-radius: 2px;
        }
        
        .profile-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 10% 0;
            position: relative;
        }
        
        .profile-picture-wrapper {
            position: relative;
            z-index: 1;
        }
        
        .profile-picture {
            width: 7rem;
            height: 7rem;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        .progress-container {
            padding: 1rem;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }

        .step-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .step-number {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-medium);
        }

        .progress-bar {
            height: 8px;
            background-color: var(--progress-bg);
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
            border-radius: 4px;
            transition: width 0.4s ease;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            padding: 0.2rem;
            margin: 1rem 0;
            color: var(--text-medium);
            position: relative;
        }

        .payment-method {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem;
            background-color: var(--card-bg);
            border-radius: 0.75rem;
            margin: 0.75rem 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .payment-icon {
            width: 3.5rem;
            height: 3.5rem;
            background-color: var(--button-secondary);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: var(--primary-dark);
            font-size: 1.5rem;
        }

        .payment-info {
            flex-grow: 1;
        }

        .payment-info p {
            margin: 0.25rem 0;
        }

        .account-details {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
        }

        .account-number {
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: 1px;
        }

        .copy-btn {
            background: none;
            border: none;
            color: var(--primary-dark);
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }

        .copy-btn:hover {
            background-color: rgba(25, 118, 210, 0.1);
            color: var(--primary-dark);
        }

        .account-name {
            font-weight: 500;
            color: var(--text-medium);
        }

        .account-type {
            font-size: 0.875rem;
            color: var(--text-light);
            background-color: rgba(25, 118, 210, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            display: inline-block;
        }

        .amount-display {
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            color: var(--success-color);
            margin: 1rem 0;
            padding: 1.5rem;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(76, 175, 80, 0.2);
        }

        .upload-container {
            padding: 1rem;
            margin: 1rem 0;
        }

        .upload-area {
            border: 2px dashed var(--upload-border);
            border-radius: 1rem;
            padding: 2.5rem 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 1.5rem;
            background-color: var(--card-bg);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-height: 220px;
        }

        .upload-area.active {
            border-color: var(--primary-color);
            background-color: rgba(25, 118, 210, 0.05);
        }

        .upload-area:hover {
            border-color: var(--primary-color);
        }

        .upload-text h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary-dark);
        }

        .upload-text p {
            color: var(--text-medium);
            font-size: 0.95rem;
            max-width: 320px;
            margin: 0 auto;
        }

        .preview-container {
            display: none;
            width: 100%;
            margin-top: 1rem;
            text-align: center;
        }

        .preview-container.active {
            display: block;
        }

        .preview-title {
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--primary-dark);
        }

        .image-preview {
            max-width: 100%;
            max-height: 300px;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            margin: 0 auto;
            display: block;
        }

        .button {
            height: 3rem;
            padding: 0 1.75rem;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .button i {
            font-size: 1.1rem;
        }

        .button-primary {
            background: linear-gradient(135deg, var(--primary-dark), var(--text-dark));
            color: var(--button-text);
            box-shadow: 0 4px 10px rgba(25, 118, 210, 0.3);
        }

        .button-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), #0a3d8f);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(25, 118, 210, 0.4);
        }

        .button-secondary {
            background-color: var(--button-secondary);
            color: var(--text-dark);
        }

        .button-secondary:hover {
            background-color: var(--button-secondary-hover);
            transform: translateY(-2px);
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            padding: 1.5rem 1rem 0.5rem;
            gap: 1rem;
        }

        .step {
            display: none;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            overflow: hidden;
            padding: 1.5rem;
            margin-top: 1rem;
        }

        .step.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        .confirmation-content {
            text-align: center;
            padding: 2rem 1rem;
        }

        .confirmation-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: bounce 1s ease;
        }
        .confirmation-icon.success { color: var(--success-color); }
        .confirmation-icon.pending { color: var(--warning-color); }

        .confirmation-content p {
            margin: 1rem 0;
            font-size: 1.1rem;
            color: var(--text-medium);
            line-height: 1.6;
            max-width: 500px;
            margin: 0 auto 2rem;
        }

        .center-button {
            justify-content: center;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            background-color: var(--success-color);
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateX(120%);
            transition: transform 0.3s ease;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .notification.show {
            transform: translateX(0);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}'''
            40% {transform: translateY(-20px);}
            60% {transform: translateY(-10px);}
        }

        .payment-method-selection {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .payment-method-option {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem;
            background-color: var(--card-bg);
            border-radius: 0.75rem;
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method-option:hover {
            border-color: var(--primary-color);
        }

        .payment-method-option input[type="radio"] {
            display: none;
        }

        .payment-method-option input[type="radio"]:checked + .payment-method-option-content {
            border: 2px solid var(--primary-color);
            box-shadow: 0 0 10px rgba(12, 127, 242, 0.2);
        }

        .payment-method-option-content {
            display: flex;
            align-items: center;
            gap: 1rem;
            width: 100%;
            border: 2px solid transparent;
            border-radius: 0.75rem;
            padding: 1rem;
        }

        .payment-method-option {
            position: relative;
            overflow: hidden;
        }

        .selected-tag {
            position: absolute;
            top: -1px;
            right: -1px;
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-bottom-left-radius: 0.75rem;
            opacity: 0;
            transform: translateY(-100%);
            transition: all 0.3s ease;
        }

        .payment-method-option.selected .selected-tag {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 600px) {
            .container {
                padding: 1rem;
            }
            
            .profile-picture {
                width: 6rem;
                height: 6rem;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div id="loading-overlay">
        <div class="loading-content">
            <i class="fas fa-sync-alt fa-spin loading-icon"></i>
            <h2 style="font-weight: bold; text-align: center; color: var(--loading-text-color); font-size: 1.225rem;">Verifying Payment...</h2>
            <p style="color: var(--loading-paragraph-color);">Please do not leave this page</p>
        </div>
    </div>
    <main>
        <div class="container">
            <div class="breadcrumb">
                <a href="logout">Logout</a> <span>/</span>
                <span>Payment</span>
            </div>
            
            <h1 class="title">Get Licensed</h1>
            
            <div class="profile-header">
                <div class="profile-picture-wrapper">
                    <img alt="Platform Logo" class="profile-picture" src="<?= BASE_URL ?>/assets/images/logo.png" />
                </div>
            </div>
            
            <div class="step active" id="step-paystack">
                <h3 class="section-title">Pay with Paystack</h3>
                <div class="amount-display">₦1,000.00</div>
                <div class="confirmation-content">
                    <p>You will be redirected to Paystack to complete your payment.</p>
                    <div class="button-group center-button">
                        <button type="button" class="button button-primary" id="pay-with-paystack">Pay with Paystack <i class="fas fa-credit-card"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (savedTheme) {
                document.documentElement.setAttribute('data-theme', savedTheme);
            } else if (prefersDark) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();

        document.addEventListener('DOMContentLoaded', function() {
            const payWithPaystackBtn = document.getElementById('pay-with-paystack');
            const loadingOverlay = document.getElementById('loading-overlay');

            payWithPaystackBtn.addEventListener('click', () => {
                payWithPaystack();
            });

            function payWithPaystack() {
                const handler = PaystackPop.setup({
                    key: '<?php echo $_ENV["PAYSTACK_PUBLIC_KEY"]; ?>',
                    email: '<?php echo $user["email"]; ?>',
                    amount: 1000 * 100, // in kobo
                    currency: 'NGN',
                    ref: 'paystack_' + Math.floor((Math.random() * 1000000000) + 1),
                    callback: function(response) {
                        loadingOverlay.classList.add('show');
                        window.location = 'payment_callback.php?reference=' + response.reference;
                    },
                    onClose: function() {
                        // user closed popup
                        alert('Payment was not completed.');
                    }
                });
                handler.openIframe();
            }
        });
    </script>
</body>
</html>
