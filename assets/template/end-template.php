</div>
      </main>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
const themeToggle = document.getElementById('theme-toggle');
const html = document.documentElement;

      //  const themeToggler = document.getElementById('theme-toggled');
    const body = document.body;

    // Function to apply theme
    const applyTheme = (theme) => {
        if (theme === 'dark') {
            html.setAttribute('data-theme', theme);
            body.classList.add('dark-theme');
           body.classList.remove('light-theme')
           // themeToggler.checked = true;
        } else {
           body.classList.remove('dark-theme');
           body.classList.add('light-theme')
           // themeToggler.checked = false;
           html.setAttribute('data-theme', theme);
        }
    };
    
    // Check for saved theme in localStorage
    const savedTheme = localStorage.getItem('theme');
    // Check for user's system preference
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    // Prioritize saved theme, then system preference, then default to light
    if (savedTheme) {
        applyTheme(savedTheme);
    } else if (prefersDark) {
        applyTheme('dark');
    } else {
        applyTheme('light'); // Default
    }

    // Event listener for the toggle switch
    


// 3. Add event listener for the toggle button
themeToggle.addEventListener('click', () => {
  const currentTheme = html.getAttribute('data-theme');
  const newTheme = currentTheme === 'light' ? 'dark' : 'light';
  applyTheme(newTheme);
  localStorage.setItem('theme', newTheme);
});

// --- Mobile Navigation ---
const burgerMenu = document.getElementById('burger-menu');
const closeMenuBtn = document.getElementById('close-menu-btn');
const mobileNav = document.getElementById('nav-mobile');
const navOverlay = document.getElementById('nav-overlay');

const openNav = () => {
    mobileNav.classList.add('is-open');
    navOverlay.classList.add('is-open');
    document.body.style.overflow = 'hidden'; // Prevent scrolling
};

const closeNav = () => {
    mobileNav.classList.remove('is-open');
    navOverlay.classList.remove('is-open');
    document.body.style.overflow = ''; // Restore scrolling
};

burgerMenu.addEventListener('click', openNav);
closeMenuBtn.addEventListener('click', closeNav);
navOverlay.addEventListener('click', closeNav);
      });
    </script>
  </body>
</html>