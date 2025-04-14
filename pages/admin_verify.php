<?php
require_once __DIR__ . '/../src/functions.php';

// Hardcoded admin emails
$adminEmails = [
    'admin@penniepoint.com',
    'supervisor@penniepoint.com'
];

// Check if current user is admin
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['email'], $adminEmails)) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access Denied");
}

// Handle verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proof_id'])) {
    
    $proofId = (int)$_POST['proof_id'];
    $userId = (int)$_POST['user_id'];
    
    try {
        
        // Update user status
        $stmt = $pdo->prepare("
            UPDATE users 
            SET status = 2 
            WHERE id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        
        $_SESSION['success'] = "User verified successfully!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating status: " . $e->getMessage();
    }
    
    try {
    
   $stmt = $pdo->prepare("
   UPDATE payment_proofs 
   SET status = 2 
   WHERE id = :proof_id
   ");
   $stmt->execute([':proof_id' => $proofId]);
   header("Location: admin_verify");
   exit;
    
    $_SESSION['success'] = "User verified successfully!";
    } catch (PDOException $e) {
    $_SESSION['error'] = "Error updating status: " . $e->getMessage();
    }
}

// Get pending verifications
try {
    // In admin_verify.php, modify the query
    $stmt = $pdo->prepare("
    SELECT u.id, u.username, u.email, p.status, p.id, p.user_id, p.file_path, p.uploaded_at 
    FROM users u
    LEFT JOIN payment_proofs p ON u.id = p.user_id
    WHERE p.status = 1
    ORDER BY p.uploaded_at DESC
    ");
    
    $stmt->execute();
    $pendingProofs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
	<html lang="en">
	<head>
	  <meta charset="UTF-8" />
	  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	  <title>Payment Verification</title>
	  <!-- Font Awesome (CDN) -->
	  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
	  <!-- CSS -->
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
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .verification-list {
            display: grid;
            gap: 1.5rem;
        }
        
        .verification-item {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            display:flex;
            flex-direction:column;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            align-items: center;
        }
        
        .proof-image {
        	display:block;
            max-width: 200px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .verification-item  form{
        	display:block;
        }
        
        .proof-image:hover {
            transform: scale(1.05);
        }
        
        .user-info {
            padding: 0 1.5rem;
        }
        
        .verify-btn {
            background: #2ecc71;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .verify-btn:hover {
            background: #27ae60;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>Pending Verifications</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <div class="verification-list">
            <?php foreach ($pendingProofs as $proof): ?>
            <div class="verification-item">
                <img src="<?= htmlspecialchars($proof['file_path']) ?>" 
                     class="proof-image" 
                     alt="Payment proof">
                
                <div class="user-info">
                    <h3><?= htmlspecialchars($proof['username']) ?></h3>
                    <p><?= htmlspecialchars($proof['email']) ?></p>
                    <small>Uploaded: <?= date('M j, Y H:i', strtotime($proof['uploaded_at'])) ?></small>
                </div>
                <div>
                <form method="POST">
                    <input type="hidden" name="proof_id" value="<?= $proof['id'] ?>">
                    <input type="hidden" name="user_id" value="<?= $proof['user_id'] ?>">
                    <button type="submit" class="verify-btn">
                        <i class="fas fa-check"></i> Verify Account
                    </button>
                </form>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($pendingProofs)): ?>
                <div class="empty-state">
                	<p>
                    <i class="fas fa-check-circle"></i>
                    No pending verifications</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
