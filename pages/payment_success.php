<?php
require_once __DIR__ . '/../src/functions.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0c7ff2;
            --primary-light: #e0f0ff;
            --primary-dark: #0d47a1;
            --success-color: #4caf50;
            --body-bg: #f8fafc;
            --card-bg: #ffffff;
            --text-dark: #0d141c;
            --text-medium: #4a5568;
            --border-color: #e2e8f0;
        }

        html[data-theme='dark'] {
            --primary-color: #3b82f6;
            --primary-light: #1e3a8a;
            --primary-dark: #60a5fa;
            --success-color: #4ade80;
            --body-bg: #0d141c;
            --card-bg: #111827;
            --text-dark: #f9fafb;
            --text-medium: #9ca3af;
            --border-color: #374151;
        }

        body {
            background-color: var(--body-bg);
            font-family: 'Inter', 'Noto Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        .confirmation-card {
            background-color: var(--card-bg);
            border-radius: 1rem;
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            animation: fadeIn 0.5s ease-out;
        }

        .confirmation-icon {
            font-size: 5rem;
            color: var(--success-color);
            margin-bottom: 1.5rem;
            animation: bounceIn 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .confirmation-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .confirmation-message {
            font-size: 1.1rem;
            color: var(--text-medium);
            margin-bottom: 2rem;
            line-height: 1.6;
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
            text-decoration: none;
        }

        .button-primary {
            background: linear-gradient(135deg, var(--primary-dark), var(--text-dark));
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(25, 118, 210, 0.3);
        }

        .button-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(25, 118, 210, 0.4);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes bounceIn {
            0% { transform: scale(0.5); opacity: 0; }
            80% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }

        .verification-container {
            margin-top: 2rem;
            padding: 1.5rem;
            background-color: var(--primary-light);
            border-radius: 0.75rem;
            border: 1px solid var(--primary-color);
        }

        .verification-message {
            font-size: 1rem;
            color: var(--primary-dark);
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="confirmation-card">
        <div class="confirmation-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1 class="confirmation-title">Payment Successful!</h1>
        <p class="confirmation-message">
            Congratulations! Your payment has been successfully processed. Your account is now being verified.
        </p>

        <a href="wallet" class="button button-primary" id="go-to-wallet-btn" style="display:none;">
            <i class="fas fa-wallet"></i> Go to Wallet
        </a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                window.location.href = 'logout';
            }, 3000); // 3000 milliseconds = 3 seconds
        });
    </script>
</body>
</html>
