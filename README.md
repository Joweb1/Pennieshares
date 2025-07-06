# Pennieshares: A PHP-Based Financial Asset Management Platform

Pennieshares is a robust web application designed to facilitate financial asset management, user accounts, and a multi-level marketing (MLM) or referral-based payout system. Built with PHP and SQLite, it offers a comprehensive backend for handling complex financial logic and a modern, responsive frontend for seamless user interaction.

## Features

### User Management & Authentication
*   **Secure User Operations:** Handles user registration, login, and session management with password hashing and CSRF protection.
*   **Password Reset:** Functionality for users to securely reset forgotten passwords.
*   **User Profiles & VIP Stages:** Users have profiles and can progress through "VIP" stages.
*   **Admin Roles:** Supports assignment of administrative privileges for privileged operations.
*   **Account Verification:** Users can submit payment proofs for account verification, which is managed by administrators.

### Asset Management System
*   **Asset Purchasing:** Users can purchase various asset types, each with defined prices, payout caps, and durations.
*   **Hierarchical Structure:** Implements a parent-child relationship for assets, enabling generational payouts.
*   **Asset Statuses:** Assets can be marked as completed or expired based on predefined conditions or manual intervention.
*   **Asset Type Management:** Administrators can add, delete, and manage asset types, including their pricing, payout caps, and associated images.

### Dynamic Payout Mechanisms
*   **Generational Payouts:** A portion of new asset purchases is distributed upwards through a hierarchy of parent assets, with payouts capped per asset and limited by generation depth.
*   **Shared Payouts:** A fixed amount from new asset purchases is equally distributed among all currently active assets in the system.

### Wallet & Financial Transactions
*   **User Wallets:** Manages individual user wallet balances.
*   **Company Funds Tracking:** Tracks various company funds, including total profit, reservation fund, generational pot, and shared pot.
*   **Fund Transfers:** Supports direct wallet transfers between users (specifically to admin users).
*   **Comprehensive Transaction Logging:** All financial activities are logged with types such as `credit`, `debit`, `payout`, `transfer_in`, `transfer_out`, and `asset_profit`.
*   **Transaction History:** Users can view their detailed transaction history with filtering and pagination options, and export it as a PDF.

### Administrative Panel
A dedicated interface for administrators to:
*   Manually buy assets for users.
*   Register new users.
*   Credit user wallets.
*   Transfer funds between users.
*   Assign admin roles.
*   Verify user accounts based on payment proofs.
*   Manage (mark expired/completed, add, delete) asset types.
*   View overall system statistics and financial distributions through charts.
*   Search and paginate user and asset tables.
*   View recent payout history.

### Frontend Interface
Built with HTML, CSS, and JavaScript for a modern and responsive user experience. Key pages include:
*   **Home:** A "Coming Soon" page with a countdown.
*   **Dashboard:** Overview for logged-in users, displaying referral counts, verification status, and quick access to other features.
*   **Market:** Allows users to browse and select available asset types for purchase, with category filtering.
*   **Buy Asset:** A multi-step process for purchasing assets, with pre-selection capability from the Market page.
*   **Wallet:** Displays user's wallet balance, total return, and assets worth, along with performance charts.
*   **Transfer:** Enables users to transfer funds to brokers.
*   **Transactions:** Provides a detailed history of all wallet activities with filtering and export options.
*   **Profile:** Displays user profile information, referred partners, and options to edit profile or delete account.
*   **Settings:** Allows users to manage general settings like theme and account-related actions.
*   **ID Card:** For verified users to generate a digital ID card.
*   **About & FAQs:** Informational pages about the platform and common questions.
*   **404 Page:** A custom error page for invalid routes.
*   **Dynamic Theming:** Supports dynamic dark/light theme toggling.

## Technologies Used

*   **Backend:** PHP
*   **Database:** SQLite (using PDO for interactions)
*   **Frontend:** HTML, CSS, JavaScript
*   **Styling:** Custom CSS with CSS variables for theming, Font Awesome for icons, Chart.js for data visualization.
*   **PDF Generation:** jsPDF and html2canvas for client-side PDF export.

## Project Structure

The project is organized into logical directories:

*   `assets/`: Contains static assets like images, sounds, and shared HTML templates.
    *   `assets/images/`: Images used throughout the application.
    *   `assets/sound/`: Sound effects for UI feedback.
    *   `assets/template/`: Reusable HTML templates (`intro-template.php`, `end-template.php`).
*   `config/`: Configuration files, primarily `database.php` for database setup.
*   `database/`: SQLite database files (`mydatabase.sqlite`, `mydatabasebak.sqlite`).
*   `pages/`: Contains all the individual PHP files that render different web pages.
    *   `pages/api/`: API endpoints (e.g., `generate_transaction_history.php`).
*   `src/`: Core PHP logic and utility functions.
    *   `src/functions.php`: General user, session, and wallet management functions.
    *   `src/assets_functions.php`: Functions related to asset management and payouts.
*   `uploads/`: Directory for user-uploaded files (e.g., payment proofs).
*   `vendor/`: Third-party dependencies (ignored by Git).
*   `.htaccess`: Apache rewrite rules for clean URLs.
*   `.gitignore`: Specifies files and directories to be ignored by Git.
*   `db_refresh.php`: Script for resetting the database (likely for development/testing).
*   `import_users.php`: Script for importing user data from a backup database.
*   `index.php`: The main entry point and routing mechanism for the application.
*   `README.md`: This file.
*   `script.js`: General frontend JavaScript for UI enhancements.

## Setup & Installation

To set up and run Pennieshares locally, you will need a PHP server (e.g., Apache, Nginx) with PHP 7.4+ and SQLite support.

1.  **Clone the repository:**
    ```bash
    git clone <repository_url>
    cd Pennieshares
    ```
2.  **Configure your web server:** Point your web server's document root to the `Pennieshares` directory. Ensure `mod_rewrite` is enabled for clean URLs to work.
3.  **Database Setup:**
    *   The `config/database.php` file will automatically create the necessary tables if they don't exist when accessed.
    *   You can optionally run `db_refresh.php` (accessible via `http://your-domain/db_refresh.php`) to reset the database and populate it with initial data.
    *   If you have a backup, `import_users.php` can be used to import user data.
4.  **Access the application:** Open your web browser and navigate to the configured domain or `localhost` address.

## Usage

*   **Registration:** New users can register via the `/register` page.
*   **Login:** Existing users can log in via the `/login` page.
*   **Dashboard:** After logging in, users are redirected to their dashboard, providing an overview of their account.
*   **Explore Features:** Navigate through the various sections using the sidebar or header links to manage your wallet, buy assets, view transactions, and more.
*   **Admin Access:** If you have an admin account, you can access the admin panel via `/admin` to manage users, assets, and system settings.