<?php
/**
 * حذف شخص نهائياً من قاعدة البيانات والسيرفر
 */
require_once __DIR__ . '/../includes/header.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    
    $id = (int)post('id');
    $person = dbQueryOne("SELECT * FROM persons WHERE id = ?", [$id]);
    
    if (!$person) {
        redirectWithMessage(APP_URL . '/admin/trash.php', 'danger', 'الشخص غير موجود.');
    }
    
    // 1. حذف الملفات من السيرفر
    $images = ['id_image', 'personal_photo'];
    foreach ($images as $imgField) {
        if ($person[$imgField] && file_exists(UPLOAD_PATH . '/' . $person[$imgField])) {
            @unlink(UPLOAD_PATH . '/' . $person[$imgField]);
        }
    }
    
    // 2. حذف السجل نهائياً (سيحذف custom_field_values تلقائياً بسبب CASCADE)
    dbExecute("DELETE FROM persons WHERE id = ?", [$id]);
    
    logAction('force_delete', 'persons', $id, $person, []);
    
    redirectWithMessage(APP_URL . '/admin/trash.php', 'success', 'تم حذف الشخص وجميع بياناته نهائياً.');
} else {
    redirect(APP_URL . '/admin/trash.php');
}
