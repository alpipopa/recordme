<?php
/**
 * استعادة شخص من سلة المهملات
 */
require_once __DIR__ . '/../includes/header.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    
    $id = (int)post('id');
    $person = dbQueryOne("SELECT * FROM persons WHERE id = ? AND deleted_at IS NOT NULL", [$id]);
    
    if (!$person) {
        redirectWithMessage(APP_URL . '/admin/trash.php', 'danger', 'الشخص غير موجود في سلة المهملات.');
    }
    
    dbExecute("UPDATE persons SET deleted_at = NULL WHERE id = ?", [$id]);
    logAction('restore', 'persons', $id, ['deleted_at' => $person['deleted_at']], ['deleted_at' => null]);
    
    redirectWithMessage(APP_URL . '/admin/trash.php', 'success', 'تم استعادة الشخص بنجاح.');
} else {
    redirect(APP_URL . '/admin/trash.php');
}
