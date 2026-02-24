/**
 * notifications.js — نظام التنبيهات
 */

document.addEventListener('DOMContentLoaded', function () {
    const notifMenuToggle = document.getElementById('notifMenuToggle');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifList = document.getElementById('notifList');
    const notifBadge = document.getElementById('notifBadge');

    // المسارات - تأكد من مطابقتها لهيكل المجلدات لديك
    const ajaxPath = '/contact/includes/notifications_ajax.php';

    if (notifMenuToggle) {
        // فتح وإغلاق القائمة
        notifMenuToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            notifDropdown.classList.toggle('show');
            if (notifDropdown.classList.contains('show')) {
                fetchNotifications();
            }
        });

        // إغلاق عند النقر في الخارج
        document.addEventListener('click', function () {
            notifDropdown?.classList.remove('show');
        });

        // منع الإغلاق عند النقر داخل القائمة
        notifDropdown?.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    // جلب التنبيهات من السيرفر
    async function fetchNotifications() {
        if (!notifList) return;

        try {
            const response = await fetch(`${ajaxPath}?action=get_latest`);
            const data = await response.json();

            if (data.success) {
                // تحديث شارة العدد
                updateBadge(data.unread_count);

                // بناء قائمة التنبيهات
                if (data.notifications.length === 0) {
                    notifList.innerHTML = '<div class="notif-empty">لا توجد تنبيهات جديدة</div>';
                } else {
                    let html = '';
                    data.notifications.forEach(n => {
                        const unreadClass = n.is_read == 0 ? 'unread' : '';
                        html += `
                            <div class="notif-item ${unreadClass}" onclick="markNotifRead(${n.id}, '${n.link || '#'}')">
                                <span class="notif-title">${n.title}</span>
                                <span class="notif-msg">${n.message}</span>
                                <span class="notif-time">${n.time_ago}</span>
                            </div>
                        `;
                    });
                    notifList.innerHTML = html;
                }
            }
        } catch (error) {
            notifList.innerHTML = '<div class="notif-empty text-danger">فشل في تحميل التنبيهات</div>';
            console.error('Notification Fetch Error:', error);
        }
    }

    // تحديث الشارة العلوية
    function updateBadge(count) {
        if (!notifBadge) return;
        if (count > 0) {
            notifBadge.textContent = count;
            notifBadge.style.display = 'block';
        } else {
            notifBadge.style.display = 'none';
        }
    }

    // تحديد كمقروء والانتقال للرابط
    window.markNotifRead = async function (id, link) {
        const formData = new FormData();
        formData.append('id', id);
        try {
            await fetch(`${ajaxPath}?action=mark_read`, {
                method: 'POST',
                body: formData
            });
            window.location.href = link;
        } catch (error) {
            window.location.href = link;
        }
    };

    // فحص دوري كل دقيقة لتحديث شارة العدد فقط
    if (notifMenuToggle) {
        setInterval(async () => {
            try {
                const res = await fetch(`${ajaxPath}?action=get_latest`);
                const d = await res.json();
                if (d.success) updateBadge(d.unread_count);
            } catch (e) { }
        }, 60000);
    }
});
