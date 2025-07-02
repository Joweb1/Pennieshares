<?php
require_once __DIR__ . '/../src/functions.php';
check_auth();

// Get current user data
$user = $_SESSION['user'];

// Get referrer username
function getReferrerUsername($referralCode) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE partner_code = ?");
        $stmt->execute([$referralCode]);
        return $stmt->fetchColumn() ?: 'N/A';
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return 'N/A';
    }
}

$referrerUsername = getReferrerUsername($user['referral']);
$verificationStatus = $user['status'] == 2 ? 'Verified' : 'Not Verified';

// Fetch referred users
function getReferredUsers($partnerCode) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT username, created_at, status 
            FROM users 
            WHERE referral = :code 
            ORDER BY created_at DESC
        ");
        $stmt->execute([':code' => $partnerCode]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

$referredUsers = getReferredUsers($user['partner_code']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Profile - <?= htmlspecialchars($user['username']) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style type="text/css">
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
    /* RESPONSIVE DESIGN */
    @media (max-width: 768px) {
    .partner-stats {
    flex-direction: column;
    }
    
    }
    /* Add to existing styles */
    .profile-card {
    background: white;
    margin: 20px;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.1);
    position: relative;
    }
    
    .profile-header {
    text-align: center;
    margin-bottom: 0px;
    }
    
    .profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: #020066;
    margin: 20px auto 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .profile-avatar i {
    font-size: 50px;
    color: white;
    }
    
    .profile-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
    }
    
    .detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f8f8;
    border-radius: 10px;
    }
    
    .detail-label {
    font-weight: 600;
    color: #020066;
    }
    
    .detail-value {
    color: #555;
    font-weight: 500;
    }
    
    .button-group {
    display: flex;
    gap: 10px;
    margin-top: 30px;
    align-items:center;
    justify-content:space-around;
    }
    
    .verify-btn {
    background: linear-gradient(45deg, #0D47A1, #2962FF);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 25px;
    flex-grow:2;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.3s ease;
    box-shadow: 0 5px 15px rgba(41,98,255,0.3);
    }
    .verify-sub {
    background: linear-gradient(45deg, #0D47A1, #2962FF);
    color: white;
    border: none;
    padding: 0px;
    display:flex;
    justify-content:center;
    align-items:center;
    border-radius: 25px;
    flex-grow:2;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.3s ease;
    box-shadow: 0 5px 15px rgba(41,98,255,0.3);
    }
    .verify-b{
    background:transparent;
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 25px;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.3s ease;
    }
    
    .delete-sub {
    background: linear-gradient(45deg, #ff4444, #cc0000);
    color: white;
    border: none;
    padding: 0px;
    display:flex;
    justify-content:center;
    align-items:center;
    border-radius: 25px;
    font-size:13px;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.3s ease;
    box-shadow: 0 5px 15px rgba(255,68,68,0.3);
    }
    .delete-b {
    background:transparent;
    color: white;
    border: none;
    padding: 15px 20px;
    border-radius: 25px;
    font-size:13px;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.3s ease;
    box-shadow: 0 5px 15px rgba(255,68,68,0.3);
    }
    
    .vip-stage {
    background: linear-gradient(45deg, #FFD700, #FFC400);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 800;
    }
    .logo-img {
    width: 80px;
    height:auto;
    }
    
    @media (max-width: 768px) {
    .profile-card {
    margin: 10px;
    }
    
    .button-group {
    flex-direction: row;
    }
    
    .verify-btn, .delete-btn {
    text-align: center;
    width:auto;
    }
    }
    
    .dynamic-status { color: <?= $user['status'] == 2 ? '#00cc00' : '#ff4444' ?>; }
    
    /* Referred Partners Section */
    .referred-partners {
    background: #fff;
    margin: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    }
    
    .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    }
    
    .section-header h3 {
    color: #001970;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 10px;
    }
    
    .count-badge {
    background: #f0f4ff;
    color: #001970;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    }
    
    .partner-list {
    padding: 10px;
    }
    
    .partner-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    margin: 8px 0;
    background: #f9f9f9;
    border-radius: 8px;
    transition: transform 0.2s ease;
    }
    
    .partner-item:hover {
    transform: translateX(5px);
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .partner-username {
    font-weight: 600;
    color: #001970;
    }
    
    .partner-date {
    font-size: 11px;
    color: #666;
    }
    
    .partner-status {
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 8px;
    }
    
    .partner-status.verified {
    background: #e3fcef;
    color: #00a76f;
    }
    
    .partner-status.pending {
    background: #fff4e5;
    color: #ff9500;
    }
    
    .empty-state {
    text-align: center;
    padding: 30px 20px;
    color: #666;
    }
    
    .empty-state i {
    font-size: 2.5rem;
    color: #d0d4e3;
    margin-bottom: 15px;
    }
    
    .copy-code-btn {
    background: #001970;
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 25px;
    margin-top: 15px;
    cursor: pointer;
    transition: opacity 0.2s;
    }
    
    .copy-code-btn:hover {
    opacity: 0.9;
    }
    
    @media (max-width: 480px) {
    .partner-item {
    flex-direction: row;
    align-items: flex-start;
    gap: 10px;
    }
    
    .partner-status {
    align-self: flex-end;
    }
    }
    .back{
    	background:transparent;
    	color:white;
    	border-radius:10px;
    	font-size:25px;
    	position:absolute;
    	z-index:2;
    	top:10px;
    	font-weight:600;
    	left:10px;
    	padding:10px 10px;
    	text-align:center;
    	box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    a{
    color:inherit;
    text-decoration:none;
    }
  </style>
</head>
<body>
  <div class="cont">
    <div class="header-container">
      <div class="up-circle"></div>
      <div class="profile-header">
        <div class="profile-avatar">
          <img src="assets/images/logo.png" class="logo-img" >
        </div>
      </div>
    </div>

    <div class="profile-card">
      <?php if($user['status'] != 2): ?>
      <div class="button-group" style="margin:0px;">
        <button class="verify-btn" onclick="window.location.href='/payment'">
          <i class="fas fa-check-circle"></i> Verify Analyst Account
        </button>
      </div>
      <?php endif; ?>

      <div class="profile-details">
        <div class="detail-item">
          <span class="detail-label">Username:</span>
          <span class="detail-value">@<?= htmlspecialchars($user['username']) ?></span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Full Name:</span>
          <span class="detail-value"><?= htmlspecialchars($user['fullname']) ?></span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Email:</span>
          <span class="detail-value"><?= htmlspecialchars($user['email']) ?></span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Phone:</span>
          <span class="detail-value"><?= htmlspecialchars($user['phone']) ?></span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Partner Code:</span>
            <?php if($user['status'] == 2): ?>

          <span class="detail-value code-value"><?= htmlspecialchars($user['partner_code']) ?></span>
    <?php else: ?>
              <span class="detail-value code-value">Not verified</span>

    <?php endif; ?>

        </div>
        <div class="detail-item">
          <span class="detail-label">Your Joint Partner:</span>
          <span class="detail-value">@<?= htmlspecialchars($referrerUsername) ?></span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Account Status:</span>
          <span class="gradient-gold" style="font-weight:bolder;">
            <i class="fa fa-medal"></i> VIP <?= (int)$user['stage'] ?>
          </span>
        </div>
      </div>

      <div class="button-group">
        <form action="/logout" method="POST" class="verify-sub">
          <button type="submit" class="verify-b" >
            <i class="fas fa-sign-out-alt"></i> Logout
          </button>
         </form>
         <form action="/delete_account" method="POST" id="del"  class="delete-sub">
         <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>" />
        <button class="delete-b" type="button" onclick="confirmDelete()" >
          <i class="fas fa-trash-alt"></i> Delete Account
        </button>
        </form>
      </div>
    </div>
    
    <!-- Add this section after the info-section -->
    <section class="referred-partners">
    <div class="section-header">
    <h3><i class="fas fa-users"></i> Joint Partners</h3>
    <span class="count-badge"><?= count($referredUsers) ?> members</span>
    </div>
    
    <?php if (!empty($referredUsers)): ?>
    <div class="partner-list">
    <?php foreach ($referredUsers as $member): ?>
    <div class="partner-item">
    <div class="partner-info">
    <span class="partner-username"><?= htmlspecialchars($member['username']) ?></span>
    <span class="partner-date"><?= date('M j, Y', strtotime($member['created_at'])) ?></span>
    </div>
    <div class="partner-status <?= $member['status'] == 2 ? 'verified' : 'pending' ?>">
    <i class="fas <?= $member['status'] == 2 ? 'fa-check-circle' : 'fa-hourglass-half' ?>"></i>
    <?= $member['status'] == 2 ? 'Verified' : 'Pending' ?>
    </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
    <i class="fas fa-user-plus"></i>
    <p>No Joint partners yet<br>
    <button class="copy-code-btn" onclick="copyPartnerCode()">
    <i class="fas fa-copy"></i> Copy Your Partner Code
    </button>
    </p>
    </div>
    <?php endif; ?>
    </section>
    <div class="back" ><a href="dashboard" ><i class="fas fa-arrow-left" ></i></a></div>
  </div>

<script>
function confirmDelete() {
    if (confirm('Are you sure you want to delete your account? This cannot be undone!')) {
        document.getElementById('del').submit();
    }
}
    function copyPartnerCode() {
                <?php if($user['status'] == 2): ?>
    const code = '<?= $user['partner_code'] ?>';
            <?php else: ?>
                const code = "Not verified";
            <?php endif; ?>

    navigator.clipboard.writeText(code);
    showModal(
    '<i class="fas fa-copy"></i> Code Copied',
    `Your partner code <strong>${code}</strong> has been copied to clipboard!`
    );
    }
</script>
</body>
</html>
