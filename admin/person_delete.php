<?php
/**
 * حذف شخص
 */
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$id     = (int)get('id');
$person = dbQueryOne("SELECT * FROM persons WHERE id = ?", [$id]);

if (!$person) {
    redirectWithMessage(APP_URL . '/admin/persons.php', 'danger', 'الشخص غير موجود.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    
    // التحديث لسلة المهملات بدلاً من الحذف النهائي
    dbExecute("UPDATE persons SET deleted_at = NOW() WHERE id = ?", [$id]);
    logAction('soft_delete', 'persons', $id, $person, ['deleted_at' => date('Y-m-d H:i:s')]);
    
    redirectWithMessage(APP_URL . '/admin/persons.php', 'success', 'تم نقل الشخص إلى سلة المهملات بنجاح.');
}
?>

<div class="page-header">
    <h1 class="page-title">🗑 تأكيد الحذف</h1>
</div>

<div class="card" style="max-width:600px;margin:0 auto;">
    <div class="card-body text-center">
        <div class="delete-icon">⚠</div>
        <h3 class="mt-20">هل أنت متأكد من حذف هذا الشخص؟</h3>
        <p class="text-muted">سيتم حذف جميع بيانات الشخص بشكل نهائي ولا يمكن التراجع عن هذا الإجراء.</p>
        
        <div class="delete-card-info">
            <div class="info-row"><span>الاسم:</span><strong><?= clean($person['full_name']) ?></strong></div>
            <div class="info-row"><span>رقم الهوية:</span><strong><?= clean($person['id_number']) ?: '—' ?></strong></div>
            <div class="info-row"><span>الهاتف:</span><strong><?= clean($person['phone']) ?: '—' ?></strong></div>
        </div>
        
        <form method="POST" class="mt-20">
            <?= csrfField() ?>
            <button type="submit" class="btn btn-danger btn-lg">🗑 نعم، احذف الآن</button>
            <a href="persons.php" class="btn btn-secondary btn-lg">✖ إلغاء</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
