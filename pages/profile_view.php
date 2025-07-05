<?php
require_once __DIR__ . '/../src/functions.php';
check_auth(); // Ensure user is logged in

require_once __DIR__ . '/../assets/template/intro-template.php';
?>

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet" />
    <style>
        /* General Body Styles - Adapted to use existing theme variables */
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            margin: 0;
            transition: background-color 0.3s, color 0.3s;
        }

        /* Utility & Layout */
        a {
            text-decoration: none;
            color: inherit;
        }
        
        .profile-container {
            max-width: 24rem; /* 384px, Original: max-w-sm */
            margin-left: auto;
            margin-right: auto;
            padding: 1rem; /* 16px, Original: p-4 */
        }

        .profile-main {
            margin-top: 2rem; /* 32px, Original: mt-8 */
        }

        /* Profile Header */
        .profile-header {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .profile-picture-wrapper {
            position: relative;
        }

        .profile-picture {
            width: 8rem; /* 128px, Original: w-32 */
            height: 8rem; /* 128px, Original: h-32 */
            border-radius: 9999px; /* Original: rounded-full */
            object-fit: cover;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1); /* Original: shadow-lg */
        }

        .camera-button {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: var(--bg-secondary); /* Original: bg-white */
            padding: 0.5rem; /* 8px, Original: p-2 */
            border-radius: 9999px; /* Original: rounded-full */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1); /* Original: shadow-md */
            border: none;
            cursor: pointer;
        }
        
        html[data-theme="dark"] .camera-button {
            background-color: var(--bg-tertiary); /* Original: dark:bg-gray-700 */
        }
        
        .camera-button .material-icons-outlined {
            color: var(--text-secondary); /* Original: text-gray-600 */
        }

        html[data-theme="dark"] .camera-button .material-icons-outlined {
            color: var(--text-secondary); /* Original: dark:text-gray-300 */
        }

        /* Profile Menu */
        .profile-menu {
            margin-top: 3rem; /* 48px, Original: mt-12 */
            display: flex;
            flex-direction: column;
            gap: 1rem; /* Replaces space-y-4 */
        }

        .menu-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: var(--bg-secondary); /* Original: bg-white */
            padding: 1rem; /* 16px, Original: p-4 */
            border-radius: 0.75rem; /* 12px, Original: rounded-xl */
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* Original: shadow-sm */
            transition: background-color 150ms cubic-bezier(0.4, 0, 0.2, 1); /* Original: transition-colors */
        }
        
        .menu-item:hover {
            background-color: var(--bg-tertiary); /* Original: hover:bg-gray-100 */
        }
        
        html[data-theme="dark"] .menu-item {
            background-color: var(--bg-secondary); /* Original: dark:bg-gray-800 */
        }
        
        html[data-theme="dark"] .menu-item:hover {
            background-color: var(--bg-tertiary); /* Original: dark:hover:bg-gray-700 */
        }
        
        .menu-item-content {
            display: flex;
            align-items: center;
            gap: 1rem; /* Replaces space-x-4 */
        }

        .menu-icon {
            color: rgba(120,120,250,1); /* Original: text-orange-500 */
        }
        
        .menu-text {
            font-weight: 500; /* Original: font-medium */
            color: var(--text-primary); /* Original: text-gray-800 */
        }
        
        html[data-theme="dark"] .menu-text {
             color: var(--text-primary); /* Original: dark:text-gray-200 */
        }

        .menu-chevron {
            color: var(--text-secondary); /* Original: text-gray-400 */
        }
        
        html[data-theme="dark"] .menu-chevron {
             color: var(--text-secondary); /* Original: dark:text-gray-500 */
        }

        /* Bottom Bar - Not used with current template structure, but keeping for reference */
        .bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            max-width: 24rem; /* 384px, Original: max-w-sm */
            margin-left: auto;
            margin-right: auto;
            padding: 1rem; /* 16px, Original: p-4 */
            background-color: var(--bg-primary); /* Original: bg-gray-50 */
        }
        
        html[data-theme="dark"] .bottom-bar {
            background-color: var(--bg-primary); /* Original: dark:bg-gray-900 */
        }

        .bottom-bar-handle {
            width: 8rem; /* 128px, Original: w-32 */
            height: 0.375rem; /* 6px, Original: h-1.5 */
            background-color: var(--border-color); /* Original: bg-gray-300 */
            border-radius: 9999px; /* Original: rounded-full */
            margin-left: auto;
            margin-right: auto;
        }
        
        html[data-theme="dark"] .bottom-bar-handle {
            background-color: var(--border-color); /* Original: dark:bg-gray-600 */
        }
    </style>

    <div class="profile-container">
        <main class="profile-main">
            <div class="profile-header">
                <div class="profile-picture-wrapper">
                    <img alt="Profile picture of a man wearing a cap" class="profile-picture" src="https://lh3.googleusercontent.com/aida-public/AB6AXuC-4RPOc6HNDAFhk_LyIy-p2fSrbKXD0aYO2mINMFUXtrD_yVkQ9iNA6_NNEBlSbRJe3adn6t5xMCfqiBySHBHUmR3cMIchTGSUvbGs1Eem3fZtz2ou-E1xRTcGwF2rD82I3s4pTQIBatm2UKl00vf0Xz_ebifLCuyoTkDWQzDn1m6QjPADKt3UFJududA247DLw1un-BJKCNnZ_6iTcqV3T_V5NJrA7N4SPCRRWvEEnDzahaZI_NFsGivv4_gktKFP8FPt-WGQfGo" />
                    <button class="camera-button">
                        <span class="material-icons-outlined">photo_camera</span>
                    </button>
                </div>
            </div>

            <div class="profile-menu">
                <a class="menu-item" href="profile_edit">
                    <div class="menu-item-content">
                        <span class="material-icons-outlined menu-icon">person_outline</span>
                        <span class="menu-text">Edit profile</span>
                    </div>
                    <span class="material-icons-outlined menu-chevron">chevron_right</span>
                </a>
                <a class="menu-item" href="transactions">
                    <div class="menu-item-content">
                        <span class="material-icons-outlined menu-icon">notifications_none</span>
                        <span class="menu-text">Notifications</span>
                    </div>
                    <span class="material-icons-outlined menu-chevron">chevron_right</span>
                </a>
                <a class="menu-item" href="settings">
                    <div class="menu-item-content">
                        <span class="material-icons-outlined menu-icon">settings</span>
                        <span class="menu-text">Settings</span>
                    </div>
                    <span class="material-icons-outlined menu-chevron">chevron_right</span>
                </a>
                <a class="menu-item" href="#">
                    <div class="menu-item-content">
                        <span class="material-icons-outlined menu-icon">help_outline</span>
                        <span class="menu-text">Help Center</span>
                    </div>
                    <span class="material-icons-outlined menu-chevron">chevron_right</span>
                </a>
                <a class="menu-item" href="logout">
                    <div class="menu-item-content">
                        <span class="material-icons-outlined menu-icon">logout</span>
                        <span class="menu-text">Log Out</span>
                    </div>
                    <span class="material-icons-outlined menu-chevron">chevron_right</span>
                </a>
            </div>
        </main>
    </div>

<?php
require_once __DIR__ . '/../assets/template/end-template.php';
?>