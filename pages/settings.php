<?php
require_once __DIR__ . '/../src/functions.php';
check_auth(); // Ensure user is logged in

// You might fetch user-specific settings here if needed
// $userSettings = getUserSettings($pdo, $_SESSION['user']['id']);

require_once __DIR__ . '/../assets/template/intro-template.php';
?>

<style>
      /* --- CSS Variables and Theme Setup --- */
      :root {
        /* Light Theme (Default) */
        --bg-primary: #f0f2f5;
        --bg-secondary: #ffffff;
        --bg-tertiary: #e4e6eb;
        --text-primary: #050505;
        --text-secondary: #65676b;
        --border-color: #ced0d4;
        --accent-color: #0c7ff2;
        --shadow-color: rgba(0, 0, 0, 0.1);
        --icon-color: #333;
      }

      body.dark-theme {
        /* Dark Theme */
        --bg-primary: #111418;
        --bg-secondary: #1e2228;
        --bg-tertiary: #283039;
        --text-primary: #e4e6eb;
        --text-secondary: #9cabba;
        --border-color: #283039;
        --accent-color: #0c7ff2;
        --shadow-color: rgba(0, 0, 0, 0.25);
        --icon-color: #e4e6eb;
      }

      /* --- Base & Reset Styles --- */
      *,
      *::before,
      *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      body {
        font-family: Inter, "Noto Sans", sans-serif;
        background-color: var(--bg-primary);
        color: var(--text-primary);
        min-height: 100vh;
        font-size: 16px;
        transition: background-color 0.3s ease, color 0.3s ease;
        overflow-x: hidden;
      }

      body.nav-open {
        overflow: hidden;
      }
      
      a {
        text-decoration: none;
        color: inherit;
      }

      button {
        font-family: inherit;
        background: none;
        border: none;
        cursor: pointer;
      }

      ul {
        list-style: none;
      }

      /* --- Layout Containers --- */
      .page-wrapper {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
      }

      .content-wrapper {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
      }
      
      .main-content {
        display: flex;
        justify-content: center;
        padding: 20px 160px;
        flex-grow: 1;
      }

      .settings-container {
        width: 100%;
        max-width: 600px;
      }
      
      /* --- Settings Page --- */
      .page-title {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 24px;
        color: var(--text-primary);
      }
      
      .settings-section-title {
        font-size: 18px;
        font-weight: 700;
        padding: 16px 0 8px;
        color: var(--text-primary);
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 8px;
      }
      
      .settings-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px;
        background-color: var(--bg-secondary);
        border-radius: 8px;
        margin-bottom: 12px;
        transition: background-color 0.3s ease;
      }

      .settings-card-content p:first-child {
        font-size: 16px;
        font-weight: 500;
        color: var(--text-primary);
        line-height: 1.4;
      }
      
      .settings-card-content p:last-child {
        font-size: 14px;
        color: var(--text-secondary);
        line-height: 1.4;
      }

      .settings-card .action-icon {
        color: var(--text-secondary);
      }
      
      .settings-card .action-icon svg {
        width: 24px;
        height: 24px;
      }

      /* Theme Toggle Switch */
      .theme-switch-wrapper {
        position: relative;
        display: flex;
        align-items: center;
      }
      
      .theme-switch {
        position: relative;
        display: inline-block;
        width: 51px;
        height: 31px;
      }
      
      .theme-switch input {
        opacity: 0;
        width: 0;
        height: 0;
      }
      
      .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: var(--bg-tertiary);
        transition: .4s;
        border-radius: 34px;
      }
      
      .slider:before {
        position: absolute;
        content: "";
        height: 23px;
        width: 23px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      }
      
      input:checked + .slider {
        background-color: var(--accent-color);
      }
      
      input:checked + .slider:before {
        transform: translateX(20px);
      }

      /* Logout Button */
      .logout-section {
        margin-top: 24px;
      }

      .logout-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background-color: var(--bg-tertiary);
        color: var(--text-primary);
        border-radius: 8px;
        font-weight: 700;
        font-size: 14px;
        transition: background-color 0.2s ease, color 0.2s ease;
      }

      .logout-button:hover {
        background-color: #d32f2f;
        color: white;
      }
      .logout-button svg {
        width: 18px;
        height: 18px;
      }

      /* --- Responsive Design --- */
      @media (max-width: 992px) {
        .main-content {
          padding: 20px 40px;
        }
      }

      @media (max-width: 768px) {
        .main-content {
          padding: 20px;
        }
      }
    </style>

<main>
    <div class="settings-container">
        <h1 class="page-title">Settings</h1>

        <div class="settings-section">
            <h2 class="settings-section-title">General</h2>
            <div class="settings-card">
                <div class="settings-card-content">
                    <p>Theme</p>
                    <p>Switch between light and dark themes</p>
                </div>
                <label class="theme-switch-wrapper">
                    <div class="theme-switch">
                        <input type="checkbox" id="theme-toggle-settings">
                        <span class="slider"></span>
                    </div>
                </label>
            </div>
        </div>

        <div class="settings-section">
            <h2 class="settings-section-title">Account</h2>
            <a href="profile_edit" class="settings-card">
                <div class="settings-card-content">
                    <p>Change Password</p>
                    <p>Update your account password</p>
                </div>
                <span class="action-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            </a>
            <a href="delete_account" class="settings-card">
                <div class="settings-card-content">
                    <p>Delete Account</p>
                    <p>Permanently delete your account and data</p>
                </div>
                <span class="action-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            </a>
        </div>

        <div class="logout-section">
            <a href="logout" class="logout-button">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H3a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Logout
            </a>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../assets/template/end-template.php';
?>