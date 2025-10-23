<?php
require_once __DIR__ . '/../src/functions.php';
check_auth();
$user = $_SESSION['user'];
$is_verified = ($user['status'] == 2);
$user_email = $user['email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accessing Your Office...</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0d3c8a;
            --secondary-color: #00aaff;
            --success-color: #28a745;
            --error-color: #dc3545;
            --background-color: #f0f2f5;
            --text-color: #333;
            --container-bg: #ffffff;
        }

        html[data-theme='dark'] {
            --primary-color: #60a5fa;
            --secondary-color: #60a5fa;
            --success-color: #4ade80;
            --error-color: #f87171;
            --background-color: #0d141c;
            --text-color: #f9fafb;
            --container-bg: #111827;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--background-color);
            color: var(--text-color);
            overflow: hidden;
            transition: background-color 0.3s, color 0.3s;
        }

        .loading-container {
            text-align: center;
            background: var(--container-bg);
            padding: 50px;
            border-radius: 25px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            width: 60%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
            transform: scale(0.95);
            opacity: 0;
            animation: fadeInScale 0.8s cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
            transition: background-color 0.3s;
        }

        @keyframes fadeInScale {
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .logo {
            width: 90px;
            height: auto;
            margin: 0 auto 20px;
            text-align:center;
            animation: bounceIn 1s ease-out;
        }

        @keyframes bounceIn {
            0%, 20%, 40%, 60%, 80%, 100% {
                transition-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);
            }
            0% { opacity: 0; transform: scale(0.3); }
            20% { transform: scale(1.1); }
            40% { transform: scale(0.9); }
            60% { opacity: 1; transform: scale(1.03); }
            80% { transform: scale(0.97); }
            100% { opacity: 1; transform: scale(1); }
        }

        .loading-title {
            font-size: 1.8em;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 30px;
            text-align:center;
        }

        .status-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .status-item {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 20px;
            font-size: 1.1em;
            opacity: 0;
            transform: translateX(-30px);
            animation: slideInItem 0.6s forwards;
            animation-delay: var(--delay);
        }

        @keyframes slideInItem {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .status-item .icon {
            width: 30px;
            height: 30px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .status-item .icon i {
            font-size: 1.5em;
            color: var(--secondary-color);
            transition: all 0.3s ease;
        }

        .status-item.completed .icon i {
            color: var(--success-color);
            animation: iconPop 0.5s;
        }
        
        .status-item.failed .icon i {
            color: var(--error-color);
            animation: iconPop 0.5s;
        }

        @keyframes iconPop {
            0% { transform: scale(1); }
            50% { transform: scale(1.4); }
            100% { transform: scale(1); }
        }

        .status-item .text {
            transition: color 0.3s ease;
        }

        .status-item.completed .text {
            color: var(--text-color);
            text-align:left;
            /* text-decoration: line-through; */
        }
        
        .status-item.failed .text {
            color: var(--text-color);
            text-align:left;
            /* text-decoration: line-through; */
        }

        .progress-bar {
            height: 8px;
            width: 0%;
            background: linear-gradient(90deg, var(--secondary-color), var(--primary-color));
            border-radius: 4px;
            margin-top: 30px;
            transition: width 1s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .confirmation-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--container-bg);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transform: scale(1.2);
            transition: all 0.5s ease;
        }

        .confirmation-overlay.visible {
            opacity: 1;
            visibility: visible;
            transform: scale(1);
        }

        .confirmation-icon {
            font-size: 6em;
            color: var(--success-color);
            animation: successPulse 1.5s infinite;
        }
        
        .denied-icon {
            font-size: 6em;
            color: var(--error-color);
            animation: deniedPulse 1.5s infinite;
        }

        @keyframes successPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        @keyframes deniedPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .confirmation-text {
            font-size: 1.5em;
            color: var(--success-color);
            margin-top: 20px;
            font-weight: 600;
        }
        
         .denied-text {
            font-size: 1.5em;
            color: var(--error-color);
            margin-top: 20px;
            font-weight: 600;
        }

    </style>
</head>
<body>
    <div style="position: fixed; top: 10px; width: 100%; text-align: center; font-weight: bold; font-size: 1.2em; color: var(--primary-color); z-index: 100;"></div>
    <div class="loading-container">
        <img src="/assets/images/logo.png" alt="Logo" class="logo">
        <h1 class="loading-title">Accessing Your Office</h1>
        <ul class="status-list">
            <li class="status-item" style="--delay: 0.5s;">
                <span class="icon"><i class="fas fa-cogs fa-spin"></i></span>
                <span class="text">Initializing Secure Connection...</span>
            </li>
            <li class="status-item" style="--delay: 1.5s;">
                <span class="icon"><i class="fas fa-shield-alt fa-beat"></i></span>
                <span class="text">Authenticating Credentials...</span>
            </li>
            <li class="status-item" style="--delay: 2.5s;">
                <span class="icon"><i class="fas fa-user-check fa-fade"></i></span>
                <span class="text">Verifying Access Level...</span>
            </li>
        </ul>
        <div class="progress-bar"></div>
        <div class="confirmation-overlay">
            <i class="fas fa-check-circle confirmation-icon"></i>
            <p class="confirmation-text">Access Granted!</p>
        </div>
        <div class="confirmation-overlay" id="denied-overlay">
            <i class="fas fa-times-circle denied-icon"></i>
            <p class="denied-text">Access Denied!</p>
        </div>
    </div>

    <audio id="confirmation-sound" src="/assets/sound/new-notification-07-210334.mp3" preload="auto"></audio>
    <audio id="denied-sound" src="/assets/sound/error-call.mp3" preload="auto"></audio>

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

        document.addEventListener('DOMContentLoaded', () => {
            const isVerified = <?php echo json_encode($is_verified); ?>;
            const userEmail = <?php echo json_encode($user_email); ?>;
            const statusItems = document.querySelectorAll('.status-item');
            const progressBar = document.querySelector('.progress-bar');
            const confirmationOverlay = document.querySelector('.confirmation-overlay:not(#denied-overlay)');
            const deniedOverlay = document.getElementById('denied-overlay');
            const confirmationSound = document.getElementById('confirmation-sound');
            const deniedSound = document.getElementById('denied-sound');

            const timings = [1500, 1500, 1500];
            let completedSteps = 0;

            const runStep = (index) => {
                if (index >= statusItems.length) {
                    if (isVerified) {
                        showConfirmation();
                    } else {
                        showDenied();
                    }
                    return;
                }

                setTimeout(() => {
                    const item = statusItems[index];
                    const icon = item.querySelector('i');
                    
                    if (index === 2 && !isVerified) {
                         item.classList.add('failed');
                         icon.className = 'fas fa-times-circle';
                    } else {
                        item.classList.add('completed');
                        icon.className = 'fas fa-check-circle';
                    }


                    completedSteps++;
                    progressBar.style.width = `${(completedSteps / statusItems.length) * 100}%`;
                    
                    runStep(index + 1);
                }, timings[index]);
            };

            const showConfirmation = () => {
                confirmationOverlay.classList.add('visible');
                confirmationSound.play().catch(e => console.error("Audio play failed: ", e));
                if ('vibrate' in navigator) {
                    navigator.vibrate([100, 50, 100]);
                }
                setTimeout(() => {
                    if (userEmail === 'codewithmobile00@gmail.com') {
                        window.location.href = 'learning';
                    } else {
                        window.location.href = 'wallet';
                    }
                }, 1500);
            };

            const showDenied = () => {
                deniedOverlay.classList.add('visible');
                deniedSound.play().catch(e => console.error("Audio play failed: ", e));
                if ('vibrate' in navigator) {
                    navigator.vibrate([100, 50, 100, 50, 100]);
                }
                setTimeout(() => {
                    window.location.href = 'payment';
                }, 2000);
            };

            runStep(0);
        });
    </script>
</body>
</html>
