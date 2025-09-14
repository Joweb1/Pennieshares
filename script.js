// Theme Toggling
const themeToggle = document.getElementById('theme-toggle-mobile');
const html = document.documentElement;

if (themeToggle && html) {
    const applyTheme = (theme) => {
        html.setAttribute('data-theme', theme);
        const sunIcon = themeToggle.querySelector('.sun-icon');
        const moonIcon = themeToggle.querySelector('.moon-icon');
        if (sunIcon && moonIcon) {
            if (theme === 'dark') {
                sunIcon.style.display = 'none';
                moonIcon.style.display = 'block';
            } else {
                sunIcon.style.display = 'block';
                moonIcon.style.display = 'none';
            }
        }
        
        // Also handle the light/dark images on the download page
        const lightModeImg = document.querySelector('.light-mode-img');
        const darkModeImg = document.querySelector('.dark-mode-img');
        const lightModeImgHero = document.querySelector('.light-mode-img-hero');
        const darkModeImgHero = document.querySelector('.dark-mode-img-hero');

        if (lightModeImg && darkModeImg) {
            lightModeImg.style.display = theme === 'dark' ? 'none' : 'block';
            darkModeImg.style.display = theme === 'dark' ? 'block' : 'none';
        }
        if (lightModeImgHero && darkModeImgHero) {
            lightModeImgHero.style.display = theme === 'dark' ? 'none' : 'block';
            darkModeImgHero.style.display = theme === 'dark' ? 'block' : 'none';
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
}


// Mobile Navigation
const burgerMenu = document.getElementById('burger-menu');
const mobileNav = document.getElementById('nav-mobile');
const closeMenuBtn = document.getElementById('close-menu-btn');
const navOverlay = document.getElementById('nav-overlay');

if (burgerMenu && mobileNav && closeMenuBtn && navOverlay) {
    burgerMenu.addEventListener('click', () => {
      mobileNav.classList.add('is-open');
      navOverlay.classList.add('is-open');
      document.body.classList.add('body-no-scroll');
    });

    closeMenuBtn.addEventListener('click', () => {
      mobileNav.classList.remove('is-open');
      navOverlay.classList.remove('is-open');
      document.body.classList.remove('body-no-scroll');
    });

    navOverlay.addEventListener('click', () => {
      mobileNav.classList.remove('is-open');
      navOverlay.classList.remove('is-open');
      document.body.classList.remove('body-no-scroll');
    });
}


// Smooth scroll for internal links
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const id = a.getAttribute('href').slice(1);
    if (id) {
        const target = document.getElementById(id);
        if (target) {
          e.preventDefault();
          window.scrollTo({
            top: target.offsetTop - 70, // 70px offset for fixed header
            behavior: 'smooth'
          });
          // close mobile panel after navigation
          if (mobileNav && mobileNav.classList.contains('is-open')) {
            mobileNav.classList.remove('is-open');
            navOverlay.classList.remove('is-open');
            document.body.classList.remove('body-no-scroll');
          }
        }
    }
  });
});

// Active link highlighting on scroll
const sections = document.querySelectorAll('section[id]');
const navLinks = document.querySelectorAll(".nav-mobile-links a");

if (sections.length > 0 && navLinks.length > 0) {
    window.addEventListener('scroll', () => {
      let current = "";
      sections.forEach((section) => {
        const sectionTop = section.offsetTop;
        if (window.pageYOffset >= sectionTop - 71) {
          current = section.getAttribute("id");
        }
      });

      navLinks.forEach((a) => {
        a.classList.remove("active");
        if (a.getAttribute('href').includes(current)) {
          a.classList.add("active");
        }
      });
    });
}


// AOS Initialization
document.addEventListener('DOMContentLoaded', function() {
    try {
        AOS.init();
    } catch (e) {
        console.error("Error initializing AOS:", e);
    }
});


// Swiper Initialization
document.addEventListener('DOMContentLoaded', function() {
    try {
        if (typeof Swiper !== 'undefined') {
            var swiper = new Swiper('.blog-swiper', {
              slidesPerView: 1,
              spaceBetween: 30,
              loop: true,
              pagination: {
                el: '.swiper-pagination',
                clickable: true,
              },
              navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
              },
              breakpoints: {
                640: {
                  slidesPerView: 2,
                  spaceBetween: 20,
                },
                1024: {
                  slidesPerView: 3,
                  spaceBetween: 30,
                },
              },
            });
        }
    } catch (e) {
        console.error("Error initializing Swiper:", e);
    }
});


// Intersection Observer for videos
const videoObserver = new IntersectionObserver((entries, observer) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.play().catch(e => console.error("Video play failed:", e));
    } else {
      entry.target.pause();
    }
  });
}, { threshold: 0.5 });

document.querySelectorAll('.boomerang-video').forEach(video => {
  videoObserver.observe(video);
});

// Loading Overlay
window.addEventListener('load', () => {
  document.body.classList.add('loaded');
});
