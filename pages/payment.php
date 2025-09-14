<?php
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
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
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
    <div class="notification" id="notification">
        <i class="fas fa-check-circle"></i>
        <span>Account number copied to clipboard!</span>
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
                    <img alt="Platform Logo" class="profile-picture" src="assets/images/logo.png" />
                </div>
            </div>
            
            <div class="progress-container">
                <div class="step-info">
                    <span class="step-number" id="step-text">Step 1 of 4</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
            </div>
            
            <form method="POST" action="payment" enctype="multipart/form-data" id="payment-form">
                <!-- Step 1: Choose Payment Method -->
                <div class="step" id="step1">
                    <h3 class="section-title">Select Payment Method</h3>
                    <div class="payment-method-selection">
                        <label class="payment-method-option selected">
                            <input type="radio" name="payment_method" value="bank_transfer" checked>
                            <div class="payment-method-option-content">
                                <div class="selected-tag"><i class="fas fa-check"></i> Selected</div>
                                <div class="payment-icon"><i class="fas fa-building"></i></div>
                                <div class="payment-info">
                                    <p class="account-name">Bank Transfer</p>
                                    <span class="account-type">Upload proof of payment</span>
                                </div>
                            </div>
                        </label>
                        <label class="payment-method-option">
                            <input type="radio" name="payment_method" value="paystack">
                            <div class="payment-method-option-content">
                                <div class="selected-tag"><i class="fas fa-check"></i> Selected</div>
                                <div class="payment-icon"><i class="fas fa-credit-card"></i></div>
                                <div class="payment-info">
                                    <p class="account-name">Pay with Paystack</p>
                                    <span class="account-type">Pay with your card</span>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div class="button-group">
                        <button type="button" class="button button-primary" id="next-step1">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- Step 2: Bank Transfer -->
                <div class="step" id="step-bank-transfer">
                    <h3 class="section-title">Amount</h3>
                    <div class="amount-display">₦1,000.00</div>
                    <h3 class="section-title">Payment Method</h3>
                    <div class="payment-method">
                        <div class="payment-icon"><i class="fas fa-building"></i></div>
                        <div class="payment-info">
                            <p class="account-name">Uroh Patience</p>
                            <div class="account-details">
                                <span class="account-number" id="account-number">9135580911</span>
                                <button type="button" class="copy-btn" id="copy-btn" title="Copy account number"><i class="far fa-copy"></i></button>
                            </div>
                            <span class="account-type">Moniepoint MFB • Bank account</span>
                        </div>
                    </div>
                    <div class="button-group">
                        <button type="button" class="button button-secondary" id="prev-step-bank-transfer"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="button" class="button button-primary" id="next-step-bank-transfer">Continue <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- Step 3: Payment Proof -->
                <div class="step" id="step-proof">
                    <h3 class="section-title">Upload Payment Proof</h3>
                    <div class="upload-container">
                        <div class="upload-area" id="upload-area">
                            <div class="upload-text">
                                <h3>Drag & Drop or Browse</h3>
                                <p>Upload a screenshot or receipt of your payment (JPG, PNG)</p>
                            </div>
                            <button type="button" class="button button-secondary" id="browse-btn"><i class="fas fa-folder-open"></i> Browse Files</button>
                            <input type="file" name="file" id="file-input" accept="image/*" style="display: none;">
                        </div>
                        <div class="preview-container" id="preview-container">
                            <div class="preview-title">Uploaded Image Preview</div>
                            <img src="" alt="Payment proof preview" class="image-preview" id="image-preview">
                        </div>
                    </div>
                    <div class="button-group">
                        <button type="button" class="button button-secondary" id="prev-step-proof"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="submit" class="button button-primary" id="submit-proof-btn">Submit Proof <i class="fas fa-check"></i></button>
                    </div>
                </div>

                <!-- Step Paystack -->
                <div class="step" id="step-paystack">
                    <h3 class="section-title">Pay with Paystack</h3>
                    <div class="amount-display">₦1,000.00</div>
                    <div class="confirmation-content">
                        <p>You will be redirected to Paystack to complete your payment.</p>
                        <div class="button-group center-button">
                            <button type="button" class="button button-secondary" id="prev-step-paystack"><i class="fas fa-arrow-left"></i> Back</button>
                            <button type="button" class="button button-primary" id="pay-with-paystack">Pay with Paystack <i class="fas fa-credit-card"></i></button>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Step 3: Confirmation on Success -->
            <div class="step" id="step3">
                <div class="confirmation-content">
                    <div class="confirmation-icon success"><i class="fas fa-check-circle"></i></div>
                    <h2 class="title" style="text-align: center; margin-bottom: 1.5rem;">Payment Submitted!</h2>
                    <p>Your payment is being processed. We'll notify you via email once your license is approved. This usually takes 1-2 business days.</p>
                    <div class="button-group center-button">
                        <a href="logout" class="button button-primary"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>

            <!-- Step 4: Awaiting Approval -->
            <div class="step" id="step4">
                <div class="confirmation-content">
                    <div class="confirmation-icon pending"><i class="fas fa-hourglass-half"></i></div>
                    <h2 class="title" style="text-align: center; margin-bottom: 1.5rem;">Awaiting Approval</h2>
                    <p>Your payment proof has been submitted and is awaiting approval. We'll notify you via email once it's verified.</p>
                    <div class="button-group center-button" style="flex-direction: row; gap: 1rem;">
                        <a href="logout" class="button button-secondary"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        <a href="payment?action=retry" class="button button-primary"><i class="fas fa-redo"></i> Retry Payment</a>
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
            const steps = {
                1: document.getElementById('step1'),
                'bank-transfer': document.getElementById('step-bank-transfer'),
                'proof': document.getElementById('step-proof'),
                'paystack': document.getElementById('step-paystack'),
                3: document.getElementById('step3'),
                4: document.getElementById('step4')
            };
            const nextStep1 = document.getElementById('next-step1');
            const prevStepBankTransfer = document.getElementById('prev-step-bank-transfer');
            const nextStepBankTransfer = document.getElementById('next-step-bank-transfer');
            const prevStepProof = document.getElementById('prev-step-proof');
            const prevStepPaystack = document.getElementById('prev-step-paystack');
            const payWithPaystackBtn = document.getElementById('pay-with-paystack');

            const stepText = document.getElementById('step-text');
            const progressFill = document.getElementById('progress-fill');
            const browseBtn = document.getElementById('browse-btn');
            const fileInput = document.getElementById('file-input');
            const uploadArea = document.getElementById('upload-area');
            const previewContainer = document.getElementById('preview-container');
            const imagePreview = document.getElementById('image-preview');
            const accountNumber = document.getElementById('account-number');
            const copyBtn = document.getElementById('copy-btn');
            const notification = document.getElementById('notification');
            const paymentForm = document.getElementById('payment-form');
            const submitProofBtn = document.getElementById('submit-proof-btn');

            let initialStep = <?php echo $initialStep; ?>;
            let currentStep = initialStep > 2 ? initialStep : 1;

            const paymentMethodOptions = document.querySelectorAll('.payment-method-option');

            paymentMethodOptions.forEach(option => {
                option.addEventListener('click', () => {
                    paymentMethodOptions.forEach(o => o.classList.remove('selected'));
                    option.classList.add('selected');
                });
            });

            function updateProgress() {
                let progressPercent = 25;
                if (currentStep === 'bank-transfer' || currentStep === 'paystack') {stepText.textContent = `Step 2 of 4`; progressPercent = 50;}
                if (currentStep === 'proof') {progressPercent = 75; stepText.textContent = `Step 3 of 4`;}
                if (currentStep >= 3){
                    progressPercent = 100; stepText.textContent = `Step 4 of 4`;
                }
                if(currentStep < 2){
                    progressPercent = 10; stepText.textContent = `Step 1 of 4`;
                }
                progressFill.style.width = `${progressPercent}%`;
            }

            function goToStep(step) {
                Object.values(steps).forEach(s => s.classList.remove('active'));
                if (steps[step]) {
                    steps[step].classList.add('active');
                    currentStep = step;
                    updateProgress();
                }
            }

            nextStep1.addEventListener('click', () => {
                const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
                if (selectedPaymentMethod === 'bank_transfer') {
                    goToStep('bank-transfer');
                } else {
                    goToStep('paystack');
                }
            });

            prevStepBankTransfer.addEventListener('click', () => goToStep(1));
            nextStepBankTransfer.addEventListener('click', () => goToStep('proof'));
            prevStepProof.addEventListener('click', () => goToStep('bank-transfer'));
            prevStepPaystack.addEventListener('click', () => goToStep(1));

            payWithPaystackBtn.addEventListener('click', () => {
                // Redirect to Paystack payment page
                window.location.href = 'paystack_payment';
            });


            browseBtn.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', handleFiles);
            uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.classList.add('active'); });
            uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('active'));
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('active');
                fileInput.files = e.dataTransfer.files;
                handleFiles();
            });

            function handleFiles() {
                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    if (file.type.match('image.*')) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            imagePreview.src = e.target.result;
                            previewContainer.classList.add('active');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        alert('Please select an image file (JPG, PNG).');
                    }
                }
            }

            paymentForm.addEventListener('submit', (e) => {
                if (currentStep === 'proof' && fileInput.files.length === 0) {
                    e.preventDefault();
                    alert('Please upload a payment proof before submitting.');
                }
            });

            copyBtn.addEventListener('click', function() {
                navigator.clipboard.writeText(accountNumber.textContent).then(() => {
                    notification.classList.add('show');
                    setTimeout(() => notification.classList.remove('show'), 3000);
                });
            });

            // Initial setup
            goToStep(currentStep);
        });
    </script>
</body>
</html>
