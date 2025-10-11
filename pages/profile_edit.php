<?php
require_once __DIR__ . '/../src/functions.php';

check_auth(); // Ensure user is logged in

$loggedInUser = $_SESSION['user'];
$userId = $loggedInUser['id'];

$fullname = $loggedInUser['fullname'];
$email = $loggedInUser['email'];
$phone = $loggedInUser['phone'];

$profile_errors = [];
$profile_success_message = '';
$password_errors = [];
$password_success_message = '';
$pin_errors = [];
$pin_success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Full Name and Phone Number
    $newFullname = trim($_POST['fullname'] ?? '');
    $newPhone = trim($_POST['phone'] ?? '');

    if (empty($newFullname)) {
        $profile_errors[] = "Full name cannot be empty.";
    }
    if (empty($newPhone)) {
        $profile_errors[] = "Phone number cannot be empty.";
    }

    if (empty($profile_errors)) {
        if (updateUserProfile($pdo, $userId, $newFullname, $newPhone)) {
            // Update session data immediately after successful update
            $_SESSION['user']['fullname'] = $newFullname;
            $_SESSION['user']['phone'] = $newPhone;
            $fullname = $newFullname;
            $phone = $newPhone;
            $profile_success_message = "Profile updated successfully.";
        } else {
            $profile_errors[] = "Failed to update profile.";
        }
    }

    // Password Change
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if (!empty($oldPassword) || !empty($newPassword)) {
        if (empty($oldPassword)) {
            $password_errors[] = "Old password is required to change password.";
        }
        if (empty($newPassword)) {
            $password_errors[] = "New password cannot be empty.";
        }
        if (strlen($newPassword) < 8) {
            $password_errors[] = "New password must be at least 8 characters long.";
        }

        if (empty($password_errors)) {
            if (updateUserPassword($pdo, $userId, $oldPassword, $newPassword)) {
                $password_success_message = "Password updated successfully.";
            } else {
                $password_errors[] = "Failed to update password. Check your old password.";
            }
        }
    }

    // Transaction PIN Change
    $newPin = $_POST['new_pin'] ?? '';
    $confirmPin = $_POST['confirm_pin'] ?? '';
    $currentPasswordForPin = $_POST['current_password_for_pin'] ?? '';

    if (!empty($newPin) || !empty($confirmPin) || !empty($currentPasswordForPin)) {
        if (empty($newPin) || empty($confirmPin) || empty($currentPasswordForPin)) {
            $pin_errors[] = "All PIN fields and current password are required to set/change PIN.";
        }
        if ($newPin !== $confirmPin) {
            $pin_errors[] = "New PIN and confirm PIN do not match.";
        }
        if (!preg_match('/^\d{4}$/', $newPin)) {
            $pin_errors[] = "Transaction PIN must be a 4-digit number.";
        }

        if (empty($pin_errors)) {
            $pinResult = setTransactionPin($pdo, $userId, $newPin, $currentPasswordForPin);
            if ($pinResult['success']) {
                $pin_success_message = $pinResult['message'];
            } else {
                $pin_errors[] = $pinResult['message'];
            }
        }
    }
}

require_once __DIR__ . '/../assets/template/intro-template.php';
?>

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <style>
        /* Base and Custom Properties - Adapted to use existing theme variables */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            margin: 0;
        }

        /* Layout Container */
        .container {
            max-width: 512px;
            margin-left: auto;
            margin-right: auto;
            padding: 1rem;
        }

        /* Profile Picture Section */
        .profile-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 2rem; /* Adjusted for template */
        }
        
        .profile-image-wrapper {
            position: relative;
        }

        .profile-image {
            width: 8rem;
            height: 8rem;
            border-radius: 9999px;
            border: 4px solid var(--border-color);
            object-fit: cover;
        }
        
        .camera-button {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: var(--bg-secondary);
            border-radius: 9999px;
            padding: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .material-icons.camera-icon {
            color: var(--text-secondary);
        }

        .change-picture-text {
            margin-top: 0.5rem;
            color: var(--text-secondary);
        }

        /* Form Section */
        .form-container {
            padding: 2rem 1.5rem;
        }

        /* This selector applies margin to all direct children of .form-container except the first one */
        .form-container > div + div {
            margin-top: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-input {
            margin-top: 0.25rem;
            display: block;
            width: 100%;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            padding: 0.75rem 1rem;
            color: var(--text-primary);
            box-sizing: border-box; /* Ensures padding doesn't affect width */
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px var(--accent-color);
        }

        .form-input.readonly {
            background-color: var(--bg-tertiary);
            cursor: not-allowed;
            color: var(--text-secondary);
        }
        
        .password-section-title {
            margin-top: 2rem;
            margin-bottom: -0.5rem;
            font-weight: 500;
            color: var(--text-primary);
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
        }

        /* Update Button */
        .button-wrapper {
            padding-top: 1rem;
        }

        .update-button {
            width: 100%;
            background-color: var(--accent-color);
            color: var(--accent-text);
            font-weight: 700;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border: none;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .update-button:hover {
            opacity: 0.9;
        }

        .update-button:focus {
            outline: none;
            box-shadow: 0 0 0 2px var(--bg-secondary), 0 0 0 4px var(--accent-color);
        }
        .message.error {
            color: red;
            margin-bottom: 10px;
        }
        .message.success {
            color: green;
            margin-bottom: 10px;
        }
    </style>

    <div class="container">

        <div class="profile-section">
            <div class="profile-image-wrapper">
                <img alt="Company Logo" class="profile-image" src="assets/images/logo.png"/>
                <button class="camera-button">
                    <span class="material-icons camera-icon">photo_camera</span>
                </button>
            </div>
        </div>

        <form class="form-container" method="POST">
            <?php if (!empty($profile_errors)): ?>
                <?php foreach ($profile_errors as $error): ?>
                    <p class="message error"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (!empty($profile_success_message)): ?>
                <p class="message success"><?php echo htmlspecialchars($profile_success_message); ?></p>
            <?php endif; ?>

            <?php if (!empty($password_errors)): ?>
                <?php foreach ($password_errors as $error): ?>
                    <p class="message error"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (!empty($password_success_message)): ?>
                <p class="message success"><?php echo htmlspecialchars($password_success_message); ?></p>
            <?php endif; ?>

            <?php if (!empty($pin_errors)): ?>
                <?php foreach ($pin_errors as $error): ?>
                    <p class="message error"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (!empty($pin_success_message)): ?>
                <p class="message success"><?php echo htmlspecialchars($pin_success_message); ?></p>
            <?php endif; ?>

            <div>
                <label class="form-label" for="full_name">Full Name</label>
                <input class="form-input" id="full_name" name="fullname" type="text" value="<?php echo htmlspecialchars($fullname); ?>"/>
            </div>

            <div>
                <label class="form-label" for="email">Email ID</label>
                <input class="form-input readonly" id="email" name="email" type="email" value="<?php echo htmlspecialchars($email); ?>" readonly/>
            </div>

            <div>
                <label class="form-label" for="phone">Phone Number</label>
                <input class="form-input" id="phone" name="phone" type="tel" value="<?php echo htmlspecialchars($phone); ?>"/>
            </div>
            
            <div class="password-section">
                 <h3 class="password-section-title">Change Password</h3>
            </div>
            
            <div>
                <label class="form-label" for="old_password">Old Password</label>
                <input class="form-input" id="old_password" name="old_password" type="password" placeholder="Enter your current password"/>
            </div>

            <div>
                <label class="form-label" for="new_password">New Password</label>
                <input class="form-input" id="new_password" name="new_password" type="password" placeholder="Enter your new password"/>
            </div>

            <div class="password-section">
                 <h3 class="password-section-title">Set Transaction PIN</h3>
            </div>

            <div>
                <label class="form-label" for="new_pin">New Transaction PIN</label>
                <input class="form-input" id="new_pin" name="new_pin" type="password" placeholder="Enter a 4-digit PIN" maxlength="4" pattern="\d{4}" title="Please enter a 4-digit PIN"/>
            </div>

            <div>
                <label class="form-label" for="confirm_pin">Confirm Transaction PIN</label>
                <input class="form-input" id="confirm_pin" name="confirm_pin" type="password" placeholder="Confirm your 4-digit PIN" maxlength="4" pattern="\d{4}" title="Please confirm your 4-digit PIN"/>
            </div>

            <div>
                <label class="form-label" for="current_password_for_pin">Current Password (to confirm PIN change)</label>
                <input class="form-input" id="current_password_for_pin" name="current_password_for_pin" type="password" placeholder="Enter your current password"/>
            </div>

            <div class="button-wrapper">
                <button class="update-button" type="submit">
                    Update Profile
                </button>
            </div>
        </form>
    </div>

<?php
require_once __DIR__ . '/../assets/template/end-template.php';
?>