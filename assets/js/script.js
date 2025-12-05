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
    statNumbers.forEach(stat => {
        const count = parseInt(stat.getAttribute('data-count'));
        const label = stat.nextElementSibling.textContent;
        let current = 0;
        const increment = count / 100;

        const updateCount = () => {
            if (current < count) {
                current += increment;
                stat.textContent = Math.ceil(current);
                requestAnimationFrame(updateCount);
            } else {
                if (label.includes('%')) {
                    stat.textContent = count + '%';
                } else if (label.includes('+')) {
                    stat.textContent = count + '+';
                } else if (label.includes('/7')) {
                    stat.textContent = '24/7';
                } else {
                    stat.textContent = count;
                }
            }
        };
        updateCount();
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


