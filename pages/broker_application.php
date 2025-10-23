<?php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/kyc_functions.php';

$kyc_status = getKycStatus($pdo, $user['id']);
if (!$kyc_status || $kyc_status['status'] !== 'verified') {
    $_SESSION['show_kyc_popup'] = true;
    header('Location: /wallet');
    exit;
}

require_once __DIR__ . '/../src/functions.php';
check_auth(); // Ensure user is logged in

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $formData = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'phone_number' => trim($_POST['phone_number'] ?? ''),
        'current_address' => trim($_POST['current_address'] ?? ''),
        'nationality' => trim($_POST['nationality'] ?? ''),
        'employment_status' => trim($_POST['employment_status'] ?? ''),
        'tech_knowledge' => trim($_POST['tech_knowledge'] ?? ''),
        'laptop_access' => trim($_POST['laptop_access'] ?? ''),
        'smartphone_access' => trim($_POST['smartphone_access'] ?? ''),
        'about_self' => trim($_POST['about_self'] ?? ''),
        'why_broker' => trim($_POST['why_broker'] ?? '')
    ];

    // Simple validation
    if (in_array('', $formData, true)) {
        $error_message = "Please fill out all fields.";
    } else {
        // Send emails
        $email_sent = sendBrokerApplicationEmails($pdo, $user, $formData);

        if ($email_sent) {
            $success_message = "Application submitted successfully! We'll review your information and get back to you soon.";
        } else {
            $error_message = "There was an error submitting your application. Please try again later.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Pennieshares Broker Application</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght @400;500;700&amp;display=swap" rel="stylesheet"/>
<style>
    :root {
      --primary-color: #3498db; /* Changed to blue */
      --background-color: #ffffff;
      --text-primary: #333333;
      --text-secondary: #666666;
      --accent-color: #f0f0f0;
      --success-color: #2ecc71;
      --divider-color: #e0e0e0;
      --error-color: #e74c3c;
    }
    
    * {
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--background-color);
      color: var(--text-primary);
      margin: 0;
      padding: 0;
      min-height: 100vh;
      line-height: 1.5;
    }
    
    .shadow-soft {
      box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    }
    
    .container {
      max-width: 768px;
      margin: 0 auto;
      width: 100%;
    }
    
    /* Header Styles */
    header {
      padding: 1rem 4rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--divider-color);
    }
    
    header img {
      height: 2rem;
    }
    
    header h1 {
      font-size: 1.25rem;
      font-weight: 600;
      margin: 0;
    }
    
    /* Main Content Styles */
    main {
      padding: 1rem 4rem;
    }
    
    @media (min-width: 640px) {
      main {
        padding: 1.5rem 6rem;
      }
    }
    
    /* Form Styles */
    form {
      width: 100%;
    }
    
    .form-section {
      margin-bottom: 2rem;
    }
    
    .section-title {
      font-size: 1.125rem;
      font-weight: 500;
      margin-bottom: 1rem;
    }
    
    .form-field {
      margin-bottom: 1rem;
    }
    
    .form-label {
      display: block;
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--text-primary);
      margin-bottom: 0.25rem;
    }
    
    .input-container {
      position: relative;
      border-radius: 0.375rem;
    }
    
    .input-icon {
      position: absolute;
      top: 0;
      bottom: 0;
      left: 0;
      padding-left: 0.75rem;
      display: flex;
      align-items: center;
      pointer-events: none;
      color: #9ca3af;
    }
    
    .input-icon svg {
      width: 1.25rem;
      height: 1.25rem;
    }
    
    .form-input, .form-select, .form-textarea {
      width: 100%;
      border: 1px solid #d1d5db;
      border-radius: 0.375rem;
      padding: 0.5rem 0.75rem;
      font-size: 1rem;
      font-family: inherit;
      transition: all 0.2s ease;
    }
    
    .form-input:focus, .form-select:focus, .form-textarea:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2); /* Blue shadow */
    }
    
    .form-input.with-icon, .form-select.with-icon {
      padding-left: 2.5rem;
    }
    
    .form-textarea.with-icon {
      padding-left: 2.5rem;
      padding-top: 0.65rem;
    }
    
    .form-textarea {
      min-height: 100px;
      resize: vertical;
    }
    
    /* Radio Button Styles */
    .radio-group {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
    }
    
    .radio-label {
      display: flex;
      align-items: center;
      cursor: pointer;
    }
    
    .radio-input {
      margin-right: 0.5rem;
      width: 1rem;
      height: 1rem;
      accent-color: var(--primary-color);
    }
    
    /* Divider */
    .section-divider {
      border-top: 1px solid var(--divider-color);
      margin: 1.5rem 0;
    }
    
    /* Button Styles */
    .button-container {
      margin-top: 2rem;
      text-align: right;
    }
    
    .button-primary {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 0.375rem;
      font-size: 1rem;
      font-weight: 500;
      color: white;
      background-color: var(--primary-color);
      cursor: pointer;
      transition: background-color 0.2s ease;
      box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
      position: relative;
    }
    
    .button-primary:hover {
      background-color: #2980b9; /* Darker blue */
    }

    .button-primary .button-text {
        transition: opacity 0.2s;
    }

    .button-primary .loader {
        position: absolute;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #3498db;
        border-radius: 50%;
        width: 1.2rem;
        height: 1.2rem;
        animation: spin 1s linear infinite;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .button--loading .button-text {
        opacity: 0;
    }

    .button--loading .loader {
        opacity: 1;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Footer Styles */
    footer {
      text-align: center;
      padding: 1rem;
      font-size: 0.75rem;
      color: var(--text-secondary);
    }
    
    /* Message Styles */
    .message {
      padding: 1rem;
      border-radius: 0.375rem;
      margin-bottom: 1.5rem;
      text-align: center;
    }
    .success-message {
      background-color: #d4edda;
      color: #155724;
    }
    .error-message-top {
      background-color: #f8d7da;
      color: #721c24;
    }
    
    /* Responsive adjustments */
    @media (max-width: 640px) {
      .radio-group {
        flex-direction: column;
        gap: 0.5rem;
      }
      
      header {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
        padding: 1rem;
      }

      main {
        padding: 2.5rem;
      }
      
      .button-container {
        text-align: center;
      }
      
      .button-primary {
        width: 100%;
      }
    }
</style>
</head>
<body>
<div class="container">
  <header>
    <img alt="Pennieshares Logo" src="<?= BASE_URL ?>/assets/images/logo.png"/>
    <h1>Broker Application Form</h1>
    <div></div>
  </header>
  
  <main>
    <?php if ($success_message): ?>
        <div class="message success-message"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="message error-message-top"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form id="brokerForm" method="POST">
      <div class="form-sections">
        <div class="form-section">
          <h2 class="section-title">Personal Information</h2>
          <div class="form-fields">
            <div class="form-field">
              <label class="form-label" for="full_name">Full Legal Name</label>
              <div class="input-container">
                <input class="form-input" id="full_name" name="full_name" placeholder="James Scott" type="text" required/>
              </div>
            </div>
            
            <div class="form-field">
              <label class="form-label" for="phone_number">Phone Number</label>
              <div class="input-container">
                <input class="form-input" id="phone_number" name="phone_number" placeholder="(+234) 0906-7890-567" type="tel" required/>
              </div>
            </div>
            
            <div class="form-field">
              <label class="form-label" for="current_address">Current Address</label>
              <div class="input-container">
                <textarea class="form-textarea" id="current_address" name="current_address" placeholder="123 Main St, Anytown, Nigeria" required></textarea>
              </div>
            </div>
            
            <div class="form-field">
              <label class="form-label" for="nationality">Nationality</label>
              <div class="input-container">
                <select class="form-select" id="nationality" name="nationality" required>
                  <option value="">Select your nationality</option>
                  <option value="Nigerian">Nigerian</option>
                  <option value="Ghanian">Ghanian</option>
                  <option value="South Africa">South Africa</option>
                  <option value="Other">Other</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        
        <div class="section-divider"></div>
        
        <div class="form-section">
          <h2 class="section-title">Employment</h2>
          <div class="form-fields">
            <div class="form-field">
              <label class="form-label" for="employment_status">Employment Status</label>
              <select class="form-select" id="employment_status" name="employment_status" required>
                <option value="">Select your employment status</option>
                <option value="Employed">Employed</option>
                <option value="Self-employed">Self-employed</option>
                <option value="Unemployed">Unemployed</option>
                <option value="Student">Student</option>
                <option value="Retired">Retired</option>
              </select>
            </div>
          </div>
        </div>
        
        <div class="section-divider"></div>
        
        <div class="form-section">
          <h2 class="section-title">Tech & Internet Savviness</h2>
          <div class="form-fields">
            <div class="form-field">
              <p class="form-label">Do you have tech knowledge?</p>
              <div class="radio-group">
                <label class="radio-label">
                  <input class="radio-input" name="tech_knowledge" type="radio" value="yes" required/>
                  <span>Yes</span>
                </label>
                <label class="radio-label">
                  <input class="radio-input" name="tech_knowledge" type="radio" value="no"/>
                  <span>No</span>
                </label>
              </div>
            </div>
            
            <div class="form-field">
              <p class="form-label">Do you have a laptop?</p>
              <div class="radio-group">
                <label class="radio-label">
                  <input class="radio-input" name="laptop_access" type="radio" value="yes" required/>
                  <span>Yes</span>
                </label>
                <label class="radio-label">
                  <input class="radio-input" name="laptop_access" type="radio" value="no"/>
                  <span>No</span>
                </label>
              </div>
            </div>
            
            <div class="form-field">
              <p class="form-label">Do you have a smartphone capable of fast internet connection?</p>
              <div class="radio-group">
                <label class="radio-label">
                  <input class="radio-input" name="smartphone_access" type="radio" value="yes" required/>
                  <span>Yes</span>
                </label>
                <label class="radio-label">
                  <input class="radio-input" name="smartphone_access" type="radio" value="no"/>
                  <span>No</span>
                </label>
              </div>
            </div>
          </div>
        </div>
        
        <div class="section-divider"></div>
        
        <div class="form-section">
          <h2 class="section-title">About You</h2>
          <div class="form-fields">
            <div class="form-field">
              <label class="form-label" for="about_self">Brief description about yourself</label>
              <textarea class="form-textarea" id="about_self" name="about_self" placeholder="Tell us a little bit about yourself..." rows="4" required></textarea>
            </div>
            
            <div class="form-field">
              <label class="form-label" for="why_broker">Why do you want to become a broker on Pennieshares?</label>
              <textarea class="form-textarea" id="why_broker" name="why_broker" placeholder="What motivates you to join us?" rows="4" required></textarea>
            </div>
          </div>
        </div>
      </div>
      
      <div class="button-container">
        <button class="button-primary" type="submit" id="submitBtn">
            <span class="button-text">Apply</span>
            <span class="loader"></span>
        </button>
      </div>
    </form>
  </main>
  
  <footer>
    <p>Your application draft is automatically saved.</p>
  </footer>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('brokerForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', function() {
        // Basic validation check
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
            }
        });

        if (isValid) {
            submitBtn.classList.add('button--loading');
            submitBtn.disabled = true;
        }
    });
});
</script>
</body>
</html>
