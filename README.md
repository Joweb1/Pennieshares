# Pennieshares: A PHP-Based Financial Asset Management Platform

This project is a web application built with PHP and SQLite, designed to manage user accounts, financial assets, and a multi-level marketing (MLM) or referral-based payout system. It features a comprehensive backend for handling complex financial logic and a modern, responsive frontend for user interaction.

## Core Features:

*   **User Management & Authentication:**
    *   Secure user registration, login, and session management.
    *   Password reset functionality.
    *   User profiles with "VIP" stages.
    *   Admin role assignment for privileged operations.
    *   CSRF protection for enhanced security.

*   **Asset Management System:**
    *   Users can purchase various asset types, each with defined prices, payout caps, and durations.
    *   Implements a hierarchical asset structure, allowing assets to have parent-child relationships.
    *   Assets can be marked as completed or expired.

*   **Dynamic Payout Mechanisms:**
    *   **Generational Payouts:** A portion of new asset purchases is distributed up a hierarchy of parent assets, with payouts capped per asset.
    *   **Shared Payouts:** A fixed amount from new asset purchases is equally distributed among all currently active assets in the system.

*   **Wallet & Financial Transactions:**
    *   Manages individual user wallet balances.
    *   Tracks company funds, including profit, reservation fund, generational pot, and shared pot.
    *   Supports direct wallet transfers between users (specifically to admin users).
    *   Comprehensive transaction logging for all financial activities:
        *   `credit`: When an admin credits a user's wallet.
        *   `debit`: When a user purchases an asset.
        *   `payout`: When a user transfers funds out of their wallet.
        *   `asset_profit`: For both generational and shared payouts from assets.

*   **Administrative Panel:**
    *   A dedicated interface for administrators to:
        *   Manually buy assets for users.
        *   Register new users.
        *   Credit user wallets.
        *   Transfer funds between users.
        *   Assign admin roles.
        *   Manage (mark expired/completed, add, delete) asset types.
        *   View overall system statistics and financial distributions.

*   **Frontend Interface:**
    *   Built with HTML, CSS, and JavaScript for a modern user experience.
    *   Includes key pages:
        *   **Dashboard:** Overview for logged-in users, displaying referral counts and verification status.
        *   **Market:** Browse and select available asset types for purchase.
        *   **Buy Asset:** A multi-step process for purchasing assets, with pre-selection capability from the Market page.
        *   **Transfer:** Allows users to transfer funds from their wallet to an admin.
        *   **Transactions:** A detailed history of all wallet activities, with filtering options for different transaction types.
    *   Supports dynamic dark/light theme toggling.

## Technologies Used:

*   **Backend:** PHP
*   **Database:** SQLite (PDO for database interactions)
*   **Frontend:** HTML, CSS, JavaScript
*   **Styling:** Custom CSS with CSS variables for theming.

This application provides a robust framework for managing a financial asset system with integrated user and payout management.
