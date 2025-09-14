<?php
require_once __DIR__ . '/../src/functions.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0c7ff2;
            --primary-light: #e0f0ff;
            --primary-dark: #0d47a1;
            --error-color: #ef4444;
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
            --error-color: #f87171;
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
            color: var(--error-color);
            margin-bottom: 1.5rem;
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
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

        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
    </style>
</head>
<body>
    <div class="confirmation-card">
        <div class="confirmation-icon">
            <i class="fas fa-times-circle"></i>
        </div>
        <h1 class="confirmation-title">Payment Failed</h1>
        <p class="confirmation-message">
            Unfortunately, we were unable to process your payment. Please try again.
        </p>
        <a href="wallet" class="button button-primary">
            <i class="fas fa-wallet"></i> Go to Wallet
        </a>
    </div>
</body>
</html>