<?php
require_once __DIR__ . '/../src/functions.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            overflow:hidden;
        }

        body {
            background: linear-gradient(135deg, #f0f9ff, #e6f4ff);
            color: #2d3748;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            padding:20px;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .container {
            text-align: center;
            position: relative;
            z-index: 1;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(31, 117, 203, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .lost-illustration {
            width: 200px;
            animation: float 4s ease-in-out infinite;
            filter: drop-shadow(0 10px 20px rgba(45, 140, 235, 0.15));
        }

        .error-code {
            font-size: 6rem;
            font-weight: 900;
            background: linear-gradient(45deg, #2d8df5, #4a90e2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 1rem 0;
            text-shadow: 0 4px 18px rgba(45, 140, 235, 0.15);
        }

        .message {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: #4a5568;
            max-width: 500px;
            line-height: 1.6;
        }

        .home-btn {
            background: linear-gradient(45deg, #4a90e2, #2d8df5);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(45, 140, 235, 0.2);
        }

        .home-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(45, 140, 235, 0.3);
        }

        .home-btn::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255,255,255,0.15);
            transform: rotate(45deg);
            transition: all 0.5s ease;
        }

        .home-btn:hover::after {
            left: 120%;
        }

        .bubbles {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index:99;
        }

        .bubble {
            position: absolute;
            background: rgba(45, 140, 235, 0.1);
            border-radius: 50%;
            animation: bubbleUp var(--duration) linear infinite;
            z-index:99;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        @keyframes bubbleUp {
            0% { 
                transform: translateY(100vh) scale(0.5); 
                opacity: 0;
            }
            50% { opacity: 0.4; }
            100% { 
                transform: translateY(-100vh) scale(1.2); 
                opacity: 0;
            }
        }

        .social-links {
            position:fixed;
            top: 90vh;
            left: 11%;
            display: flex;
            gap: 1.5rem;
            z-index:10;
        }

        .social-links a {
            color: #4a5568;
            font-size: 1.5rem;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.9);
            padding: 0.8rem;
            border-radius: 50%;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(45, 140, 235, 0.1);
        }

        .social-links a:hover {
            transform: translateY(-5px);
            color: #2d8df5;
            box-shadow: 0 4px 15px rgba(45, 140, 235, 0.15);
        }

        
    </style>
</head>
<body>
    <div class="container">
        <img src="https://cdn-icons-png.flaticon.com/512/3244/3244370.png" class="lost-illustration" alt="Astronaut">
        <h1 class="error-code">404</h1>
        <p class="message">Oops! <?php if (isset($_SESSION['user'])): ?><?= $_SESSION['user']['username'] ?> <?php endif; ?>The page you're looking for seems to have drifted into deep space.</p>
        <a href="/" class="home-btn">
            <i class="fas fa-compass"></i> Navigate Home
        </a>
    </div>

    <div class="social-links">
        <a href="#"><i class="fab fa-github"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-linkedin"></i></a>
    </div>
    <script type="text/javascript">
    function createBubbles() {
    const bubblesContainer = document.createElement('div');
    bubblesContainer.className = 'bubbles';
    
    for(let i = 0; i < 20; i++) {
    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    bubble.style.left = `${Math.random() * 100}%`;
    bubble.style.width = bubble.style.height = `${Math.random() * 40 + 20}px`;
    bubble.style.setProperty('--duration', `${Math.random() * 8 + 4}s`);
    bubble.style.animationDelay = `${Math.random() * 5}s`;
    bubblesContainer.appendChild(bubble);
    }
    document.body.appendChild(bubblesContainer);
    }
    
    window.onload = createBubbles;
    </script>
</body>
</html></body>
</html>