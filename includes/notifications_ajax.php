<?php
/**
 * التعامل مع التنبيهات عبر AJAX
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'get_latest') {
    $notifs = getMyNotifications(5);
    $unreadCount = getUnreadCount();
    
    // تنسيق الوقت للظهور بشكل جميل
    foreach ($notifs as &$n) {
        $n['time_ago'] = formatDate($n['created_at'], true);
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifs,
        'unread_count'  => $unreadCount
    ]);
    exit;
}

if ($action === 'mark_read') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $success = markAsRead($id);
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid Action']);
