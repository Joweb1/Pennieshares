<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/kyc_functions.php';

$kyc_status = getKycStatus($pdo, $user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_step1'])) {
        submitKycStep1($pdo, $user['id'], $_POST['fullName'], $_POST['dob'], $_POST['address'], $_POST['state'], $_POST['bvn'], $_POST['nin']);
        header('Location: /kyc?step=2');
        exit;
    } elseif (isset($_POST['submit_step2'])) {
        submitKycStep2($pdo, $user['id'], $_FILES);
        header('Location: /kyc?step=3');
        exit;
    }
}

$current_step = $_GET['step'] ?? 1;
if ($kyc_status) {
    if ($kyc_status['status'] == 'verified') {
        $current_step = 3;
    } elseif ($kyc_status['status'] == 'rejected') {
        $current_step = 1;
    }
}

require_once __DIR__ . '/../assets/template/intro-template.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>KYC Verification</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin="" />
    <link
      rel="stylesheet"
      as="style"
      onload="this.rel='stylesheet'"
      href="https://fonts.googleapis.com/css2?display=swap&family=Inter%3Awght%40400%3B500%3B700%3B900&family=Noto+Sans%3Awght%40400%3B500%3B700%3B900"
    />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        /* CSS Variables for Theming */
        :root {
            --bg-primary-light: #f4f7fa;
            --bg-secondary-light: #ffffff;
            --bg-tertiary-light: #e9eef2;
            --text-primary-light: #111418;
            --text-secondary-light: #5a6470;
            --border-color-light: #dde3e9;
            --accent-color-light: #0c7ff2;
            --accent-text-light: #ffffff;

            /* Dark Theme Variables */
            --bg-primary-dark: #111418;
            --bg-secondary-dark: #1b2127;
            --bg-tertiary-dark: #283039;
            --text-primary-dark: #ffffff;
            --text-secondary-dark: #9cabba;
            --border-color-dark: #3b4754;
            --accent-color-dark: #0c7ff2;
            --accent-text-dark: #ffffff;

            /* Default to Light Theme */
            --bg-primary: var(--bg-primary-light);
            --bg-secondary: var(--bg-secondary-light);
            --bg-tertiary: var(--bg-tertiary-light);
            --text-primary: var(--text-primary-light);
            --text-secondary: var(--text-secondary-light);
            --border-color: var(--border-color-light);
            --accent-color: var(--accent-color-light);
            --accent-text: var(--accent-text-light);
        }

        html[data-theme="dark"] {
            --bg-primary: var(--bg-primary-dark);
            --bg-secondary: var(--bg-secondary-dark);
            --bg-tertiary: var(--bg-tertiary-dark);
            --text-primary: var(--text-primary-dark);
            --text-secondary: var(--text-secondary-dark);
            --border-color: var(--border-color-dark);
            --accent-color: var(--accent-color-dark);
            --accent-text: var(--accent-text-dark);
        }

        body {
            font-family: Inter, "Noto Sans", sans-serif;
            background-color: var(--bg-primary); /* Use theme variable */
            color: var(--text-primary); /* Use theme variable */
            margin: 0;
            transition: background-color 0.3s, color 0.3s;
        }
        .container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .main-content {
            padding: 20px 5%; /* Reduced padding */
            display: flex;
            flex: 1;
            justify-content: center;
        }
        .content-container {
            display: flex;
            flex-direction: column;
            max-width: 960px;
            flex: 1;
        }
        .page-title {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 12px;
            padding: 16px;
        }
        .page-title-text {
            color: var(--text-primary); /* Use theme variable */
            letter-spacing: 0.01em; /* tracking-light */
            font-size: 2rem; /* text-[32px] */
            font-weight: 700;
            line-height: 1.2;
            min-width: 288px;
        }
        .progress-section {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 16px;
        }
        .progress-header {
            display: flex;
            gap: 24px;
            justify-content: space-between;
        }
        .progress-text {
            color: var(--text-primary); /* Use theme variable */
            font-size: 1rem;
            font-weight: 500;
            line-height: 1.5;
        }
        .progress-bar-bg {
            border-radius: 0.25rem;
            background-color: var(--border-color); /* Use theme variable */
        }
        .progress-bar {
            height: 8px;
            border-radius: 0.25rem;
            background-color: var(--accent-color); /* Use theme variable */
        }
        .progress-steps {
            color: var(--text-secondary); /* Use theme variable */
            font-size: 0.875rem;
            font-weight: 400;
            line-height: 1.5;
        }
        .section-title {
            color: var(--text-primary); /* Use theme variable */
            font-size: 1.125rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.015em;
            padding: 16px 16px 8px;
        }
        .card {
            padding: 16px;
            background-color: var(--bg-secondary); /* Use theme variable */
            border-radius: 0.5rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05); /* Subtle shadow */
            margin-bottom: 1rem;
        }
        .card-inner {
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            gap: 16px;
            border-radius: 0.5rem;
        }
        .card-content {
            display: flex;
            flex: 2 1 0px;
            flex-direction: column;
            gap: 16px;
        }
        .card-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .card-title {
            color: var(--text-primary); /* Use theme variable */
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .card-description {
            color: var(--text-secondary); /* Use theme variable */
            font-size: 0.875rem;
            font-weight: 400;
            line-height: 1.5;
        }
        /* Removed .upload-button as it's replaced by .upload-area */
        .card-image {
            width: 100%;
            background-position: center;
            background-repeat: no-repeat;
            aspect-ratio: 16 / 9;
            background-size: cover;
            border-radius: 0.5rem;
            flex: 1;
        }
        .footer-buttons {
            display: flex;
            padding: 12px 16px;
            justify-content: flex-end;
            gap: 12px;
        }
        .submit-button {
            display: flex;
            min-width: 84px;
            max-width: 480px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 0.5rem;
            height: 40px;
            padding: 0 16px;
            background-color: var(--accent-color); /* Use theme variable */
            color: var(--accent-text); /* Use theme variable */
            font-size: 0.875rem;
            font-weight: 700;
            line-height: 1.5;
            letter-spacing: 0.015em;
            border: none;
        }
        .submit-button:hover {
            opacity: 0.9;
        }
        .submit-button span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary); /* Use theme variable */
            font-size: 1rem;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 0.5rem;
            border-radius: 0.25rem;
            border: 1px solid var(--border-color); /* Use theme variable */
            background-color: var(--bg-tertiary); /* Use theme variable */
            color: var(--text-primary); /* Use theme variable */
        }
        .verification-item {
            display: flex;
            align-items: center;
            gap: 16px;
            background-color: var(--bg-secondary); /* Use theme variable */
            padding: 8px 16px;
            min-height: 72px;
            justify-content: space-between;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .verification-item-content {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .verification-icon-container {
            color: var(--text-primary); /* Use theme variable */
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            background-color: var(--bg-tertiary); /* Use theme variable */
            flex-shrink: 0;
            width: 48px;
            height: 48px;
        }
        .verification-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .verification-title {
            color: var(--text-primary); /* Use theme variable */
            font-size: 1rem;
            font-weight: 500;
            line-height: 1.5;
            overflow: hidden;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 1;
        }
        .verification-status {
            color: var(--text-secondary); /* Use theme variable */
            font-size: 0.875rem;
            font-weight: 400;
            line-height: 1.5;
            overflow: hidden;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }
        .status-indicator-container {
            flex-shrink: 0;
        }
        .status-indicator {
            display: flex;
            width: 28px;
            height: 28px;
            align-items: center;
            justify-content: center;
        }
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 9999px;
            /* background-color set dynamically */
        }
        .info-text {
            color: var(--text-primary); /* Use theme variable */
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            padding: 4px 16px 12px;
        }
        
        .hidden {
            display:none;
        }
        /* New styles for upload section */
        .upload-area {
            border: 2px dashed var(--border-color); /* Use theme variable */
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s;
            position: relative; /* For absolute positioning of input */
            overflow: hidden; /* Hide default input */
        }
        .upload-area:hover {
            border-color: var(--accent-color); /* Use theme variable */
        }
        .upload-area-icon {
            font-size: 3rem;
            color: var(--text-secondary); /* Use theme variable */
        }
        .upload-area-text {
            color: var(--text-secondary); /* Use theme variable */
            font-weight: 500;
        }
        .upload-area input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .upload-preview {
            margin-top: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
        }
        .upload-preview-item {
            position: relative;
            width: 100px;
            height: 100px;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .upload-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 0.5rem;
        }
        .upload-preview-item .remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: #ef4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="main-content">
            <div class="content-container">
                <div class="page-title">
                    <p class="page-title-text">KYC Verification</p>
                </div>

                <!-- Step 1: Personal Info Form -->
                <form method="POST" id="kycFormStep1" class="<?php echo $current_step == 1 ? '' : 'hidden'; ?>">
                    <input type="hidden" name="submit_step1" value="1">
                    <div class="progress-section">
                        <div class="progress-header">
                            <p class="progress-text">KYC Progress</p>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar" style="width: 33%;"></div>
                        </div>
                        <p class="progress-steps">1/3 steps completed</p>
                    </div>
                    <h3 class="section-title">Personal Information</h3>
                    <div class="card">
                        <div class="form-group">
                            <label for="fullName">Full Name</label>
                            <input type="text" id="fullName" name="fullName" value="<?php echo htmlspecialchars($kyc_status['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($kyc_status['dob'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($kyc_status['address'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="state">State</label>
                            <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($kyc_status['state'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="bvn">BVN</label>
                            <input type="text" id="bvn" name="bvn" value="<?php echo htmlspecialchars($kyc_status['bvn'] ?? ''); ?>" required minlength="11" maxlength="11">
                        </div>
                        <div class="form-group">
                            <label for="nin">NIN</label>
                            <input type="text" id="nin" name="nin" value="<?php echo htmlspecialchars($kyc_status['nin'] ?? ''); ?>" required minlength="11" maxlength="11">
                        </div>
                    </div>
                    <div class="footer-buttons">
                        <button type="submit" class="submit-button">
                            <span class="truncate">Next</span>
                        </button>
                    </div>
                </form>

                <!-- Step 2: Document Upload Form -->
                <form method="POST" enctype="multipart/form-data" id="kycFormStep2" class="<?php echo $current_step == 2 ? '' : 'hidden'; ?>">
                    <input type="hidden" name="submit_step2" value="1">
                    <div class="progress-section">
                        <div class="progress-header">
                            <p class="progress-text">KYC Progress</p>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar" style="width: 66%;"></div>
                        </div>
                        <p class="progress-steps">2/3 steps completed</p>
                    </div>
                    <h3 class="section-title">Identity Verification</h3>
                    <div class="card">
                        <div class="upload-area" id="identity-document-upload-area">
                            <span class="material-icons-outlined upload-area-icon">add_photo_alternate</span>
                            <p class="upload-area-text">Upload Passport or National ID</p>
                            <input type="file" name="identity_document" id="identity-document-upload" class="hidden" accept="image/*,application/pdf">
                        </div>
                        <div class="upload-preview" id="identity-document-preview"></div>
                    </div>
                    <h3 class="section-title">Address Verification</h3>
                    <div class="card">
                        <div class="upload-area" id="proof-of-address-upload-area">
                            <span class="material-icons-outlined upload-area-icon">add_photo_alternate</span>
                            <p class="upload-area-text">Upload Proof of Address</p>
                            <input type="file" name="proof_of_address" id="proof-of-address-upload" class="hidden" accept="image/*,application/pdf">
                        </div>
                        <div class="upload-preview" id="proof-of-address-preview"></div>
                    </div>
                    <h3 class="section-title">Selfie Verification</h3>
                    <div class="card">
                        <div class="upload-area" id="selfie-upload-area">
                            <span class="material-icons-outlined upload-area-icon">add_photo_alternate</span>
                            <p class="upload-area-text">Upload Selfie</p>
                            <input type="file" name="selfie" id="selfie-upload" class="hidden" accept="image/*,application/pdf">
                        </div>
                        <div class="upload-preview" id="selfie-preview"></div>
                    </div>
                    <div class="footer-buttons">
                        <button type="button" class="submit-button" onclick="showStep(1)">
                            <span class="truncate">Back</span>
                        </button>
                        <button type="submit" class="submit-button">
                            <span class="truncate">Submit for Verification</span>
                        </button>
                    </div>
                </form>

                <!-- Step 3: Verification Status -->
                <div id="step3" class="<?php echo $current_step == 3 ? '' : 'hidden'; ?>">
                    <div class="progress-section">
                        <div class="progress-header">
                            <p class="progress-text">Step 3 of 3</p>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar" style="width: 100%;"></div>
                        </div>
                    </div>
                    <h3 class="section-title">Identity Verification</h3>
                    <div class="verification-item">
                        <div class="verification-item-content">
                            <div class="verification-icon-container">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" fill="currentColor" viewBox="0 0 256 256">
                                    <path d="M200,112a8,8,0,0,1-8,8H152a8,8,0,0,1,0-16h40A8,8,0,0,1,200,112Zm-8,24H152a8,8,0,0,0,0,16h40a8,8,0,0,0,0-16Zm40-80V200a16,16,0,0,1-16,16H40a16,16,0,0,1-16-16V56A16,16,0,0,1,40,40H216A16,16,0,0,1,232,56ZM216,200V56H40V200H216Zm-80.26-34a8,8,0,1,1-15.5,4c-2.63-10.26-13.06-18-24.25-18s-21.61,7.74-24.25,18a8,8,0,1,1-15.5-4,39.84,39.84,0,0,1,17.19-23.34,32,32,0,1,1,45.12,0A39.76,39.76,0,0,1,135.75,166ZM96,136a16,16,0,1,0-16-16A16,16,0,0,0,96,136Z"></path>
                                </svg>
                            </div>
                            <div class="verification-text">
                                <p class="verification-title">ID</p>
                                <p class="verification-status"><?php echo htmlspecialchars(ucfirst($kyc_status['status'] ?? 'pending')); ?></p>
                            </div>
                        </div>
                        <div class="status-indicator-container">
                            <div class="status-indicator">
                                <div class="status-dot" style="background-color: <?php echo $kyc_status['status'] == 'verified' ? '#078838' : ($kyc_status['status'] == 'rejected' ? '#ef4444' : '#ffc107'); ?>;"></div>
                            </div>
                        </div>
                    </div>
                    <p class="info-text">Your identity has been successfully verified. You can now proceed to explore investment opportunities and manage your portfolio.</p>
                    <div class="footer-buttons">
                        <button class="submit-button" onclick="showStep(2)">
                            <span class="truncate">Back</span>
                        </button>
                        <button class="submit-button" onclick="window.location.href='/wallet'">
                            <span class="truncate">Go to Wallet</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showStep(step) {
            const kycFormStep1 = document.getElementById('kycFormStep1');
            const kycFormStep2 = document.getElementById('kycFormStep2');
            const step3 = document.getElementById('step3');

            if (kycFormStep1) kycFormStep1.classList.add('hidden');
            if (kycFormStep2) kycFormStep2.classList.add('hidden');
            if (step3) step3.classList.add('hidden');

            if (step === 1) {
                if (kycFormStep1) kycFormStep1.classList.remove('hidden');
            } else if (step === 2) {
                if (kycFormStep2) kycFormStep2.classList.remove('hidden');
            } else if (step === 3) {
                if (step3) step3.classList.remove('hidden');
            }
        }

        function nextStep(step) {
            if (validateStep1()) {
                showStep(step);
            }
        }

        function prevStep(step) {
            showStep(step);
        }

        function validateStep1() {
            let isValid = true;
            const requiredFields = ['fullName', 'dob', 'address', 'state', 'bvn', 'nin'];
            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                if (!input.value) {
                    isValid = false;
                    alert(field + ' is required');
                }
            });

            const bvn = document.getElementById('bvn');
            if (bvn.value.length !== 11) {
                isValid = false;
                alert('BVN must be 11 digits');
            }

            const nin = document.getElementById('nin');
            if (nin.value.length !== 11) {
                isValid = false;
                alert('NIN must be 11 digits');
            }

            return isValid;
        }

        document.getElementById('kycFormStep1').addEventListener('submit', function(e) {
            if (!validateStep1()) {
                e.preventDefault();
            }
        });

        // Set the initial step based on PHP variable
        document.addEventListener('DOMContentLoaded', function() {
            showStep(<?php echo $current_step; ?>);

            // File upload preview setup
            function setupUploadPreview(uploadAreaId, uploadInputId, previewContainerId, initialFilePath = null) {
                const uploadArea = document.getElementById(uploadAreaId);
                const uploadInput = document.getElementById(uploadInputId);
                const previewContainer = document.getElementById(previewContainerId);

                // Clear existing previews
                previewContainer.innerHTML = '';

                // Display initial file if it exists
                if (initialFilePath) {
                    const previewItem = document.createElement('div');
                    previewItem.className = 'upload-preview-item';
                    previewItem.innerHTML = `<img src="/${initialFilePath}" alt="Uploaded Document"><button class="remove-btn" data-file-name="${initialFilePath}">&times;</button>`;
                    previewContainer.appendChild(previewItem);
                }

                uploadArea.addEventListener('click', () => uploadInput.click());

                uploadInput.addEventListener('change', () => {
                    previewContainer.innerHTML = ''; // Clear previous previews when new files are selected
                    for (const file of uploadInput.files) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const previewItem = document.createElement('div');
                            previewItem.className = 'upload-preview-item';
                            previewItem.innerHTML = `<img src="${e.target.result}" alt="${file.name}"><button class="remove-btn" data-file-name="${file.name}">&times;</button>`;
                            previewContainer.appendChild(previewItem);
                        };
                        reader.readAsDataURL(file);
                    }
                });

                previewContainer.addEventListener('click', (e) => {
                    if (e.target.classList.contains('remove-btn')) {
                        const fileName = e.target.dataset.fileName;
                        // Create a new FileList without the removed file
                        const newFiles = new DataTransfer();
                        for (const file of uploadInput.files) {
                            if (file.name !== fileName) {
                                newFiles.items.add(file);
                            }
                        }
                        uploadInput.files = newFiles.files;
                        e.target.parentElement.remove();
                    }
                });
            }

            // Initialize upload previews with existing data
            setupUploadPreview('identity-document-upload-area', 'identity-document-upload', 'identity-document-preview', '<?php echo $kyc_status['passport_path'] ?? $kyc_status['national_id_path'] ?? ''; ?>');
            setupUploadPreview('proof-of-address-upload-area', 'proof-of-address-upload', 'proof-of-address-preview', '<?php echo $kyc_status['proof_of_address_path'] ?? ''; ?>');
            setupUploadPreview('selfie-upload-area', 'selfie-upload', 'selfie-preview', '<?php echo $kyc_status['selfie_path'] ?? ''; ?>');
        });
    </script>
</body>
</html>
<?php
require_once __DIR__ . '/../assets/template/end-template.php';
?>
