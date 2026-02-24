/**
 * app.js — نظام تسجيل بيانات الأشخاص
 * JavaScript الرئيسي
 */

document.addEventListener('DOMContentLoaded', function () {

    // ==========================================
    // 1) قائمة المستخدم (User Dropdown)
    // ==========================================
    const userMenuToggle = document.getElementById('userMenuToggle');
    const userDropdown   = document.getElementById('userDropdown');

    if (userMenuToggle) {
        userMenuToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            userMenuToggle.classList.toggle('open');
        });
        document.addEventListener('click', function () {
            userMenuToggle.classList.remove('open');
        });
    }

    // ==========================================
    // 2) الشريط الجانبي (Sidebar Toggle)
    // ==========================================
    const sidebarToggle  = document.getElementById('sidebarToggle');
    const sidebar        = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        sidebar?.classList.add('sidebar--open');
        sidebarOverlay?.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar?.classList.remove('sidebar--open');
        sidebarOverlay?.classList.remove('active');
        document.body.style.overflow = '';
    }

    sidebarToggle?.addEventListener('click', function () {
        sidebar?.classList.contains('sidebar--open') ? closeSidebar() : openSidebar();
    });
    sidebarOverlay?.addEventListener('click', closeSidebar);

    // ==========================================
    // 3) Tabs النظام
    // ==========================================
    document.querySelectorAll('.tabs-nav .tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.dataset.tab;

            // إلغاء تفعيل كل الأزرار والمحتوى
            this.closest('.tabs-wrapper').querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.closest('.tabs-wrapper').querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

            // تفعيل المحدد
            this.classList.add('active');
            const target = document.getElementById(targetId);
            if (target) target.classList.add('active');

            // حفظ التبويب النشط
            sessionStorage.setItem('activeTab_' + window.location.pathname, targetId);
        });
    });

    // استعادة التبويب النشط عند إعادة التحميل
    const savedTab = sessionStorage.getItem('activeTab_' + window.location.pathname);
    if (savedTab) {
        const savedBtn = document.querySelector(`.tab-btn[data-tab="${savedTab}"]`);
        savedBtn?.click();
    }

    // ==========================================
    // 4) قابل للطي (Collapsible Cards)
    // ==========================================
    document.querySelectorAll('.collapsible').forEach(header => {
        const targetId = header.dataset.target;
        const content  = document.getElementById(targetId);
        if (!content) return;

        header.addEventListener('click', function () {
            const isHidden = content.style.display === 'none';
            content.style.display = isHidden ? '' : 'none';
            header.classList.toggle('collapsed', !isHidden);
        });
    });

    // ==========================================
    // 5) تأكيد الحذف (.btn-confirm)
    // ==========================================
    document.querySelectorAll('.btn-confirm').forEach(btn => {
        btn.addEventListener('click', function (e) {
            const msg = this.dataset.confirm || 'هل أنت متأكد من تنفيذ هذا الإجراء؟';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    // ==========================================
    // 6) إخفاء التنبيهات تلقائياً
    // ==========================================
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity .5s';
            alert.style.opacity    = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // ==========================================
    // 7) التحقق من النماذج
    // ==========================================
    const personForm = document.getElementById('personForm');
    if (personForm) {
        personForm.addEventListener('submit', function (e) {
            const requiredFields = personForm.querySelectorAll('[required]');
            let hasError = false;
            requiredFields.forEach(field => {
                field.style.borderColor = '';
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc2626';
                    if (!hasError) field.focus();
                    hasError = true;
                }
            });
            if (hasError) {
                e.preventDefault();
                // الانتقال للتبويب الأول الذي يحوي خطأ
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        const tabContent = field.closest('.tab-content');
                        if (tabContent) {
                            const tabId  = tabContent.id;
                            const tabBtn = document.querySelector(`[data-tab="${tabId}"]`);
                            tabBtn?.click();
                        }
                    }
                });
                showNotification('يرجى ملء جميع الحقول الإلزامية', 'danger');
            }
        });
    }

    // ==========================================
    // 8) تزامن أرقام الأطفال
    // ==========================================
    const maleInput   = document.querySelector('[name="children_male"]');
    const femaleInput = document.querySelector('[name="children_female"]');
    const totalInput  = document.querySelector('[name="children_total"]');

    function updateTotal() {
        if (maleInput && femaleInput && totalInput) {
            const m = parseInt(maleInput.value)   || 0;
            const f = parseInt(femaleInput.value)  || 0;
            totalInput.value = m + f;
        }
    }
    maleInput?.addEventListener('input', updateTotal);
    femaleInput?.addEventListener('input', updateTotal);

    // ==========================================
    // 9) إشعارات خفيفة
    // ==========================================
    function showNotification(msg, type = 'info') {
        const notif = document.createElement('div');
        notif.className = `alert alert-${type}`;
        notif.style.cssText = 'position:fixed;top:70px;left:20px;z-index:9999;min-width:250px;max-width:400px;animation:slideIn .3s ease';
        notif.innerHTML = `<span class="alert-icon">ℹ</span> ${msg}`;
        document.body.appendChild(notif);
        setTimeout(() => {
            notif.style.opacity = '0';
            notif.style.transition = 'opacity .4s';
            setTimeout(() => notif.remove(), 400);
        }, 4000);
    }

    window.showNotification = showNotification;

    // ==========================================
    // 10) Table Row Click (للانتقال للتعديل عند الضغط)
    // ==========================================
    document.querySelectorAll('table.table-hover tbody tr[data-href]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function (e) {
            if (!e.target.closest('a,button,form')) {
                window.location.href = this.dataset.href;
            }
        });
    });

    // ==========================================
    // 11) تحديث اللون عند اختيار اللون
    // ==========================================
    document.querySelectorAll('input[type="color"]').forEach(input => {
        const hexDisplay = input.nextElementSibling;
        if (hexDisplay) {
            input.addEventListener('input', () => hexDisplay.textContent = input.value);
        }
    });

    // ==========================================
    // 12) زر طباعة التقرير
    // ==========================================
    const printForm = document.getElementById('printForm');
    if (printForm) {
        printForm.addEventListener('submit', function (e) {
            const checked = printForm.querySelectorAll('.field-check:checked').length;
            if (checked === 0) {
                e.preventDefault();
                showNotification('يرجى تحديد حقل واحد على الأقل للطباعة', 'warning');
            }
        });
    }

    // ==========================================
    // 13) Active nav link highlight
    // ==========================================
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-item').forEach(link => {
        if (link.getAttribute('href') === currentPath ||
            currentPath.endsWith(link.getAttribute('href')?.replace(/^.*\//, '') || '___')) {
            link.classList.add('active');
        }
    });

});
