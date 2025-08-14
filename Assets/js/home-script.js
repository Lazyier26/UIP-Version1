/*SHOW MENU*/
document.addEventListener('DOMContentLoaded', function () {
    const navToggle = document.getElementById('nav-toggle');
    const navMenu = document.getElementById('nav-menu');

    if (navToggle && navMenu) {
        // Toggle menu visibility & icon
        navToggle.addEventListener('click', function () {
            navMenu.classList.toggle('show-menu');
            navToggle.classList.toggle('show-icon');
        });

        // Close menu when clicking on navigation links
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function () {
                navMenu.classList.remove('show-menu');
                navToggle.classList.remove('show-icon');
            });
        });

        // Close menu when clicking outside of it
        document.addEventListener('click', function (e) {
            if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
                navMenu.classList.remove('show-menu');
                navToggle.classList.remove('show-icon');
            }
        });
    }
});

