    <script>
        const themeToggle = document.getElementById('theme-toggle');
        const themeToggleSettings = document.getElementById('theme-toggle-settings');
        const html = document.documentElement;

        const applyTheme = (theme) => {
            if (theme === 'dark') {
                html.setAttribute('data-theme', theme);
                document.body.classList.add('dark-theme');
                document.body.classList.remove('light-theme');
                if (themeToggleSettings) {
                    themeToggleSettings.checked = true;
                }
            } else {
                document.body.classList.remove('dark-theme');
                document.body.classList.add('light-theme');
                html.setAttribute('data-theme', theme);
                if (themeToggleSettings) {
                    themeToggleSettings.checked = false;
                }
            }
        };
        
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (savedTheme) {
            applyTheme(savedTheme);
        } else if (prefersDark) {
            applyTheme('dark');
        } else {
            applyTheme('light');
        }

        themeToggle.addEventListener('click', () => {
          const currentTheme = html.getAttribute('data-theme');
          const newTheme = currentTheme === 'light' ? 'dark' : 'light';
          applyTheme(newTheme);
          localStorage.setItem('theme', newTheme);
        });

        if (themeToggleSettings) {
            themeToggleSettings.addEventListener('change', () => {
                const newTheme = themeToggleSettings.checked ? 'dark' : 'light';
                applyTheme(newTheme);
                localStorage.setItem('theme', newTheme);
            });
        }

        const burgerMenu = document.getElementById('burger-menu');
        const closeMenuBtn = document.getElementById('close-menu-btn');
        const mobileNav = document.getElementById('nav-mobile');
        const navOverlay = document.getElementById('nav-overlay');

        const openNav = () => {
            mobileNav.classList.add('is-open');
            navOverlay.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        };

        const closeNav = () => {
            mobileNav.classList.remove('is-open');
            navOverlay.classList.remove('is-open');
            document.body.style.overflow = '';
        };

        burgerMenu.addEventListener('click', openNav);
        closeMenuBtn.addEventListener('click', closeNav);
        navOverlay.addEventListener('click', closeNav);

        // --- Notification Badge Logic ---
        const currentTotalTransactions = <?php echo json_encode($totalTransactionCount ?? 0); ?>;
        const currentUserId = <?php echo json_encode($currentUserId ?? null); ?>;
        const lastReadTransactions = parseInt(localStorage.getItem(`lastReadTransactions_${currentUserId}`)) || 0;
        const unreadCount = currentTotalTransactions - lastReadTransactions;

        const notificationBadge = document.getElementById('notification-badge');
        const notificationBadgeMobile = document.getElementById('notification-badge-mobile');

        if (unreadCount > 0) {
            if (notificationBadge) {
                notificationBadge.textContent = unreadCount;
                notificationBadge.style.display = 'block';
            }
            if (notificationBadgeMobile) {
                notificationBadgeMobile.textContent = unreadCount;
                notificationBadgeMobile.style.display = 'block';
            }
        } else {
            if (notificationBadge) {
                notificationBadge.style.display = 'none';
            }
            if (notificationBadgeMobile) {
                notificationBadgeMobile.style.display = 'none';
            }
        }
    </script>
  </body>
</html>
