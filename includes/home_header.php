<?php
/**
 * رأس الصفحة الرئيسية العامة
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

$pageTitle   = $pageTitle ?? APP_NAME;
$metaDesc    = $metaDesc  ?? 'نظام متكامل لإدارة وحفظ بيانات الأشخاص';
$currentUser = currentUser();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= clean($metaDesc) ?>">
    <title><?= clean($pageTitle) ?> | <?= clean(getSetting('site_name', APP_NAME)) ?></title>

    <!-- الأيقونة -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📋</text></svg>">

    <!-- الخط العروب الفخم -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">

    <!-- CSS الرئيسي + CSS الصفحة الرئيسية -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/home.css">
</head>
<body class="home-body">

<!-- ============================
     الهيدر العلوي
============================  -->
<header class="home-header" id="homeHeader">
    <div class="home-header-inner">

        <!-- الشعار -->
        <a href="<?= APP_URL ?>/" class="home-brand">
            <?php 
            $logoUrl = getSetting('logo_path');
            if ($logoUrl && file_exists(UPLOAD_PATH . '/' . $logoUrl)): ?>
                <img src="<?= UPLOAD_URL ?>/<?= clean($logoUrl) ?>" alt="Logo" style="max-height: 40px;">
            <?php else: ?>
                <span class="home-brand-icon">📋</span>
            <?php endif; ?>
            <span class="home-brand-text"><?= clean(getSetting('site_name', APP_NAME)) ?></span>
        </a>

        <!-- القائمة الرئيسية -->
        <nav class="home-nav" id="homeNav">
            <a href="<?= APP_URL ?>/" class="home-nav-link <?= (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : '' ?>">
                🏠 الرئيسية
            </a>
            <?php if (isLoggedIn()): ?>
            <a href="<?= APP_URL ?>/admin/dashboard.php" class="home-nav-link">
                ⚙ لوحة التحكم
            </a>
            <a href="<?= APP_URL ?>/logout.php" class="home-nav-link home-nav-logout">
                🚪 خروج
            </a>
            <?php else: ?>
            <a href="<?= APP_URL ?>/login.php" class="home-nav-link">
                🔑 تسجيل الدخول
            </a>
            <?php endif; ?>
        </nav>

        <!-- زر القائمة (موبايل) -->
        <button class="home-nav-toggle" id="homeNavToggle" aria-label="القائمة">
            <span></span><span></span><span></span>
        </button>

    </div>
</header>

<!-- المحتوى الرئيسي -->
<main class="home-main">
