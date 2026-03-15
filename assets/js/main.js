// FILE: /consignxAnti/assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {

    // 1. Theme Toggle Logic (Light / Dark Mode)
    const toggleSwitch = document.querySelector('.theme-switch input[type="checkbox"]');
    const currentTheme = localStorage.getItem('theme') || 'light';

    if (currentTheme) {
        document.documentElement.setAttribute('data-theme', currentTheme);
        if (currentTheme === 'dark' && toggleSwitch) {
            toggleSwitch.checked = true;
        }
    }

    if (toggleSwitch) {
        toggleSwitch.addEventListener('change', (e) => {
            if (e.target.checked) {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
            }
        });
    }

    // 2. Initialize Bootstrap Tooltips & Popovers
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 3. Simple Client-Side Tracking Form validation (Landing Page)
    const trackForm = document.getElementById('quickTrackForm');
    if (trackForm) {
        trackForm.addEventListener('submit', (e) => {
            const trackInput = document.getElementById('tracking_id');
            // Basic Regex check for C-XXXX-XXXX
            const regex = /^C-[A-Z0-9]{4}-[A-Z0-9]{4}$/i;
            if (!regex.test(trackInput.value)) {
                e.preventDefault();
                alert('Please enter a valid tracking number format (e.g. C-A1B2-C3D4)');
                trackInput.classList.add('is-invalid');
            } else {
                trackInput.classList.remove('is-invalid');
            }
        });
    }

    // 4. Smooth Scrolling for Anchor Links (Landing Page)
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // 5. Hide Alerts Automatically after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    if (alerts.length > 0) {
        setTimeout(() => {
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    }

    // 6. Unified Sidebar Toggle Logic (Universal Off-canvas)
    const sidebar = document.querySelector('.sidebar');
    const toggleBtns = document.querySelectorAll('.sidebar-toggle-btn');
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    if (sidebar && !document.querySelector('.sidebar-overlay')) {
        document.body.appendChild(overlay);
    }
    
    if (sidebar) {
        const toggleSidebar = () => {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        };

        toggleBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleSidebar();
            });
        });

        // Close on overlay click
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });
    }

});
