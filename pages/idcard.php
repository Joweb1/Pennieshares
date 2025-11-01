<?php
require_once __DIR__ . '/../src/functions.php';
check_auth();

// Get current user data
$user = $_SESSION['user'];

if($user['status'] != 2){
	header("Location: dashboard");
	exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - <?= htmlspecialchars($user['username']) ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f0f2f5;
            --text-primary: #333;
            --text-secondary: #666;
            --accent-color: #0d3c8a;
            --accent-gradient: linear-gradient(135deg, rgba(12,15,122,1) 0%, rgba(212,115,122,1) 100%);

            /* ID Card specific colors that do not change with theme */
            --card-bg: white;
            --card-text-primary: #333;
            --card-text-secondary: #666;
            --card-accent-color: #0d3c8a;
        }

        html[data-theme="dark"] {
            --bg-primary: #111418;
            --bg-secondary: #1b2127;
            --text-primary: #f0f4f8;
            --text-secondary: #a0b3c6;
            --accent-color: #1d90f5;
            --accent-gradient: linear-gradient(135deg, #1d90f5 0%, #ff6b6b 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            padding: 20px;
            color: var(--text-primary);
        }

        .back{
            background: transparent; /* Transparent background */
            color: var(--text-primary); /* Dark color for the icon */
            border-radius:10px;
            font-size:20px;
            position:absolute;
            z-index:2;
            top:5px;
            font-weight:600;
            left:10px;
            padding:10px 10px;
            text-align:center;
            box-shadow: none; /* Remove shadow for transparent background */
        }
        .back:hover {
            background: rgba(0, 0, 0, 0.1);
        }
        .back svg {
            fill: currentColor; /* Ensure SVG inherits the color */
        }
        a{
            color:inherit;
            text-decoration:none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
        }

        .form-section {
            background: var(--accent-gradient);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(30, 60, 114, 0.2);
            color: white;
        }
        
        .form-section.head{
        	background:transparent;
        	margin:auto;
        	margin-bottom:45px;
        }
		.form-section h2 {
			padding-bottom:25px;
		}
        .form-section.head h2 {
            font-size: 1.5rem;
            text-align: center;
            background: linear-gradient(45deg, rgba(255,55,100,1), rgba(255,185,52,1));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            padding:0px;
            
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #f8f9fa;
            font-size: 14px;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 15px 20px;
            border: none;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            background: white;
            border-color: #ff6b6b;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
        }

        .photo-upload {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 30px;
            border-radius: 20px;
            border: 3px dashed rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.1);
        }

        .photo-upload:hover {
            border-color: #ff6b6b;
            transform: scale(1.05);
            background: rgba(255, 107, 107, 0.1);
        }

        .photo-upload input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .photo-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 17px;
        }

        .photo-placeholder {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 16px;
            font-weight: 600;
        }

        .card-section {
            perspective: 1000px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .card-container {
            position: relative;
            width: 54mm;
            height: 85mm;
            margin-bottom: 30px;
            transform-style: preserve-3d;
            transition: transform 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .card-container.flipped {
            transform: rotateY(180deg);
        }

        .card-face {
            position: absolute;
            width: 100%;
            height: 100%;
            /*backface-visibility: hidden;*/
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            background: var(--card-bg);
        }

        .card-back {
            transform: rotateY(180deg);
        }
        .card-container.flipped .card-front {
        	display:none;
        }

        .card-front, .card-back {
            position: relative;
            padding: 0px;
            display: flex;
            flex-direction: column;
        }

        /* Decorative curved elements */
        .card-front::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            border-radius: 50%;
            opacity: 0.1;
        }

        .card-front::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 100px;
            height: 100px;
            background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
            border-radius: 50%;
            opacity: 0.15;
        }

        .card-back::before {
            content: '';
            position: absolute;
            top: -40px;
            left: -40px;
            width: 120px;
            height: 120px;
            background: linear-gradient(225deg, #2a5298, #1e3c72);
            border-radius: 50%;
            opacity: 0.1;
        }

        /* Curved design lines */
        .curve-line {
            position: absolute;
            width: 200px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--card-accent-color), transparent);
            border-radius: 2px;
            opacity: 0.3;
        }

        .curve-line-1 {
            top: 80px;
            right: -50px;
            transform: rotate(25deg);
        }

        .curve-line-2 {
            bottom: 120px;
            left: -50px;
            transform: rotate(-15deg);
        }

        .card-header {
            text-align: center;
            width: 100%;
            margin-top:15px;
            margin-bottom: 20px;
            margin-left:15px;
            z-index: 10;
            position: relative;
            display:flex;
            justify-content:flex-start;
            align-items:flex-end;
        }
        
        .card-header svg{
        	width:25px;
        	height:25px;
        	display:block;
        	fill:var(--card-accent-color);
        	border-radius:50%;
        }

        .company-logo {
            color: var(--card-accent-color);
            padding: 2px 2px;
            font-weight: 800;
            font-size: 14px;
        }

        .card-body {
            text-align: center;
            z-index: 10;
            position: relative;
            flex-grow: 1;
        }
        
        .photo-c {
        	width:20mm;
        	height:20mm;
        	display:flex;
        	justify-content:center;
        	align-items:center;
        	border-radius: 50%;
        	border: 2px solid var(--card-accent-color);
        	margin: 0 auto 4px;
        	overflow:hidden;
        }
        .employee-photo {
            width: 20mm;
            height:20mm;
            position:relative;
            border-radius: 50%;
            background: var(--bg-secondary);
            display:block;
        }
        .employee-info {
        	margin-bottom: 25px;
        }

        .employee-info h3 {
            font-size: 17px;
            margin-bottom: 1px;
            font-weight: 700;
            color: var(--card-accent-color);
        }

        .employee-info p {
            font-size: 12px;
            color: var(--card-text-secondary);
            margin-bottom: 10px;
        }

        .qr-section {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: auto;
        }

        .qr-code {
        	position:relative;
            width: 60px;
            height: 60px;
            background: var(--card-accent-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 10px;
            font-weight: bold;
        }
        .qr-code img{
        	width:100%;
        }

        .card-back {
            justify-content: space-between;
            padding:15px;
            display:none;
        }
        .card-container.flipped .card-back{
        	display:flex;
        }

        .terms-section h4 {
            font-size: 10px;
            margin-bottom: 2px;
            color: var(--card-accent-color);
            font-weight: 700;
        }

        .terms-section p {
            font-size: 8.5px;
            line-height: 1.4;
            color: var(--card-text-secondary);
            margin-bottom: 10px;
        }

        .contact-info {
            background: linear-gradient(135deg, rgba(30, 60, 114, 0.08), rgba(255, 107, 107, 0.08));
            padding: 5px;
            border-radius: 12px;
            margin-bottom: 10px;
        }

        .contact-info h4 {
            font-size: 10px;
            margin-bottom: 2px;
            color: var(--card-accent-color);
            font-weight: 700;
        }

        .contact-info p {
            font-size: 8.5px;
            margin-bottom: 2px;
            color: var(--card-text-primary);
        }
        
        .comp h4{
        	font-size:9px;
        }
        .comp p{
        	font-size:8px;
        }

        .barcode {
            font-weight:300;
            font-size:20px;
            color: var(--card-accent-color);
            letter-spacing:-2.5px;
            margin:0px auto;
        }

        .controls {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .flip-btn, .download-btn {
            padding: 15px 30px;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 14px;
        }

        .flip-btn {
            background: linear-gradient(45deg, rgba(255,55,100,1), rgba(255,145,22,1));
            color: white;
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.3);
        }

        .download-btn {
            background: var(--accent-gradient);
            color: white;
            box-shadow: 0 8px 20px rgba(30, 60, 114, 0.3);
        }

        .flip-btn:hover, .download-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .flip-btn:active, .download-btn:active {
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .card-container {
                width: 54mm;
                height: 85mm;
            }
        }
    </style>
</head>
<body>
    <div class="back" ><a href="/profile_view" ><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 256 256"><path d="M160,220a12,12,0,0,1-8.49-3.51L80,136.49a12,12,0,0,1,0-17L151.51,39.51a12,12,0,0,1,17,17L97,128l71.52,71.51A12,12,0,0,1,160,220Z"></path></svg></a></div>
    <div class="container">
        <div class="card-section">
        	<div class="form-section head">
        	<h2>Partnering ID Card</h2>
        	</div>
            <div class="card-container" id="card-container">
                <div class="card-face card-front">
                    <div class="curve-line curve-line-1"></div>
                    <div class="curve-line curve-line-2"></div>
                    
                    <div class="card-header">
                    	<svg version="1.0" xmlns="http://www.w3.org/2000/svg"  width="300pt" height="300pt" viewBox="0 0 300 300"  preserveAspectRatio="xMidYMid meet">  <g transform="translate(0.000000,300.000000) scale(0.100000,-0.100000)" stroke="none"> <path d="M1321 2990 c-735 -93 -1286 -690 -1318 -1430 -25 -580 303 -1134 822 -1390 135 -66 233 -100 380 -132 144 -31 436 -31 580 0 321 70 585 215 795 438 284 302 426 687 406 1104 -37 780 -667 1394 -1451 1415 -77 2 -173 0 -214 -5z m629 -485 c136 -23 236 -70 311 -147 77 -80 109 -171 109 -316 0 -171 -48 -329 -139 -459 -88 -125 -181 -175 -336 -181 -124 -4 -241 23 -412 97 -113 48 -114 48 -125 27 -7 -11 -52 -218 -100 -459 -48 -241 -90 -448 -93 -458 -20 -62 -133 -139 -205 -139 -84 0 -179 74 -180 140 -1 14 79 425 176 915 122 613 175 899 171 918 -4 15 -19 33 -33 39 -37 17 -24 32 32 38 106 11 738 -1 824 -15z m-489 -1035 c98 -41 226 -77 302 -87 l62 -7 -115 -12 c-63 -7 -170 -13 -237 -13 l-123 -1 0 24 c0 35 22 126 30 126 4 0 40 -14 81 -30z"/> <path d="M1485 2199 c-27 -116 -115 -578 -115 -606 l0 -36 113 6 c244 14 352 68 422 210 72 147 70 306 -4 377 -52 50 -96 61 -263 67 l-147 5 -6 -23z"/> </g> </svg> 
                    	<div class="company-logo">Pennieshares</div>
                    </div>
                    
                    <div class="card-body">
                    	<div class="photo-c" >
                        	<img id="card-photo" class="employee-photo" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='50' fill='%23f0f0f0'/%3E%3Ctext x='50' y='55' text-anchor='middle' font-size='35' fill='%23666'%3E👤%3C/text%3E%3C/svg%3E" alt="Employee Photo">
                        </div>
                        
                        <div class="employee-info">
                            <h3><?= htmlspecialchars($user['fullname']) ?></h3>
                            <p>Licensed Stockholder</p>
                            <p><strong>Partnering Code:</strong> <?= htmlspecialchars($user['partner_code']) ?></p>
                        </div>

                        <div class="qr-section">
                            <div class="qr-code">
                            	<img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= htmlspecialchars($user['username']) ?><?= htmlspecialchars($user['partner_code']) ?>" >
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-face card-back">
                    <div class="curve-line curve-line-1"></div>
                    
                    <div class="terms-section">
                        <h4>Terms And Conditions</h4>
                        <p>This ID card is the property of Pennieshares and must be returned upon termination of partnership. Unauthorized use, duplication, or modification is strictly prohibited and may result in legal action.</p>
                    </div>

                    <div class="contact-info">
                        <h4>Contact Information</h4>
                        <p><strong>Phone:</strong> <span id="card-phone"> <?= htmlspecialchars($user['phone']) ?></span></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                        <p><strong>Address:</strong> <span id="card-address">123 Tech Street, Silicon Valley, CA 94000</span></p>
                    </div>

                    <div class="contact-info comp">
                        <h4>Company's Info</h4>
                        <p><strong>Email:</strong> support@pennieshares.com</p>
                        <p><strong>Call:</strong> +234 913 508 0814</p>
                        <p><strong>Visit:</strong> https://www.pennieshares.penniepoint.com</p>
                    </div>

                    <div class="barcode">||||||| |||||||| ||||||| ||||| ||||||| ||||| ||||||</div>
                </div>
            </div>

            <div class="controls">
                <button class="flip-btn" onclick="flipCard()">Flip Card</button>
                <button class="download-btn" onclick="downloadPDF()">Download PDF</button>
            </div>
        </div>
        <div class="form-section">
            <h2>Edit card info</h2>
            
            <div class="photo-upload">
                <input type="file" id="photo-input" accept="image/*">
                <img id="photo-preview" class="photo-preview" style="display: none;" alt="Photo">
                <div id="photo-placeholder" class="photo-placeholder">📷<br>Upload Photo</div>
            </div>
        
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" placeholder="Enter phone number" value="<?= htmlspecialchars($user['phone']) ?>">
            </div>
        
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" rows="4" placeholder="Enter complete address">123 Tech Street, Silicon Valley, CA 94000</textarea>
            </div>
        </div>
    </div>

    <script>
        let isFlipped = false;

        // Photo upload handling - instant reflection
        document.getElementById('photo-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const photoPreview = document.getElementById('photo-preview');
                    const cardPhoto = document.getElementById('card-photo');
                    const placeholder = document.getElementById('photo-placeholder');
                    
                    // Update both preview and card instantly
                    photoPreview.src = e.target.result;
                    cardPhoto.src = e.target.result;
                    photoPreview.style.display = 'block';
                    placeholder.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });

        // Real-time form updates for phone and address only
        document.getElementById('phone').addEventListener('input', function() {
            document.getElementById('card-phone').textContent = this.value;
        });

        document.getElementById('address').addEventListener('input', function() {
            document.getElementById('card-address').textContent = this.value;
        });

        // Flip card animation
        function flipCard() {
            const cardContainer = document.getElementById('card-container');
            isFlipped = !isFlipped;
            cardContainer.classList.toggle('flipped', isFlipped);
        }

        // Download as PDF
        async function downloadPDF() {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: [54, 85] // Portrait credit card size
            });

            const cardContainer = document.getElementById('card-container');
            
            // Capture front side
            if (isFlipped) {
                flipCard();
                await new Promise(resolve => setTimeout(resolve, 800));
            }
            
            const frontCanvas = await html2canvas(cardContainer, {
                backgroundColor: '#ffffff',
                scale: 3,
                useCORS: true
            });
            
            pdf.addImage(frontCanvas.toDataURL('image/png'), 'PNG', 0, 0, 54, 85);
            
            // Add new page for back side
            pdf.addPage();
            
            // Flip to back and capture
            flipCard();
            await new Promise(resolve => setTimeout(resolve, 800));
            
            const backCanvas = await html2canvas(cardContainer, {
                backgroundColor: '#ffffff',
                scale: 3,
                useCORS: true
            });
            
            pdf.addImage(backCanvas.toDataURL('image/png'), 'PNG', 0, 0, 54, 85);
            
            // Reset to original state
            if (isFlipped) {
                flipCard();
            }
            
            pdf.save('Employee_ID_Card.pdf');
        }

        // Add interactive card hover effect
        /*document.addEventListener('DOMContentLoaded', function() {
            const cardContainer = document.getElementById('card-container');
            
            cardContainer.addEventListener('mouseenter', function() {
                this.style.transform = isFlipped ? 'rotateY(180deg) translateY(-10px)' : 'translateY(-10px)';
            });
            
            cardContainer.addEventListener('mouseleave', function() {
                this.style.transform = isFlipped ? 'rotateY(180deg)' : 'translateY(0)';
            });
        });*/
    </script>
    <script>
        (function() {
            const html = document.documentElement;
            const applyTheme = (theme) => {
                html.setAttribute('data-theme', theme);
            };
            
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (savedTheme) {
                applyTheme(savedTheme);
            } else if (prefersDark) {
                applyTheme('dark');
            } else {
                applyTheme('light');
            }
        })();
    </script>
</body>
</html>
