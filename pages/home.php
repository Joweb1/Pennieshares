<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penniepoint - Coming Soon!</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            overflow:hidden;
        }

        body {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #fff;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .container {
            text-align: center;
            position: relative;
            z-index: 1;
            padding: 2rem 1rem;
        }

        .logo {
            width: 100px;
            margin: 3rem auto;
            margin-bottom:0.5rem;
            animation: pulse 2s ease-in-out infinite;
        }

        .title {
            font-size: 2.5rem;
            background: linear-gradient(45deg, #00ff87, #60efff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            text-shadow: 0 0 30px rgba(96,239,255,0.4);
            animation: shine 3s infinite;
        }

        .countdown {
            display: flex;
            justify-content: center;
            gap: .5rem;
            margin: 1rem 0;
            padding:1rem 0;
        }

        .countdown-item {
            background: rgba(255,255,255,0.1);
            padding: 1rem 0;
            border-radius: 15px;
            min-width: 70px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }

        .countdown-item:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
        }

        .number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #00ff87;
        }

        .label {
            color: #94a3b8;
            text-transform: uppercase;
            font-size: 0.5rem;
            letter-spacing: 2px;
        }

        .newsletter {
            margin: 2rem 0;
            position: relative;
        }

        .newsletter-input {
            padding: 1rem 2rem;
            border: none;
            border-radius: 50px;
            width: 400px;
            max-width: 90%;
            background: rgba(255,255,255,0.1);
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .newsletter-input:focus {
            outline: none;
            box-shadow: 0 0 20px rgba(96,239,255,0.3);
        }

        .subscribe-btn {
            position: absolute;
            right: 5px;
            top: 5px;
            background: linear-gradient(45deg, #00ff87, #60efff);
            color: #0f172a;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .subscribe-btn:hover {
            transform: scale(0.95);
            box-shadow: 0 0 20px rgba(96,239,255,0.4);
        }
        .log-btn {
        font-size:20px;
        letter-spacing:-0.5px;
        line-height:30px;
        right: 5px;
        top: 5px;
        background: linear-gradient(45deg, #00ff87, #60efff);
        color: #0f172a;
        padding: 0.8rem 2rem;
        border: none;
        border-radius: 50px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        }
        
        .log-btn:hover {
        transform: scale(0.95);
        box-shadow: 0 0 20px rgba(96,239,255,0.4);
        }

        .floating-coins {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .coin {
            position: absolute;
            animation: float 6s infinite ease-in-out;
            opacity: 0.3;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes shine {
            0% { background-position: -500px; }
            100% { background-position: 500px; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }

        /* Existing star animations from previous design */
        .stars {
        position: absolute;
        width: 100%;
        height: 100%;
        pointer-events: none;
        }
        
        .star {
        position: absolute;
        background: #fff;
        border-radius: 50%;
        animation: twinkle var(--duration) ease-in-out infinite;
        }
        @keyframes twinkle {
        0%, 100% { opacity: 0.3; transform: scale(0.5); }
        50% { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="floating-coins" id="coinsContainer"></div>
    <div class="stars"></div>

    <div class="container">
        <img src="assets/images/logo.png" class="logo" alt="Penniepoint Logo">
        <h1 class="title">Coming Soon</h1>
        <p class="message">Revolutionizing personal finance management!</p>

        <div class="countdown" id="countdown">
            <div class="countdown-item">
                <div class="number" id="days">00</div>
                <div class="label">Days</div>
            </div>
            <div class="countdown-item">
                <div class="number" id="hours">00</div>
                <div class="label">Hours</div>
            </div>
            <div class="countdown-item">
                <div class="number" id="minutes">00</div>
                <div class="label">Minutes</div>
            </div>
            <div class="countdown-item">
                <div class="number" id="seconds">00</div>
                <div class="label">Seconds</div>
            </div>
        </div>

        <div class="newsletter">
            <input type="email" class="newsletter-input" value=""  placeholder="Enter email for early access...">
            <button class="subscribe-btn">Email us for more info</button>
        </div>
        <a href="login"  class="newsletter" >
        <button class="log-btn" >Join our Partnership Community!!</button>
        </a>
        <p class="message">Join to learn more about Penniepoint.</p>
    </div>

    <script>
        // Countdown Timer
        const launchDate = new Date('2025-08-31T00:00:00').getTime();

        function updateCountdown() {
            const now = new Date().getTime();
            const distance = launchDate - now;

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById('days').innerHTML = days.toString().padStart(2, '0');
            document.getElementById('hours').innerHTML = hours.toString().padStart(2, '0');
            document.getElementById('minutes').innerHTML = minutes.toString().padStart(2, '0');
            document.getElementById('seconds').innerHTML = seconds.toString().padStart(2, '0');

            // Add flip animation
            document.querySelectorAll('.number').forEach(num => {
                num.style.animation = 'flip 0.5s ease';
                setTimeout(() => num.style.animation = '', 500);
            });
        }

        setInterval(updateCountdown, 1000);
        updateCountdown();

        // Create floating coins
        function createCoins() {
            const coinsContainer = document.getElementById('coinsContainer');
            const coins = ['💰', '💎', '📈', '💹'];
            
            for(let i = 0; i < 30; i++) {
                const coin = document.createElement('div');
                coin.className = 'coin';
                coin.textContent = coins[Math.floor(Math.random() * coins.length)];
                coin.style.left = `${Math.random() * 100}%`;
                coin.style.top = `${Math.random() * 100}%`;
                coin.style.fontSize = `${Math.random() * 20 + 20}px`;
                coin.style.animationDelay = `${Math.random() * 5}s`;
                coinsContainer.appendChild(coin);
            }
        }

        // Initialize
        window.onload = () => {
            createStars();
            createCoins();
        }

        // Keep existing stars function
        function createStars() {
        const starsContainer = document.createElement('div');
        starsContainer.className = 'stars';
        
        for(let i = 0; i < 100; i++) {
        const star = document.createElement('div');
        star.className = 'star';
        star.style.left = `${Math.random() * 100}%`;
        star.style.top = `${Math.random() * 100}%`;
        star.style.width = star.style.height = `${Math.random() * 3}px`;
        star.style.setProperty('--duration', `${Math.random() * 3 + 2}s`);
        starsContainer.appendChild(star);
        }
        document.body.appendChild(starsContainer);
        }
        
    </script>
</body>
</html>  
    </script>
</body>
</html>