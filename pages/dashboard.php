<?php
// Start session and check authentication
require_once __DIR__ . '/../src/functions.php';

check_auth();

// Get current user data
$user = $_SESSION['user'];
$partner_code = $user['partner_code'];

// Count referrals
function countReferrals($partner_code) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referral = ?");
    $stmt->execute([$partner_code]);
    return $stmt->fetchColumn();
}

$referral_count = countReferrals($partner_code);

$show_verification_modal = false;
if (isset($_GET['verification_required']) && $_GET['verification_required'] == 'true') {
    $show_verification_modal = true;
}
if (isset($_SESSION['verification_error'])) {
    $show_verification_modal = true;
    unset($_SESSION['verification_error']);
}

// Daily Office Visit Email Logic
/*
$today = date('Y-m-d');
if (!isset($_SESSION['last_daily_email_sent']) || $_SESSION['last_daily_email_sent'] !== $today) {
    $dashboardLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/dashboard";
    $emailBody = getDailyVisitEmailTemplate($user['username'], $dashboardLink);
    sendEmail($user['email'], $user['username'], "Your Daily Pennieshares Check-in!", $emailBody);
    $_SESSION['last_daily_email_sent'] = $today;
}
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard</title>
  <!-- Font Awesome (CDN) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <!-- CSS -->
  <style type="text/css">
  	/* Basic Reset */
  	* {
  	margin: 0;
  	padding: 0;
  	box-sizing: border-box;
  	}
  	
  	body {
  	font-family: System, sans-serif;
  	background-color: #f5f5f5;
  	color: #000;
  	}
  	.up-circle {
  		top:0;
  		left:0;
  		position:absolute;
  		background: linear-gradient(135deg, rgba(0,0,250,1) 0%, rgba(2,0,102,1) 100%);
  		height:40vh;
  		width:130vw;
  		transform:translateX(-30vw);
  		z-index:-90;
  		border-bottom-left-radius: 90% 40%;
  		border-bottom-right-radius: 100% 100%;
  	}
  	.cont {
  	overflow-x:hidden;
  	width:100vw;
  	height:100vh;
  	}
  	
  	/* HEADER (TOP SECTION) */
  	.header-container {
  	/* Blue gradient background 
  	background: linear-gradient(135deg, #0D47A1 0%, #2962FF 100%);*/
  	padding: 20px;
  	position: relative;
  	border-bottom-left-radius: 50% 10%;
  	border-bottom-right-radius: 50% 10%;
  	}
  	
  	/* Top row with username and icons */
  	.header-top {
  	display: flex;
  	justify-content: space-around;
  	align-items: center;
  	margin-bottom: 20px;
  	}
  	.dashboard {
  		display:flex;
  		color:white;
  		background: linear-gradient(135deg, rgba(87,71,255,0.61), rgba(190,28,99,0.61) , rgba(2,0,102,0.61) );
  		margin:30px 0px;
  		padding:0;
  		margin-bottom:5px;
  		justify-content:space-around;
  		align-items:center;
  		border-radius:3px;
  		box-shadow: 0 0px 20px rgba(0,0,0,0.21);
  	}
  	.user-p {
  		background:black;
  		width:50px;
  		height:50px;
  		text-align:center;
  		display:flex;
  		align-items:center;
  		justify-content:center;
  		border-radius:50%;
  		overflow:hidden;
  	}
  	.user-p i{
  		font-size:67px;
  		color:white;
  	}
  	.user-info {
  	display: flex;
  	flex-direction: column;
  	flex-grow:2;
  	margin-left:10px;
  	}
  	
  	.username {
  	font-size: 18px;
  	font-weight: bold;
  	color: #fff;
  	margin-bottom: 5px;
  	}
  	
  	/* VIP Badge with gold gradient text */
  	.vip-badge {
  	font-weight: 800;
  	letter-spacing:1px;
  	font-size: 14px;
  	background: linear-gradient(45deg, #FFD700, #FFC400, #FFD700);
  	color:transparent;
  	mask-clip:text;
  	-webkit-background-clip: text;
  	-webkit-text-fill-color: transparent;
  	}
  	
  	.vip-level {
  	font-size: 16px;
  	font-weight:800;
  	letter-spacing:1px;
  	}
  	
  	/* Envelope and Share icons */
  	.header-icons i {
  	font-size: 20px;
  	color: #fff;
  	margin-left: 15px;
  	cursor: pointer;
  	}
  	
  	/* Verified Analyst Card */
  	.analyst-card {
  	color:white;
  	flex-grow:2;
  	border-radius: 8px;
  	padding: 10px 15px;
  	max-width: 220px;
  	text-align: center;
  	margin-bottom: 15px;
  	line-height:20px;
  	}
  	
  	.verified-text {
  	font-size: 14px;
  	color:rgba(20,250,100,1);
  	font-weight: bold;
  	display: block;
  	text-align:left;
  	}
  	
  	.analyst-title {
  	font-size: 32px;
  	margin: 5px 0;
  	font-weight: 800;
  	text-transform: uppercase;
  	}
  	
  	.subtext {
  	font-size: 12px;
  	text-align:right;
  	font-weight:500;
  	line-height:12px;
  	}
  	
  	/* Apply this class to the element containing your text */
  	.gradient-gold {
  	/* Create a linear gradient with multiple gold tones */
  	background: linear-gradient(130deg, 
  	rgba(175,148,0,1),  /* Darkish gold */
  	rgba(220,200,40,1), /* Classic gold */
  	rgba(238,223,48,1), /* Bright, shining gold */
  	rgba(234,140,5,1),  /* Warm, reflective gold */
  	rgba(125,58,0,1),  /* Darkish gold */
  	rgba(220,200,40,1), /* Classic gold */
  	rgba(238,223,48,1), /* Bright, shining gold */
  	rgba(234,140,5,1),  /* Warm, reflective gold */
  	rgba(125,58,0,1),  /* Darkish gold */
  	rgba(220,200,40,1), /* Classic gold */
  	rgba(238,223,48,1), /* Bright, shining gold */
  	rgba(234,140,5,1),  /* Warm, reflective gold */
  	rgba(125,58,0,1),  /* Darkish gold */
  	rgba(220,200,40,1), /* Classic gold */
  	rgba(238,223,48,1), /* Bright, shining gold */
  	rgba(234,140,5,1),  /* Warm, reflective gold */
  	rgba(125,58,0,1),  /* Darkish gold */
  	rgba(220,200,40,1), /* Classic gold */
  	rgba(238,223,48,1), /* Bright, shining gold */
  	rgba(234,140,5,1)  /* Warm, reflective gold */
  	);
  	background-size: 300%;
  	
  	/* Use background clip to show gradient through the text */
  	-webkit-background-clip: text;
  	-webkit-text-fill-color: transparent;
  	background-clip: text;
  	color: transparent;
  	
  	/* Animate the background for a shimmering effect */
  	animation: shine 12s linear infinite;
  	}
  	
  	/* Define the animation */
  	@keyframes shine {
  	0% {
  	background-position: 0%;
  	}
  	50% {
  	background-position: 100%;
  	}
  	100% {
  	background-position: 0%;
  	}
  	}
  	/* Partnering Code & Total Partner */
  	.partner-stats {
  	display: flex;
  	gap: 10px;
  	border-left:1px solid white;
  	font-size:14px;
  	font-weight:bold;
  	}
  	
  	.partner-code, .total-partner {
  	border-radius: 8px;
  	padding: 5px 15px;
  	flex: 1;
  	text-align: center;
  	font-weight:bold;
  	}
  	
  	.partner-code span, .total-partner span {
  	display: block;
  	font-weight:bold;
  	}
  	
  	.total-partner span {
  		border:1px solid white;
  		font-weight:bold;
  	}
  	.code-value {
  	font-size: 18px;
  	color: #ff5722 !important; /* Orange for code */
  	background:white;
  	width:auto;
  	margin-top: 5px;
  	font-weight:bold;
  	}
  	
  	.partner-number {
  	font-size: 30px; /* Blue for total partner number */
  	margin-top: 5px;
  	font-weight:bold;
  	border:1px solid black;
  	}
  	
  	/* ANNOUNCEMENTS */
  	.announcements {
  	display:flex;
  	gap:10px;
  	align-items:center;
  	background-color: #fff;
  	margin: 0px 20px;
  	padding: 10px 15px;
  	border-radius: 8px;
  	text-align: center;
  	font-weight: bold;
  	color: #000;
  	box-shadow: 0 2px 5px rgba(0,0,0,0.1);
  	}
  	
  	.announcements h3 {
  	font-size: 10px;
  	color:rgba(180,10,50,1);
  	}
  	
  	/* ICON BOXES SECTION */
  	.icon-boxes {
  	display: grid;
  	grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
  	gap: 10px;
  	margin: 20px;
  	}
  	
  	.box {
  	background-color: #fff;
  	border-radius: 8px;
  	padding: 15px;
  	text-align: center;
  	color: #000;
  	box-shadow: 0 2px 5px rgba(0,0,0,0.1);
  	}
  	
  	.box i {
  	font-size: 20px;
  	margin-bottom: 10px;
  	color: rgba(2,0,102,1); /* A deep blue color */
  	}
  	
  	.box p {
  	font-size: 12px;
  	font-weight: bold;
  	}
  	
  	/* ADDITIONAL INFO SECTION */
  	.info-section {
  	margin: 20px;
  	display: grid;
  	grid-template-columns: 1fr;
  	gap: 20px;
  	}
  	
  	.info-block {
  	background-color: #fff;
  	border-radius: 8px;
  	padding: 10px 15px;
  	display:flex;
  	justify-content:space-around;
  	align-items:center;
  	gap:15px;
  	box-shadow: 0 2px 5px rgba(0,0,0,0.1);
  	
  	}
  	
  	.info-block h4 {
  	font-size: 15px;
  	margin-bottom: 5px;
  	font-weight: bold;
  	}
  	.info-block .user-c .user-i  {
  		font-size:40px;
  	}
  	.info-block .user-c p  {
  	font-size:10px;
  	font-weight:800;
  	text-align:center;
  	color:rgba(150,20,50,1);
  	}
  	.info-block .medal{
  		text-align:center;
  	}
  	
  	.info-block p {
  	font-size: 14px;
  	color: #444;
  	}
  	
  	/* VIP medal icon with gold gradient (similar approach to text) */
  	.vip-medal {
  	background: linear-gradient(45deg, #FFD700, #FFC400, #FFD700);
  	-webkit-background-clip: text;
  	-webkit-text-fill-color: transparent;
  	margin-right: 5px;
  	}
  	
  	.vip-tag {
  	background: linear-gradient(45deg, #FFD700, #FFC400, #FFD700);
  	-webkit-background-clip: text;
  	-webkit-text-fill-color: transparent;
  	}
  	
  	/* RESPONSIVE DESIGN */
  	@media (max-width: 768px) {
  	.partner-stats {
  	flex-direction: column;
  	}
  	
  	}
  	.modal-overlay {
  	position: fixed;
  	top: 0;
  	left: 0;
  	width: 100%;
  	height: 100%;
  	background: rgba(0,0,0,0.5);
  	backdrop-filter: blur(5px);
  	display: none;
  	justify-content: center;
  	align-items: center;
  	z-index: 1000;
  	animation: fadeIn 0.3s ease;
  	}
  	
  	.modal-content {
  	background: white;
  	padding: 25px;
  	border-radius: 15px;
  	max-width: 400px;
  	width: 90%;
  	transform: translateY(-20px);
  	transition: transform 0.3s ease;
  	box-shadow: 0 10px 30px rgba(0,0,0,0.2);
  	}
  	
  	.modal-header {
  	display: flex;
  	justify-content: space-between;
  	align-items: center;
  	margin-bottom: 20px;
  	}
  	
  	.modal-title {
  	color: #020066;
  	font-size: 1.4rem;
  	display: flex;
  	align-items: center;
  	gap: 10px;
  	}
  	
  	.close-modal {
  	cursor: pointer;
  	font-size: 1.5rem;
  	color: #666;
  	transition: color 0.3s ease;
  	}
  	
  	.modal-body {
  	margin: 15px 0;
  	}
  	
  	.referral-input {
  	display: flex;
  	gap: 10px;
  	margin: 15px 0;
  	}
  	
  	.referral-input input {
  	flex: 1;
  	padding: 10px;
  	border: 2px solid #eee;
  	border-radius: 8px;
  	font-size: 14px;
  	}
  	
  	.action-buttons {
  	display: flex;
  	gap: 10px;
  	margin-top: 20px;
  	}
  	
  	.pp-btn {
  	padding: 12px 25px;
  	border: none;
  	border-radius: 25px;
  	cursor: pointer;
  	transition: all 0.3s ease;
  	display: flex;
  	align-items: center;
  	gap: 8px;
  	font-weight: 600;
  	}
  	
  	.pp-btn-primary {
  	background: linear-gradient(135deg, #020066, #2962FF);
  	color: white;
  	}
  	
  	.pp-btn-secondary {
  	background: #f0f4ff;
  	color: #020066;
  	}
  	
  	.verification-alert {
  	text-align: center;
  	padding: 20px;
  	background: #fff3f3;
  	border-radius: 10px;
  	border-left: 4px solid #ff4444;
  	}
  	
  	@keyframes fadeIn {
  	from { opacity: 0; }
  	to { opacity: 1; }
  	}
  	
  	.show-modal .modal-content {
  	transform: translateY(0);
  	}
  	a{
  		color:inherit;
  		text-decoration:none;
  	}
  </style>
</head>
<body>
  
<!-- Modified Header Section -->
<div class="cont">
  <div class="header-container">
    <div class="up-circle"></div>
    <div class="header-top">
      <div class="user-p">
        <i class="fa fa-user-circle"></i>
      </div>
      <div class="user-info">
        <h1 class="username"><?= htmlspecialchars($user['username']) ?></h1>
        <span class="gradient-gold">
          <i class="fas fa-medal"></i>
          <span class="vip-level">VIP <?= htmlspecialchars($user['stage']) ?></span>
        </span>
      </div>
      <div class="header-icons">
        <i class="fas fa-envelope"></i>
        <i class="fas fa-share-alt"></i>
      </div>
    </div>

    <!-- Verified Analyst Card -->
    <div class="dashboard">
      <div class="analyst-card">
        <?php if($user['status'] == 2): ?>
          <span class="verified-text">
            <i class="fas fa-check-circle verified"></i> Verified
          </span>
        <?php else: ?>
          <span class="verified-text" style="color:#ff4444;">
            <i class="fas fa-times-circle"></i> Not Verified
          </span>
        <?php endif; ?>
        <h2 class="analyst-title">ANALYST</h2>
        <p class="subtext">on penniepoint</p>
      </div>

      <!-- Partnering Code & Total Partners -->
      <div class="partner-stats">
        <div class="partner-code">
          <span>Partnering Code</span>
                  <?php if($user['status'] == 2): ?>
          <span class="code-value"><?= htmlspecialchars($partner_code) ?></span>
                  <?php else: ?>
                  <span class="code-value">Not verified</span>
    <?php endif; ?>

        </div>
        <div class="total-partner">
          <span>Total Partner</span>
          <span class="partner-number"><?= $referral_count ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Rest of the HTML remains the same -->
  <!-- ... existing announcements, icon boxes, info sections ... -->

<!-- Announcements -->
  <section class="announcements">
  	<i class="fa fa-bullhorn" ></i>
    <h3>Announcements. Announcements. Announcements....</h3>
  </section>

  <!-- Icon Boxes Section -->
  <section class="icon-boxes">
    <div class="box">
    <a href="about" >
      <i class="fas fa-building"></i>
      <p>Penniepoint</p>
     </a>
    </div>
    <div class="box">
      <a href="loading">
        <i class="fas fa-tv-alt"></i>
        <p>Office</p>
      </a>
    </div>
    <div class="box">
    <a href="profile" >
      <i class="fas fa-users"></i>
      <p>Partner</p>
    </a>
    </div>
       <div class="box">
    <a href="stages" >
      <i class="fas fa-layer-group"></i>
      <p>Stages</p>
    </a>
    </div>
        <div class="box">
      <i class="fas fa-certificate"></i>
      <p>Certificate</p>
    </div>
    <div class="box">
    <a href="idcard" >
      <i class="fas fa-id-card"></i>
      <p>ID-Card</p>
     </a>
    </div>
  </section>

  <!-- Additional Info Section -->
            <?php if($user['status'] == 2): ?>

  <section class="info-section">
    <div class="info-block">
    	<div class="user-c" >
    		<div class="user-i" ><i class="fas fa-user" ></i></div>
    		<p>1st Start</p>
    	</div>
    	<div>
      		<h4>Understanding Your Business </h4>
      		<p>The business is all about ...</p>
    	</div>
    	<div class="medal gradient-gold"> <i class="fas fa-medal "></i> VIP1</div>
    </div>
    <div class="info-block">
    	<div class="user-c" >
    		<div class="user-i" ><i class="fas fa-user" ></i></div>
    		<p>2nd Start</p>
    	</div>
    	<div class="text-e" >
      		<h4>Your First Assignment </h4>
      		<p>Here is your assignment and guide ...</p>
      	</div>
      		<div class="medal gradient-gold"> <i class="fas fa-medal "></i> VIP1</div>
    </div>
  </section>
<?php endif; ?>
<section class="icon-boxs">
    <div class="box">
    <a href="faqs" >
      <i class="fas fa-question-circle"></i>
      <p>FAQs</p>
    </a>
    </div>
  </section>
</div>

<!-- Add this HTML before closing body -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal-content">
    <div class="modal-header">
      <h3 class="modal-title" id="modalTitle"></h3>
      <span class="close-modal" onclick="closeModal()">&times;</span>
    </div>
    <div class="modal-body" id="modalBody"></div>
  </div>
</div>
  <!-- Optional JavaScript -->
<script type="text/javascript">
	// JavaScript Implementation
	const modalOverlay = document.getElementById('modalOverlay');
	const modalTitle = document.getElementById('modalTitle');
	const modalBody = document.getElementById('modalBody');
	// Update referral link with actual partner code
        <?php if($user['status'] == 2): ?>

	const referralLink = "https://penniepoint.com/register?partnercode=<?= $partner_code ?>";
        <?php else: ?>
            	const referralLink = "Not Verified";
        <?php endif; ?>

	
	// Message Icon Click
	document.querySelector('.fa-envelope').addEventListener('click', () => {
	showModal(
	'<i class="fas fa-envelope-open-text"></i> New Message',
	`<div class="message-content">
	<p>Welcome to Penniepoint! 🎉</p>
	<p>Your analyst account is now active. Check your email for full verification details.</p>
	<div class="action-buttons">
	<button class="pp-btn pp-btn-primary" onclick="closeModal()">
	<i class="fas fa-check"></i> Got It
	</button>
	</div>
	</div>`
	);
	});
	
	// Share Icon Click
	document.querySelector('.fa-share-alt').addEventListener('click', () => {
	showModal(
	'<i class="fas fa-share"></i> Share Link',
	`<div class="referral-section">
	<div class="referral-input">
	<input type="text" value="${referralLink}" id="referralInput" readonly>
	</div>
	<div class="action-buttons">
	<button class="pp-btn pp-btn-primary" onclick="copyLink()">
	<i class="fas fa-copy"></i> Copy
	</button>
	<button class="pp-btn pp-btn-secondary" onclick="shareLink()">
	<i class="fas fa-share"></i> Share
	</button>
	</div>
	</div>`
	);
	});
	<?php if($user['status'] != 2): ?>
    	// Certificate/ID-Card Click
    	document.querySelectorAll('.fa-certificate, .fa-id-card, .fa-tv-alt').forEach(item => {
    	item.closest('.box').addEventListener('click', () => {
    	showModal(
    	'<i class="fas fa-exclamation-triangle"></i> Account Verification',
    	`<div class="verification-alert">
    	<p>To access this feature, please verify your analyst account first.</p>
    	<div class="action-buttons">
    	<button class="pp-btn pp-btn-primary" onclick="window.location.href='/payment'">
    	<i class="fas fa-shield-check"></i> Verify Account
    	</button>
    	</div>
    	</div>`
    	);
    	});
    	});
	<?php endif; ?>

    <?php if ($show_verification_modal): ?>
    showModal(
        '<i class="fas fa-exclamation-triangle"></i> Account Verification Required',
        `<div class="verification-alert">
        <p>You need to verify your account to access that feature.</p>
        <div class="action-buttons">
        <button class="pp-btn pp-btn-primary" onclick="window.location.href='/payment'">
        <i class="fas fa-shield-check"></i> Verify Account
        </button>
        </div>
        </div>`
    );
    <?php endif; ?>
	
	// Info Blocks Click
	document.querySelectorAll('.info-block').forEach((block, index) => {
	block.addEventListener('click', () => {
	const titles = ['Business Fundamentals', 'First Assignment Guide'];
	const contents = [
	`Penniepoint's system focuses on financial discipline through structured saving plans. 
	As an analyst, you'll help users achieve their goals while earning through your network.`,
	`Your first task is to understand our tiered partnership system. 
	Start by recruiting 15 partners to upgrade to Manager level.`
	];
	
	showModal(
	`<i class="fas fa-info-circle"></i> ${titles[index]}`,
	`<div class="info-content">
	<p>${contents[index]}</p>
	<div class="action-buttons">
	<button class="pp-btn pp-btn-primary" onclick="closeModal()">
	<i class="fas fa-check"></i> Continue
	</button>
	</div>
	</div>`
	);
	});
	});
	
	function showModal(title, content) {
	modalTitle.innerHTML = title;
	modalBody.innerHTML = content;
	modalOverlay.style.display = 'flex';
	setTimeout(() => modalOverlay.classList.add('show-modal'), 10);
	}
	
	function closeModal() {
	modalOverlay.classList.remove('show-modal');
	setTimeout(() => modalOverlay.style.display = 'none', 300);
	}
	
	function copyLink() {
	navigator.clipboard.writeText(referralLink);
	alert('Link copied to clipboard!');
	}
	
	function shareLink() {
	if (navigator.share) {
	navigator.share({
	title: 'Join Penniepoint',
	text: 'Start your financial discipline journey with Penniepoint:',
	url: referralLink
	});
	} else {
	alert('Sharing not supported - please copy the link instead');
	}
	}
	
	// Close modal when clicking outside
	modalOverlay.addEventListener('click', (e) => {
	if (e.target === modalOverlay) closeModal();
	});
</script>
</body>
</html>