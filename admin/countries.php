<?php
/**
 * إدارة الدول
 */
$pageTitle = 'إدارة الدول';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$errors = [];
$editCountry = null;

// جلب الدولة للتعديل
if (get('action') === 'edit' && get('id')) {
    $editCountry = dbQueryOne("SELECT * FROM countries WHERE id = ?", [(int)get('id')]);
}

// معالجة الطلبات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = post('action');
    
    if ($action === 'add') {
        $name = post('name');
        if (empty($name)) { $errors[] = 'اسم الدولة مطلوب.'; }
        else {
            dbExecute("INSERT INTO countries (name) VALUES (?)", [$name]);
            redirectWithMessage(APP_URL . '/admin/countries.php', 'success', 'تم إضافة الدولة بنجاح.');
        }
    } elseif ($action === 'edit') {
        $id = (int)post('id');
        $name = post('name');
        if (empty($name)) { $errors[] = 'اسم الدولة مطلوب.'; }
        else {
            dbExecute("UPDATE countries SET name=? WHERE id=?", [$name, $id]);
            redirectWithMessage(APP_URL . '/admin/countries.php', 'success', 'تم تحديث الدولة.');
        }
    } elseif ($action === 'delete') {
        $id = (int)post('id');
        $count = (int)(dbQueryOne("SELECT COUNT(*) as c FROM governorates WHERE country_id=?", [$id])['c'] ?? 0);
        if ($count > 0) {
            $_SESSION['flash'] = ['type'=>'danger','message'=>"لا يمكن حذف الدولة، يوجد $count محافظة مرتبطة بها."];
        } else {
            dbExecute("DELETE FROM countries WHERE id=?", [$id]);
            $_SESSION['flash'] = ['type'=>'success','message'=>'تم حذف الدولة.'];
        }
        redirect(APP_URL . '/admin/countries.php');
    }
}

$countries = dbQuery("SELECT * FROM countries ORDER BY name ASC");
?>

<div class="page-header">
    <h1 class="page-title">🌍 إدارة الدول</h1>
</div>

<?= getFlashMessage() ?>

<div class="two-col-layout">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= $editCountry ? '✏ تعديل الدولة' : '➕ إضافة دولة جديدة' ?></h3>
        </div>
        <div class="card-body">
            <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $e) echo clean($e) . '<br>'; ?></div>
            <?php endif; ?>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="<?= $editCountry ? 'edit' : 'add' ?>">
                <?php if ($editCountry): ?>
                <input type="hidden" name="id" value="<?= $editCountry['id'] ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label class="form-label required">اسم الدولة</label>
                    <input type="text" name="name" class="form-control" required maxlength="100"
                           value="<?= clean($editCountry['name'] ?? '') ?>" placeholder="مثال: اليمن، السعودية...">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $editCountry ? '💾 حفظ التعديلات' : '➕ إضافة' ?>
                    </button>
                    <?php if ($editCountry): ?>
                    <a href="countries.php" class="btn btn-secondary">إلغاء</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">قائمة الدول (<?= count($countries) ?>)</h3>
        </div>
        <div class="card-body p-0">
            <table class="table">
                <thead><tr><th>#</th><th>الاسم</th><th>الإجراءات</th></tr></thead>
                <tbody>
                <?php foreach ($countries as $c): ?>
                <tr>
                    <td><?= $c['id'] ?></td>
                    <td><?= clean($c['name']) ?></td>
                    <td>
                        <a href="countries.php?action=edit&id=<?= $c['id'] ?>" class="btn btn-sm btn-warning">✏ تعديل</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('حذف هذه الدولة؟')">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
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
