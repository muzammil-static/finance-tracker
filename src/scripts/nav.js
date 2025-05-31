// Mobile Menu Functions
function initMobileMenu() {
    const menuBtn = document.getElementById('menu-btn');
    const closeBtn = document.getElementById('close-btn');
    const navLinks = document.getElementById('nav-links');

    if (menuBtn && closeBtn && navLinks) {
        menuBtn.addEventListener('click', () => {
            navLinks.classList.add('show');
            document.body.style.overflow = 'hidden';
        });

        closeBtn.addEventListener('click', () => {
            navLinks.classList.remove('show');
            document.body.style.overflow = '';
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (navLinks.classList.contains('show') && 
                !navLinks.contains(e.target) && 
                !menuBtn.contains(e.target)) {
                navLinks.classList.remove('show');
                document.body.style.overflow = '';
            }
        });

        // Close menu when pressing Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && navLinks.classList.contains('show')) {
                navLinks.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    }
}

// Initialize mobile menu when DOM is loaded
document.addEventListener('DOMContentLoaded', initMobileMenu);

// Navigation and Dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropdowns = document.querySelectorAll('.dropdown');
    const menuBtn = document.getElementById('menu-btn');
    const navLinks = document.getElementById('nav-links');
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';

    // Set active state for current page
    function setActiveLinks() {
        // Handle main navigation links
        document.querySelectorAll('.nav-links li a').forEach(link => {
            const linkPage = link.getAttribute('href');
            if (linkPage === currentPage) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        // Handle dropdown items
        document.querySelectorAll('.dropdown-content a').forEach(link => {
            const linkPage = link.getAttribute('href');
            if (linkPage === currentPage) {
                link.classList.add('active');
                // Also set the dropdown trigger as active
                const dropdownTrigger = link.closest('.dropdown').querySelector('.dropdown-trigger span');
                if (dropdownTrigger) {
                    dropdownTrigger.classList.add('active');
                }
            } else {
                link.classList.remove('active');
            }
        });
    }

    // Set initial active states
    setActiveLinks();

    // Handle dropdown clicks on mobile
    dropdowns.forEach(dropdown => {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        
        trigger.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                dropdown.classList.toggle('active');
            }
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });

    // Close dropdowns when menu is closed
    menuBtn.addEventListener('click', function() {
        if (!navLinks.classList.contains('active')) {
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });
}); 