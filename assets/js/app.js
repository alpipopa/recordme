/**
 * app.js - Data Registry System
 */

document.addEventListener('DOMContentLoaded', function () {

    // 1) User Dropdown
    const userMenuToggle = document.getElementById('userMenuToggle');
    if (userMenuToggle) {
        userMenuToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            this.classList.toggle('open');
        });
        document.addEventListener('click', function () {
            userMenuToggle.classList.remove('open');
        });
    }

    // 2) Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        sidebar?.classList.toggle('sidebar--open');
        sidebarOverlay?.classList.toggle('active');
    }

    sidebarToggle?.addEventListener('click', toggleSidebar);
    sidebarOverlay?.addEventListener('click', toggleSidebar);

    // 3) Tabs
    document.querySelectorAll('.tabs-nav .tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.dataset.tab;
            const container = this.closest('.tabs-wrapper');
            if (!container) return;

            container.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            container.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

            this.classList.add('active');
            document.getElementById(targetId)?.classList.add('active');
        });
    });

    // 4) Dark Mode Toggle
    const darkToggle = document.getElementById('darkToggle');
    const body = document.body;

    function setDarkMode(enabled) {
        if (enabled) {
            body.classList.add('dark-mode');
            localStorage.setItem('darkMode', 'enabled');
            if (darkToggle) darkToggle.innerText = '☀️';
        } else {
            body.classList.remove('dark-mode');
            localStorage.setItem('darkMode', 'disabled');
            if (darkToggle) darkToggle.innerText = '🌙';
        }
    }

    // Load saved preference
    if (localStorage.getItem('darkMode') === 'enabled') {
        setDarkMode(true);
    }

    darkToggle?.addEventListener('click', () => {
        const isDark = body.classList.contains('dark-mode');
        setDarkMode(!isDark);
    });

    // 5) Form Confirmations
    document.querySelectorAll('.btn-confirm').forEach(btn => {
        btn.addEventListener('click', function (e) {
            const msg = this.dataset.confirm || 'Are you sure?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    // 6) Auto-hide Alerts
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // 7) Children Count Sync
    const maleInput = document.querySelector('[name="children_male"]');
    const femaleInput = document.querySelector('[name="children_female"]');
    const totalInput = document.querySelector('[name="children_total"]');

    if (maleInput && femaleInput && totalInput) {
        const updateChildren = () => {
            totalInput.value = (parseInt(maleInput.value) || 0) + (parseInt(femaleInput.value) || 0);
        };
        maleInput.addEventListener('input', updateChildren);
        femaleInput.addEventListener('input', updateChildren);
    }

});
