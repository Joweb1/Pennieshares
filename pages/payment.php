<?php
require_once __DIR__ . '/../src/functions.php';
check_auth();

$user = $_SESSION['user'];
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$error = '';
$success = '';
// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["file"]["name"]);
        
        $check = move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);

        if ($check) {
            try {
                $stmt = $pdo->prepare("SELECT file_path FROM payment_proofs WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $existingProof = $stmt->fetch();
                
                // Delete old file if exists
                if ($existingProof) {
                $oldFilePath = __DIR__ . '/../' . $existingProof['file_path'];
                if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
                }
                }
                
                // Insert or update proof
                $stmt = $pdo->prepare("
                INSERT INTO payment_proofs (user_id, file_path)
                VALUES (:user_id, :file_path)
                ON CONFLICT(user_id) DO UPDATE SET
                file_path = excluded.file_path,
                uploaded_at = CURRENT_TIMESTAMP,
                status = 1
                ");
                
                $stmt->execute([
                ':user_id' => $user['id'],
                ':file_path' => $target_file
                ]);
                
                $success = "Payment proof " . ($existingProof ? "updated" : "uploaded") . " successfully!";
                
            } catch (PDOException $e) {
                $error = "Error saving payment proof".$e;
            }
        } else {
            $error = "Error uploading file";
        }
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
	    .payment-container {
	      max-width: 600px;
	      margin: 20px auto;
	      padding: 0 20px;
	    }
	
	    .payment-card {
	      background: white;
	      border-radius: 20px;
	      padding: 30px 25px;
	      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
	      transform: translateY(0);
	      transition: transform 0.3s ease;
	    }
	
	    .payment-card:hover {
	      transform: translateY(-5px);
	    }
	
	    .payment-header {
	      text-align: center;
	      margin-bottom: 0px;
	    }
	
	    .payment-title {
	      color: #333;
	      font-size: 23px;
	      letter-spacing:.21px;
	      margin-bottom: 5px;
	      font-weight: 700;
	    }
	
	    .payment-subtitle {
	      color: #666;
	      font-size: 14px;
	    }
	
	    .payment-details {
	      background: #f9f9f9;
	      border-radius: 15px;
	      padding: 20px;
	      margin: 25px 0;
	      border: 2px solid #e8e8e8;
	    }
	
	    .detail-row {
	      display: flex;
	      justify-content: space-between;
	      align-items: center;
	      padding: 12px 0;
	      border-bottom: 1px solid #eee;
	    }
	
	    .detail-row:last-child {
	      border-bottom: none;
	    }
	
	    .detail-label {
	      color: #555;
	      font-weight: 500;
	    }
	
	    .detail-value {
	      color: #020066;
	      font-weight: 600;
	      font-size: 16px;
	    }
	
	    .upload-btn {
	      background: linear-gradient(45deg, #2ecc71, #27ae60);
	      color: white;
	      border: none;
	      padding: 18px 35px;
	      border-radius: 30px;
	      font-weight: 600;
	      cursor: pointer;
	      transition: all 0.3s ease;
	      display: flex;
	      align-items: center;
	      gap: 12px;
	      margin: 0 auto;
	      box-shadow: 0 5px 15px rgba(46,204,113,0.3);
	    }
	
	    .upload-btn:hover {
	      transform: scale(1.05);
	      box-shadow: 0 8px 20px rgba(46,204,113,0.4);
	    }
	
	    .upload-btn i {
	      font-size: 20px;
	    }
	
	    .file-input {
	      display: none;
	    }
	
	    .security-note {
	      text-align: center;
	      color: #666;
	      font-size: 14px;
	      margin-top: 25px;
	      opacity: 0;
	      animation: fadeIn 1s ease forwards;
	    }
	
	    .bank-logo {
	      width: 80px;
	      height: 80px;
	      background: #2ecc71;
	      border-radius: 50%;
	      margin: 20px auto 10px;
	      display: flex;
	      align-items: center;
	      justify-content: center;
	      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
	    }
	
	    .bank-logo i {
	      color: white;
	      font-size: 40px;
	    }
	
	    @keyframes fadeIn {
	      from { opacity: 0; transform: translateY(10px); }
	      to { opacity: 1; transform: translateY(0); }
	    }
	
	    .pulse {
	      animation: pulse 2s infinite;
	    }
	
	    @keyframes pulse {
	      0% { transform: scale(1); }
	      50% { transform: scale(1.05); }
	      100% { transform: scale(1); }
	    }
	
	    .success-message {
	      color: #2ecc71;
	      font-size: 18px;
	      text-align: center;
	      margin: 10px 0;
	      opacity: 1;
	      transition: opacity 0.3s ease;
	    }
	    
	    
	    
	    /* Add these styles to existing CSS */
	    .upload-modal {
	    display: none;
	    position: fixed;
	    top: 0;
	    left: 0;
	    width: 100%;
	    height: 100%;
	    background: rgba(0,0,0,0.5);
	    backdrop-filter: blur(5px);
	    z-index: 1000;
	    animation: fadeFlow 0.5s ease;
	    }
	    
	    .modal-content {
	    background: white;
	    width: 90%;
	    max-width: 500px;
	    margin: 0vh auto;
	    border-radius: 15px;
	    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
	    transform: translateY(15vh);
	    translate: 0.6s ease;
	    animation: transformDone 0.8s ease;
	    }
	    
	    .modal-header {
	    padding: 1.5rem;
	    border-bottom: 1px solid #eee;
	    display: flex;
	    justify-content: space-between;
	    align-items: center;
	    }
	    
	    .modal-header h3 {
	    color: #333;
	    margin: 0;
	    font-size: 1.5rem;
	    }
	    
	    .close-modal {
	    cursor: pointer;
	    font-size: 1.8rem;
	    color: #666;
	    transition: color 0.3s ease;
	    }
	    
	    .close-modal:hover {
	    color: #333;
	    }
	    
	    .modal-body {
	    padding: 1.5rem;
	    }
	    
	    .upload-area {
	    border: 2px dashed #2ecc71;
	    border-radius: 12px;
	    padding: 2rem;
	    text-align: center;
	    transition: all 0.3s ease;
	    position: relative;
	    }
	    
	    .upload-area.dragover {
	    background: rgba(46,204,113,0.1);
	    border-color: #27ae60;
	    }
	    
	    .choose-file-btn {
	    background: #2ecc71;
	    color: white;
	    border: none;
	    padding: 12px 25px;
	    border-radius: 25px;
	    font-weight: 600;
	    cursor: pointer;
	    transition: all 0.3s ease;
	    display: inline-flex;
	    align-items: center;
	    gap: 10px;
	    }
	    
	    .choose-file-btn:hover {
	    background: #27ae60;
	    transform: translateY(-2px);
	    }
	    
	    .preview-container {
	    margin: 1rem 0;
	    position: relative;
	    }
	    
	    .preview-image {
	    max-width: 100%;
	    max-height: 200px;
	    border-radius: 8px;
	    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
	    }
	    
	    .supported-files {
	    color: #666;
	    font-size: 0.9rem;
	    margin-top: 1rem;
	    }
	    
	    .modal-footer {
	    padding: 1.5rem;
	    border-top: 1px solid #eee;
	    display: flex;
	    justify-content: flex-end;
	    gap: 1rem;
	    }
	    
	    .cancel-btn {
	    background: #e74c3c;
	    color: white;
	    border: none;
	    padding: 10px 20px;
	    border-radius: 20px;
	    cursor: pointer;
	    transition: all 0.3s ease;
	    }
	    
	    .cancel-btn:hover {
	    background: #c0392b;
	    }
	    
	    .submit-btn {
	    background: #2ecc71;
	    color: white;
	    border: none;
	    padding: 10px 25px;
	    border-radius: 20px;
	    cursor: pointer;
	    transition: all 0.3s ease;
	    opacity: 0.7;
	    }
	    
	    .submit-btn:not([disabled]) {
	    opacity: 1;
	    transform: translateY(-2px);
	    box-shadow: 0 5px 15px rgba(46,204,113,0.3);
	    }
	    
	    .upload-status {
	    color: #666;
	    font-size: 0.9rem;
	    margin-top: 1rem;
	    text-align: center;
	    }
	    .logo-img {
	    width: 80px;
	    height:auto;
	    }
	    
	    @keyframes fadeFlow {
	    from { opacity: 0; }
	    to { opacity: 1; }
	    }
	    @keyframes transformDone {
	    from{transform:translateY(-10vh);}
	    to { transform:translateY(15vh); }
	    }
	    #fileInput {
	    	position:absolute;
	    	top:0px;
	    	left:0px;
	    	width:100%;
	    	height:100%;
	    	opacity:0;
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
	      <div class="up-circle" style="background: linear-gradient(135deg, #2ecc71 0%, #020066 100%);"></div>
	      <div class="payment-header">
	        <div class="bank-logo pulse">
	          <img src="assets/images/logo.png" class="logo-img"  >
	        </div>
	      </div>
	    </div>
	
	    <div class="payment-container">
	      <div class="payment-card">
	        <h1 class="payment-title">Account Verification</h1>
	        <p class="payment-subtitle">Complete your analyst verification by making payment</p>
	
	        <div class="payment-details">
	          <div class="detail-row">
	          <span class="detail-label">Username:</span>
	          <span class="detail-value"><?= htmlspecialchars($user['username']) ?></span>
	          </div>
	          <div class="detail-row">
	            <span class="detail-label">Bank:</span>
	            <span class="detail-value">Moniepoint</span>
	          </div>
	          <div class="detail-row">
	            <span class="detail-label">Account No:</span>
	            <span class="detail-value">9135580911</span>
	          </div>
	          <div class="detail-row">
	            <span class="detail-label">Account Name:</span>
	            <span class="detail-value">Penniepoint</span>
	          </div>
	        </div>
	
	        <label>
	          <button class="upload-btn">
	            <i class="fas fa-file-upload"></i>
	            Upload Payment Proof
	          </button>
	        </label>
	
	        <div class="security-note">
	          <i class="fas fa-lock"></i> Your information is securely encrypted
	        </div>
	        <div>
	        	<?php if (isset($error) && $error !== ''): ?>
	        	<div class="error-message"><i class="fas fa-times-circle"></i>
	        	<?= $error ?>
	        	</div>
	        	<?php endif; ?>
	        	
	        	<?php if (isset($success) && $success !== ''): ?>
	        	<div class="success-message"><i class="fas fa-check-circle"></i>
	        	<?= $success ?>
	        	</div>
	        	<?php endif; ?>
	        </div>
	        <!-- In your payment.php HTML -->
	        <div class="current-proof-section">
	        <?php
	        $stmt = $pdo->prepare("SELECT file_path, uploaded_at FROM payment_proofs WHERE user_id = ?");
	        $stmt->execute([$user['id']]);
	        $currentProof = $stmt->fetch();
	        ?>
	        
	        <?php if ($currentProof): ?>
	        <div class="current-proof">
	        <h4>Current Payment Proof:</h4>
	        <img src="<?= htmlspecialchars($currentProof['file_path']) ?>" 
	        alt="Current payment proof" 
	        class="proof-preview">
	        <?php if (!empty($currentProof['uploaded_at'])): ?>
	        <p>Uploaded on: <?= date('M j, Y H:i', strtotime($currentProof['uploaded_at'])) ?></p>
	        <?php endif; ?>
	        </div>
	        <?php endif; ?>
	        </div>
	        
	        <style>
	        .proof-preview {
	        max-width: 450px;
	        width:100%;
	        border-radius: 8px;
	        margin-top: 10px;
	        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
	        }
	        
	        .current-proof-section {
	        margin-top: 20px;
	        padding: 15px;
	        background: #f8f9fa;
	        border-radius: 8px;
	        }
	        </style>
	      </div>
	    </div>
	    <div class="back" ><a href="dashboard" ><i class="fas fa-arrow-left" ></i></a></div>
	  </div>
	<!-- In your HTML, modify the upload section -->
	<div class="upload-modal" id="uploadModal">
	<div class="modal-content" id="uploadContent">
	<div class="modal-header">
	<h3>Upload Payment Proof</h3>
	<span class="close-modal">&times;</span>
	</div>
	<form method="post" enctype="multipart/form-data" >
	<div class="modal-body">
	<div class="upload-area" id="dropZone">
	<input type="file" name="file"  id="fileInput" accept="image/*" >
	<div class="preview-container" id="previewContainer"></div>
	<button class="choose-file-btn" id="chooseFileBtn">
	<i class="fas fa-cloud-upload-alt"></i>
	Select Image File
	</button>
	<p class="supported-files">Supported formats: JPG, PNG (Max 2MB)</p>
	</div>
	<div class="upload-status" id="uploadStatus"></div>
	</div>
	<div class="modal-footer">
	<button class="cancel-btn">Cancel</button>
	<input type="submit"    class="submit-btn" value="✅ Comfirm Upload" >
	</div>
	</form>
	</div>
	</div>
	
	<script>
		
	  // Add this JavaScript
	  const modal = document.getElementById('uploadModal');
	  const chooseFileBtn = document.getElementById('chooseFileBtn');
	  const fileInput = document.getElementById('fileInput');
	  const previewContainer = document.getElementById('previewContainer');
	  const submitBtn = document.getElementById('submitBtn');
	  const dropZone = document.getElementById('dropZone');
	  const uploadStatus = document.getElementById('uploadStatus');
	  const successCheck = document.getElementById('successCheck');
	
	  // Open modal when clicking upload button
	  document.querySelector('.upload-btn').addEventListener('click', () => {
	    modal.style.display = 'block';
	    document.body.style.overflow = 'hidden';
	  });
	
	  // Close modal handlers
	  document.querySelectorAll('.close-modal, .cancel-btn').forEach(btn => {
	    btn.addEventListener('click', closeModal);
	  });
	
	  // Handle file selection
	
	  // Handle file input change
	  fileInput.addEventListener('change', handleFileSelect);
	

	
	  function handleFileSelect() {
	    const file = fileInput.files[0];
	    if (!file) return;
	
	    // Validate file
	    if (!file.type.startsWith('image/')) {
	      uploadStatus.innerHTML = '⚠️ Please select an image file';
	      submitBtn.disabled = true;
	      return;
	    }
	
	    if (file.size > 2 * 1024 * 1024) {
	      uploadStatus.innerHTML = '⚠️ File size exceeds 2MB limit';
	      submitBtn.disabled = true;
	      return;
	    }
		
		document.getElementById("uploadContent").style.transform="translateY(0vh)";
		
	    // Preview image
	    const reader = new FileReader();
	    reader.onload = (e) => {
	      previewContainer.innerHTML = `
	        <img src="${e.target.result}" class="preview-image" alt="Payment proof preview">
	      `;
	      submitBtn.disabled = false;
	      uploadStatus.innerHTML = '✅ File ready for upload';
	    };
	    reader.readAsDataURL(file);
	  }

	  function closeModal() {
	    modal.style.display = 'none';
	    document.getElementById("uploadContent").style.transform="translateY(15vh)";
	    document.body.style.overflow = 'auto';
	    // Reset form
	    fileInput.value = '';
	    previewContainer.innerHTML = '';
	    submitBtn.disabled = true;
	    uploadStatus.innerHTML = '';
	  }
	
	  // Close modal when clicking outside
	  window.onclick = (e) => {
	    if (e.target === modal) closeModal();
	  }
	    // File upload feedback animation
	
	  </script>
	</body>
</html>