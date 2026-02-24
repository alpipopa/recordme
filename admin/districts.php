<?php
/**
 * إدارة المديريات / الأحياء السكنية
 */
$pageTitle = 'إدارة الأحياء والمديريات';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$errors = [];
$editDistrict = null;

if (get('action') === 'edit' && get('id')) {
    $editDistrict = dbQueryOne("SELECT * FROM districts WHERE id = ?", [(int)get('id')]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = post('action');
    
    if ($action === 'add') {
        $name = post('name');
        $governorate_id = (int)post('governorate_id');
        if (empty($name)) { $errors[] = 'اسم الحي/المديرية مطلوب.'; }
        else {
            dbExecute("INSERT INTO districts (name, governorate_id) VALUES (?, ?)", [$name, $governorate_id]);
            redirectWithMessage(APP_URL . '/admin/districts.php', 'success', 'تم إضافة الحي/المديرية بنجاح.');
        }
    } elseif ($action === 'edit') {
        $id = (int)post('id');
        $name = post('name');
        $governorate_id = (int)post('governorate_id');
        if (empty($name)) { $errors[] = 'اسم الحي/المديرية مطلوب.'; }
        else {
            dbExecute("UPDATE districts SET name=?, governorate_id=? WHERE id=?", [$name, $governorate_id, $id]);
            redirectWithMessage(APP_URL . '/admin/districts.php', 'success', 'تم تحديث الحي/المديرية.');
        }
    } elseif ($action === 'delete') {
        $id = (int)post('id');
        // يمكن إضافة فحص ارتباط بأشخاص هنا مستقبلاً
        dbExecute("DELETE FROM districts WHERE id=?", [$id]);
        redirectWithMessage(APP_URL . '/admin/districts.php', 'success', 'تم حذف الحي/المديرية.');
    }
}

$districts = dbQuery("SELECT d.*, g.name as gov_name 
                     FROM districts d 
                     LEFT JOIN governorates g ON d.governorate_id = g.id 
                     ORDER BY g.name, d.name ASC");
$governorates = dbQuery("SELECT * FROM governorates ORDER BY name ASC");
?>

<div class="page-header">
    <h1 class="page-title">🏠 إدارة الأحياء والمديريات</h1>
</div>

<?= getFlashMessage() ?>

<div class="two-col-layout">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= $editDistrict ? '✏ تعديل الحي/المديرية' : '➕ إضافة حي/مديرية جديد' ?></h3>
        </div>
        <div class="card-body">
            <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $e) echo clean($e) . '<br>'; ?></div>
            <?php endif; ?>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="<?= $editDistrict ? 'edit' : 'add' ?>">
                <?php if ($editDistrict): ?>
                <input type="hidden" name="id" value="<?= $editDistrict['id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label required">المحافظة</label>
                    <select name="governorate_id" class="form-control" required>
                        <?php foreach ($governorates as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= (($editDistrict['governorate_id'] ?? '') == $g['id']) ? 'selected' : '' ?>>
                            <?= clean($g['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label required">اسم الحي / المديرية</label>
                    <input type="text" name="name" class="form-control" required maxlength="100"
                           value="<?= clean($editDistrict['name'] ?? '') ?>" placeholder="مثال: حي الرويشان، مديرية السبعين...">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $editDistrict ? '💾 حفظ التعديلات' : '➕ إضافة' ?>
                    </button>
                    <?php if ($editDistrict): ?>
                    <a href="districts.php" class="btn btn-secondary">إلغاء</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">قائمة الأحياء (<?= count($districts) ?>)</h3>
        </div>
        <div class="card-body p-0">
            <table class="table">
                <thead><tr><th>الحي / المديرية</th><th>المحافظة</th><th>الإجراءات</th></tr></thead>
                <tbody>
                <?php if (empty($districts)): ?>
                <tr><td colspan="3" class="text-center p-20">لا توجد بيانات مضافة</td></tr>
                <?php endif; ?>
                <?php foreach ($districts as $d): ?>
                <tr>
                    <td><?= clean($d['name']) ?></td>
                    <td><span class="badge badge-secondary"><?= clean($d['gov_name'] ?? '—') ?></span></td>
                    <td>
                        <a href="districts.php?action=edit&id=<?= $d['id'] ?>" class="btn btn-sm btn-warning">✏ تعديل</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('حذف هذا الحي؟')">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $d['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">🗑</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
