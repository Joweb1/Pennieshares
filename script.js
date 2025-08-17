// Smooth Scroll
document.querySelectorAll('nav ul li a').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href.startsWith('#')) {
            e.preventDefault();
            document.querySelector(href).scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// Fade-in effect
document.addEventListener('DOMContentLoaded', function() {
    const hero = document.querySelector('.hero');
    if (hero) {
        hero.style.opacity = 0;
        setTimeout(() => {
            hero.style.transition = "opacity 2s";
            hero.style.opacity = 1;
        }, 500);
    }

    console.log("DOMContentLoaded fired. Attempting AOS.init().");
    try {
        console.log("Before AOS.init()");
        AOS.init();
        console.log("AOS.init() called successfully.");
    } catch (e) {
        console.error("Error initializing AOS:", e);
    }
});