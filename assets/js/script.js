document.addEventListener('DOMContentLoaded', function() {
    // Navbar scroll effect
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Mobile menu toggle
    const navToggle = document.getElementById('nav-toggle');
    const navMenu = document.getElementById('nav-menu');
    navToggle.addEventListener('click', () => {
        navToggle.classList.toggle('active');
        navMenu.classList.toggle('active');
    });

    // Hero stats counter
    const statNumbers = document.querySelectorAll('.stat-number');
    const animationDuration = 2000; // 2 seconds

    const animateCount = (stat, finalCount, label) => {
        let startTime = null;

        const step = (timestamp) => {
            if (!startTime) startTime = timestamp;
            const progress = timestamp - startTime;
            const currentCount = Math.min(Math.floor(progress / animationDuration * finalCount), finalCount);

            stat.textContent = currentCount;

            if (progress < animationDuration) {
                requestAnimationFrame(step);
            } else {
                if (label.includes('%')) {
                    stat.textContent = finalCount + '%';
                } else if (label.includes('+')) {
                    stat.textContent = finalCount + '+';
                } else if (label.includes('/7')) {
                    stat.textContent = '24/7';
                } else if (label.toLowerCase().includes('hrs')) {
                    stat.textContent = finalCount + 'hrs';
                } else {
                    stat.textContent = finalCount;
                }
            }
        };
        requestAnimationFrame(step);
    };

    statNumbers.forEach(stat => {
        const finalCount = parseInt(stat.getAttribute('data-count'));
        const label = stat.nextElementSibling.textContent;
        // Only animate if finalCount is a valid number
        if (!isNaN(finalCount)) {
            animateCount(stat, finalCount, label);
        }
    });

    // Modal functionality
    const openModalBtns = document.querySelectorAll('.open-modal-btn');
    const modals = document.querySelectorAll('.modal');
    const body = document.querySelector('body');

    openModalBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.getAttribute('data-target');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
                body.style.overflow = 'hidden';
            }
        });
    });

    modals.forEach(modal => {
        const closeBtn = modal.querySelector('.close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                modal.classList.remove('show');
                body.style.overflow = 'auto';
            });
        }
        window.addEventListener('click', (event) => {
            if (event.target == modal) {
                modal.classList.remove('show');
                body.style.overflow = 'auto';
            }
        });
    });
});
