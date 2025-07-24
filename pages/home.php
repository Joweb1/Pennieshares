<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Pennieshares</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: black;
            color: white;
            min-height: 100vh;
            overflow: hidden;
        }
        
        .bg-image {
            background-image: url('/assets/images/home5.png');
            background-size: cover;
            background-position: right;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .overlay{
            width:100vw;
            height: 100vh;
            background:rgba(0,0,0,0.6);
            display: flex;
            flex-direction: column;
        }
        
        /* Header styles */
        .header {
            padding: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .header-left {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .header-right {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .logo-container {
            display: flex;
            align-items: flex-start;
            border-radius:50%;
        }
        
        /* Main content styles */
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            text-align: center;
            padding: 2rem;
            margin-bottom:5rem;
        }
        
        .heading {
            font-size: 3rem;
            font-weight: 700;
            line-height: 1.25;
        }
        
        .subheading {
            font-size: 1.125rem;
            margin-top: 1rem;
            color: #d1d5db;
        }
        
        .cta-button {
            background: rgba(10,80,200,1);
            color: white;
            font-weight: 700;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            margin-top: 0.8 rem;
            border: none;
            cursor: pointer;
            transition: background 0.3s ease;
            font-size: 1rem;
        }
        .cta-b {
            margin-top: 1rem;
        }
        
        .cta-button:hover {
            background: #2563eb;
        }
        
        .disclaimer {
            font-size: 0.875rem;
            margin-top: 0.75rem;
            color: #9ca3af;
        }
        a{
            text-decoration:none;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header {
                padding: 0.75rem;
            }
            
            .heading {
                font-size: 2.25rem;
            }
            
            .subheading {
                font-size: 1rem;
            }
            
            .main-content {
                padding: 1.5rem;
            }
            
            .cta-button {
                padding: 0.75rem 1.25rem;
                font-size: 0.9rem;
            }
        }
        /*
        @media (max-width: 480px) {
            .heading {
                font-size: 1.75rem;
            }
            
            .subheading {
                font-size: 0.9rem;
            }
            
            .header-left,
            .header-right {
                gap: 0.75rem;
            }
            
            .material-icons {
                font-size: 20px;
            }
            
            .cta-button {
                width: 100%;
                max-width: 300px;
            }
        } */
    </style>
</head>
<body>
    <div class="bg-image">
        <div class="overlay">
        <header class="header">
            <div class="header-left">
                <div class="logo-container">
                    <img src="assets/images/logo4.png" alt="Pennieshares Logo" style="height: 28px; width: auto;">
                </div>
            </div>
            <div class="header-right">
                <a href="login" class="cta-button">Sign in</a>
            </div>
        </header>
        <main class="main-content">
            <h1 class="heading">Look first /<br/>Then leap.</h1>
            <p class="subheading">The best trades require research, then commitment.</p>
            <a href="register" class="cta-button cta-b">Get Started Today</a>
            <a href="register" class="cta-button cta-b">Download App</a>
            <p class="disclaimer">Get your license and secure a better future</p>
        </main>
        </div>
    </div>
</body>
</html>
