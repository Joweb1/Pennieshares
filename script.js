// Smooth Scroll
document.querySelectorAll('nav ul li a').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({ behavior: 'smooth' });
    });
});

// Fade-in effect
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.hero').style.opacity = 0;
    setTimeout(() => {
        document.querySelector('.hero').style.transition = "opacity 2s";
        document.querySelector('.hero').style.opacity = 1;
    }, 500);
});
