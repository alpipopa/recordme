<?php
/**
 * صفحة تسجيل الخروج
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    logAction('logout', 'users', (int)$_SESSION['user_id']);
    logout();
}

redirect(APP_URL . '/login.php');
