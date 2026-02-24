<?php
/**
 * رأس الصفحة - Header
 * يتضمن HTML الافتتاحي، meta tags، CSS، وشريط التنقل
 */

ob_start(); // منع أخطاء إرسال الرؤوس بعد المخرجات
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

// التحقق من تسجيل الدخول قبل أي مخرجات HTML
requireLogin();

$pageTitle = $pageTitle ?? APP_NAME;
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= clean($pageTitle) ?> | <?= clean(getSetting('site_name', APP_NAME)) ?></title>
    <!-- الخط العروب الفخم -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">
    
    <!-- مكتبة الرسوم البيانية -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📋</text></svg>">
</head>
<body class="admin-body">

<!-- شريط التنقل العلوي -->
<nav class="topbar">
    <div class="topbar-inner">
        <div class="topbar-brand">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="قائمة">
                <span></span><span></span><span></span>
            </button>
            <a href="<?= APP_URL ?>/admin/dashboard.php" class="brand-link">
                <?php 
                $logoUrl = getSetting('logo_path');
                if ($logoUrl && file_exists(UPLOAD_PATH . '/' . $logoUrl)): ?>
                    <img src="<?= UPLOAD_URL ?>/<?= clean($logoUrl) ?>" alt="Logo" style="max-height: 28px; margin-left: 8px;">
                <?php else: ?>
                    <span class="brand-icon">📋</span>
                <?php endif; ?>
                <span class="brand-text"><?= clean(getSetting('site_name', APP_NAME)) ?></span>
            </a>
        </div>
        <div class="topbar-actions">
            <!-- التنبيهات -->
            <div class="notif-menu" id="notifMenuToggle">
                <div class="notif-icon-wrap">
                    <span class="notif-icon">🔔</span>
                    <?php $unread = getUnreadCount(); ?>
                    <span id="notifBadge" class="notif-badge" style="<?= $unread > 0 ? '' : 'display:none' ?>"><?= $unread ?></span>
                </div>
                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-header">
                        <span>التنبيهات</span>
                        <a href="#" onclick="fetchNotifications(); return false;" style="font-size: 11px;">تحديث 🔄</a>
                    </div>
                    <div id="notifList" class="notif-list">
                        <div class="notif-loading">جاري التحميل...</div>
                    </div>
                    <div class="notif-footer">
                        <a href="#">عرض كل التنبيهات</a>
                    </div>
                </div>
            </div>

            <div class="user-menu" id="userMenuToggle">
                <?php if (!empty($user['avatar']) && file_exists(UPLOAD_PATH . '/avatars/' . $user['avatar'])): ?>
                    <img src="<?= UPLOAD_URL ?>/avatars/<?= clean($user['avatar']) ?>" alt="Avatar" class="user-avatar" style="object-fit: cover;">
                <?php else: ?>
                    <div class="user-avatar"><?= mb_substr($user['full_name'] ?? 'م', 0, 1) ?></div>
                <?php endif; ?>
                <div class="user-info">
                    <span class="user-name"><?= clean($user['full_name'] ?? '') ?></span>
                    <span class="user-role"><?= clean($user['role'] ?? '') ?></span>
                </div>
                <div class="user-dropdown" id="userDropdown">
                    <a href="<?= APP_URL ?>/admin/profile.php" class="dropdown-item">👤 الملف الشخصي</a>
                    <a href="<?= APP_URL ?>/admin/users.php" class="dropdown-item">⚙ إدارة المستخدمين</a>
                    <div class="dropdown-divider"></div>
                    <a href="<?= APP_URL ?>/logout.php" class="dropdown-item dropdown-item-danger">🚪 تسجيل الخروج</a>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- الشريط الجانبي -->
<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <div class="nav-section-title">القائمة الرئيسية</div>
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'active' : '' ?>">
            <span class="nav-icon">🏠</span>
            <span class="nav-label">لوحة التحكم</span>
        </a>
        
        <div class="nav-section-title">إدارة البيانات</div>
        <a href="<?= APP_URL ?>/admin/persons.php" class="nav-item <?= in_array(basename($_SERVER['PHP_SELF']), ['persons.php','person_add.php','person_edit.php']) ? 'active' : '' ?>">
            <span class="nav-icon">👥</span>
            <span class="nav-label">الأشخاص</span>
        </a>
        <a href="<?= APP_URL ?>/admin/person_add.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) === 'person_add.php') ? 'active' : '' ?>">
            <span class="nav-icon">➕</span>
            <span class="nav-label">إضافة شخص</span>
        </a>
        <a href="<?= APP_URL ?>/admin/categories.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) === 'categories.php') ? 'active' : '' ?>">
            <span class="nav-icon">🏷</span>
            <span class="nav-label">التصنيفات</span>
        </a>
        <a href="<?= APP_URL ?>/admin/custom_fields.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) === 'custom_fields.php') ? 'active' : '' ?>">
            <span class="nav-icon">🔧</span>
            <span class="nav-label">الحقول المخصصة</span>
        </a>
        <a href="<?= APP_URL ?>/admin/sliders.php" class="nav-item <?= in_array(basename($_SERVER['PHP_SELF']), ['sliders.php','slider_add.php','slider_edit.php']) ? 'active' : '' ?>">
            <span class="nav-icon">🖼</span>
            <span class="nav-label">إدارة السلايدر</span>
        </a>
        
        <div class="nav-section-title">التقارير والتصدير</div>
        <a href="<?= APP_URL ?>/admin/print_report.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) === 'print_report.php') ? 'active' : '' ?>">
            <span class="nav-icon">🖨</span>
            <span class="nav-label">طباعة التقارير</span>
        </a>
        <a href="<?= APP_URL ?>/admin/export_csv.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) === 'export_csv.php') ? 'active' : '' ?>">
            <span class="nav-icon">📊</span>
            <span class="nav-label">تصدير CSV</span>
        </a>
        
        <?php if (isAdmin()): ?>
        <div class="nav-section-title">الإدارة والرقابة</div>
        <a href="<?= APP_URL ?>/admin/users.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) === 'users.php') ? 'active' : '' ?>">
            <span class="nav-icon">👤</span>
            <span class="nav-label">المستخدمون</span>
        </a>
        <a href="<?= APP_URL ?>/admin/audit_log.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) === 'audit_log.php') ? 'active' : '' ?>">
            <span class="nav-icon">🛡</span>
            <span class="nav-label">سجل الأنشطة</span>
        </a>
        <a href="<?= APP_URL ?>/admin/settings.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) === 'settings.php') ? 'active' : '' ?>">
            <span class="nav-icon">⚙</span>
            <span class="nav-label">الإعدادات</span>
        </a>
        <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer">
        <div class="sidebar-version">الإصدار <?= APP_VERSION ?></div>
        <a href="<?= APP_URL ?>/logout.php" class="btn-logout">🚪 خروج</a>
    </div>
</aside>

<!-- المحتوى الرئيسي -->
<main class="main-content" id="mainContent">
    <div class="content-wrapper">
