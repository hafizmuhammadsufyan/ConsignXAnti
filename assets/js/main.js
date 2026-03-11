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

    // 6. Sidebar Toggle Logic
    const sidebar = document.querySelector('.sidebar');
    const desktopToggleBtn = document.querySelector('.desktop-toggle-btn');
    const mobileToggleBtn = document.querySelector('.sidebar-toggle-btn');
    
    // Desktop: collapse / expand
    if (sidebar && desktopToggleBtn) {
        desktopToggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            // Save preference to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed ? 'true' : 'false');
        });

        // Load preference on load
        const savedState = localStorage.getItem('sidebarCollapsed');
        if (savedState === 'true') {
            sidebar.classList.add('collapsed');
        }
    }

    // Mobile: open / close off-canvas
    if (sidebar && mobileToggleBtn) {
        mobileToggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
        
        // Close when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
                if (!sidebar.contains(e.target) && !mobileToggleBtn.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }

});
