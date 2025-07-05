<?php
    require_once __DIR__ . '/../../src/functions.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $totalTransactionCount = 0;
    $currentUserId = null;
    if (isset($_SESSION['user']['id'])) {
        $currentUserId = $_SESSION['user']['id'];
        $totalTransactionCount = getTotalWalletTransactionCount($pdo, $currentUserId);
    }
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?display=swap&family=Inter:wght@400;500;700;900&family=Noto+Sans:wght@400;500;700;900"
    />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet" />
    <title>Pennieshares</title>
    <link rel="icon" type="image/x-icon" href="data:image/x-icon;base64," />

    <style>
      /* --- CSS Variables and Theme Setup --- */
      :root {
        --font-primary: 'Inter', 'Noto Sans', sans-serif;

        /* Light Theme Variables */
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

        --select-arrow: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' fill='%239cabba' viewBox='0 0 256 256'%3e%3cpath d='M215.39,92.61,132.61,175.39a8,8,0,0,1-11.22,0L41.61,92.61A8,8,0,0,1,52.83,81.39H203.17a8,8,0,0,1,11.22,11.22Z'%3e%3c/path%3e%3c/svg%3e");
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

      /* --- CSS Reset and Base Styles --- */
      *,
      *::before,
      *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      body {
        font-family: var(--font-primary);
        background-color: var(--bg-primary);
        color: var(--text-primary);
        min-height: 100vh;
        font-size: 16px;
        line-height: 1.5;
        transition: background-color 0.3s ease, color 0.3s ease;
        overflow-x: hidden; /* Prevent horizontal scroll */
      }

      a {
        text-decoration: none;
        color: inherit;
        transition: color 0.2s ease;
      }
      a:hover {
        color: var(--accent-color);
      }

      button {
        font-family: inherit;
        cursor: pointer;
        border: none;
        background: none;
        color: inherit;
      }

      .icon {
        width: 24px;
        height: 24px;
        stroke-width: 2;
        display: inline-block;
        vertical-align: middle;
      }

      /* --- Main Layout --- */
      .page-container {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
      }

      .main-content {
        flex-grow: 1;
        display: flex;
        justify-content: center;
        padding: 2rem 1rem;
      }

      .content-wrapper {
        width: 100%;
        max-width: 640px;
      }

      /* --- Header --- */
      .main-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 2rem;
        border-bottom: 1px solid var(--border-color);
        background-color: var(--bg-primary);
        position: sticky;
        top: 0;
        z-index: 10;
        transition: background-color 0.3s ease, border-color 0.3s ease;
      }

      .header-brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
      }
      .header-brand .icon {
        width: 28px;
        height: 28px;
        color: var(--accent-color);
      }
      .header-brand h2 {
        font-size: 1.25rem;
        font-weight: 700;
        /* display: none; /* Hidden on mobile by default */
      }

      .header-nav-desktop {
        display: none; /* Hidden on mobile */
        gap: 2rem;
      }
      .header-nav-desktop a {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.25rem; /* Space between icon and potential text if added back */
        font-size: 0.9rem;
        font-weight: 500;
      }

      .header-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
      }

      .action-button {
        background-color: var(--bg-tertiary);
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.2s ease;
      }
      .action-button:hover {
        background-color: var(--border-color);
      }
      .action-button .icon {
        color: var(--text-secondary);
      }

      .notification-bell-container {
        position: relative;
      }

      .notification-badge {
        position: absolute;
        top: 50%;
        right: 10px; /* Adjusted to be next to the text */
        transform: translateY(-50%);
        background-color: red;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 0.7em;
        min-width: 20px;
        text-align: center;
        display: none; /* Hidden by default */
      }

      .user-profile {
        display: flex;
        align-items: center;
        gap: 0.75rem;
      }
      .user-profile-photo {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-size: cover;
        background-position: center;
        border: 2px solid var(--border-color);
      }
      .user-profile-name {
        font-weight: 500;
        font-size: 0.9rem;
        /* display: none; /* Hidden by default */
      }

      #burger-menu {
        display: flex;
        padding: 0.5rem;
      }

      /* --- Mobile Navigation Menu --- */
      .nav-mobile {
        position: fixed;
        top: 0;
        left: -100%; /* Start off-screen */
        width: 280px;
        height: 100%;
        background-color: var(--bg-secondary);
        z-index: 100;
        transition: left 0.3s ease-in-out;
        display: flex;
        flex-direction: column;
        padding: 2rem 1.5rem;
        border-right: 1px solid var(--border-color);
      }
      .nav-mobile.is-open {
        left: 0;
      }

      .nav-mobile-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
      }

      .nav-mobile-brand {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1.2rem;
        font-weight: 700;
      }

      #close-menu-btn .icon {
        width: 28px;
        height: 28px;
      }

      .nav-mobile-links {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
      }
      .nav-mobile-links a {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        transition: background-color 0.2s ease, color 0.2s ease;
        position: relative; /* Added for badge positioning */
      }
      .nav-mobile-links a:hover,
      .nav-mobile-links a.active {
        background-color: var(--bg-tertiary);
        color: var(--accent-color);
      }
      .nav-mobile-links .icon {
        color: var(--text-secondary);
        transition: color 0.2s ease;
      }
      .nav-mobile-links a:hover .icon,
      .nav-mobile-links a.active .icon {
        color: var(--accent-color);
      }

      .nav-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 99;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
      }
      .nav-overlay.is-open {
        opacity: 1;
        visibility: visible;
      }

      /* --- Theme Toggle Icons --- */
      #theme-toggle .sun-icon,
      html[data-theme="dark"] #theme-toggle .moon-icon {
        display: none;
      }
      html[data-theme="dark"] #theme-toggle .sun-icon {
        display: block;
      }

      /* --- Desktop & Tablet Styles --- */
      @media (min-width: 768px) {
        .main-header {
          padding: 1rem 2.5rem;
        }
        .header-brand h2 {
          display: block;
        }
        .header-nav-desktop {
          display: flex;
          position: absolute;
          left: 50%;
          transform: translateX(-50%);
        }
        #burger-menu {
          display: none;
        }
        .user-profile-name {
          display: block;
        }
        .main-content {
          padding: 2.5rem;
        }
      }
    </style>
  </head>
  <body>
    <div class="page-container">
      <!-- Mobile Navigation -->
      <nav class="nav-mobile" id="nav-mobile">
        <div class="nav-mobile-header">
            <div class="nav-mobile-brand">
              <svg class="icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M24 4C25.7818 14.2173 33.7827 22.2182 44 24C33.7827 25.7818 25.7818 33.7827 24 44C22.2182 33.7827 14.2173 25.7818 4 24C14.2173 22.2182 22.2182 14.2173 24 4Z" fill="currentColor"></path>
              </svg>
              <span>Pennieshares</span>
            </div>
          <button id="close-menu-btn" aria-label="Close menu">
            <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <ul class="nav-mobile-links">
          <li><a href="wallet"><span class="material-icons-outlined">account_balance_wallet</span>Wallet</a></li>
          <li><a href="market"><span class="material-icons-outlined">storefront</span>Market</a></li>
          <li><a href="transactions"><span class="material-icons-outlined">receipt_long</span>Transactions<span class="notification-badge" id="notification-badge-mobile"></span></a></li>
          <li><a href="transfer"><span class="material-icons-outlined">swap_horiz</span>Transfer</a></li>
          <li><a href="find_broker"><span class="material-icons-outlined">search</span>Find Broker</a></li>
          <li><a href="profile_view"><span class="material-icons-outlined">person</span>Profile</a></li>
          <li><a href="settings"><span class="material-icons-outlined">settings</span>Settings</a></li>
          <?php if (isset($_SESSION['user']) && $_SESSION['user']['is_admin'] == 1): ?>
          <li><a href="admin"><span class="material-icons-outlined">admin_panel_settings</span>Admin Panel</a></li>
          <?php endif; ?>
        </ul>
      </nav>
      <div class="nav-overlay" id="nav-overlay"></div>

      <!-- Header -->
      <header class="main-header">
        <div class="header-brand">
          <button id="burger-menu" aria-label="Open menu">
            <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
          </button>
          <svg class="icon" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M24 4C25.7818 14.2173 33.7827 22.2182 44 24C33.7827 25.7818 25.7818 33.7827 24 44C22.2182 33.7827 14.2173 25.7818 4 24C14.2173 22.2182 22.2182 14.2173 24 4Z" fill="currentColor"></path>
          </svg>
          <h2 class="brand-name">Pennieshares</h2>
        </div>

        <nav class="header-nav-desktop">
          <a href="wallet"><span class="material-icons-outlined">account_balance_wallet</span></a>
          <a href="market"><span class="material-icons-outlined">storefront</span></a>
          <a href="transactions"><span class="material-icons-outlined">receipt_long</span></a>
          <a href="transfer"><span class="material-icons-outlined">swap_horiz</span></a>
          <a href="find_broker"><span class="material-icons-outlined">search</span></a>
          <a href="profile_view"><span class="material-icons-outlined">person</span></a>
          <a href="settings"><span class="material-icons-outlined">settings</span></a>
          <?php if (isset($_SESSION['user']) && $_SESSION['user']['is_admin'] == 1): ?>
          <a href="admin"><span class="material-icons-outlined">admin_panel_settings</span></a>
          <?php endif; ?>
        </nav>
        </nav>

        <div class="header-actions">
          </button>
          <button id="theme-toggle" class="action-button" aria-label="Toggle theme" style="display: none;">
             <svg class="icon moon-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
             <svg class="icon sun-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
          </button>
          <a href="settings" class="action-button" aria-label="Settings">
             <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
          </a>
          <a href="transactions" class="action-button notification-bell-container" aria-label="Notifications">
             <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
             <span class="notification-badge" id="notification-badge"></span>
          </a>
          
        </div>
      </header>

      </main>

      <!-- Main Content Area - This is where the page-specific content will be inserted -->
      <main class="main-content">
        <div class="content-wrapper">
