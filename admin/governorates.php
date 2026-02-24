<?php
/**
 * إدارة المحافظات
 */
$pageTitle = 'إدارة المحافظات';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$errors = [];
$editGov = null;

if (get('action') === 'edit' && get('id')) {
    $editGov = dbQueryOne("SELECT * FROM governorates WHERE id = ?", [(int)get('id')]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = post('action');
    
    if ($action === 'add') {
        $name = post('name');
        $country_id = (int)post('country_id');
        if (empty($name)) { $errors[] = 'اسم المحافظة مطلوب.'; }
        else {
            dbExecute("INSERT INTO governorates (name, country_id) VALUES (?, ?)", [$name, $country_id]);
            redirectWithMessage(APP_URL . '/admin/governorates.php', 'success', 'تم إضافة المحافظة بنجاح.');
        }
    } elseif ($action === 'edit') {
        $id = (int)post('id');
        $name = post('name');
        $country_id = (int)post('country_id');
        if (empty($name)) { $errors[] = 'اسم المحافظة مطلوب.'; }
        else {
            dbExecute("UPDATE governorates SET name=?, country_id=? WHERE id=?", [$name, $country_id, $id]);
            redirectWithMessage(APP_URL . '/admin/governorates.php', 'success', 'تم تحديث المحافظة.');
        }
    } elseif ($action === 'delete') {
        $id = (int)post('id');
        $countDist = (int)(dbQueryOne("SELECT COUNT(*) as c FROM districts WHERE governorate_id=?", [$id])['c'] ?? 0);
        if ($countDist > 0) {
            $_SESSION['flash'] = ['type'=>'danger','message'=>"لا يمكن حذف المحافظة، يوجد $countDist مديرية/حي مرتبط بها."];
        } else {
            dbExecute("DELETE FROM governorates WHERE id=?", [$id]);
            $_SESSION['flash'] = ['type'=>'success','message'=>'تم حذف المحافظة.'];
        }
        redirect(APP_URL . '/admin/governorates.php');
    }
}

$governorates = dbQuery("SELECT g.*, c.name as country_name 
                        FROM governorates g 
                        LEFT JOIN countries c ON g.country_id = c.id 
                        ORDER BY c.name, g.name ASC");
$countries = dbQuery("SELECT * FROM countries ORDER BY name ASC");
?>

<div class="page-header">
    <h1 class="page-title">🏙 إدارة المحافظات</h1>
</div>

<?= getFlashMessage() ?>

<div class="two-col-layout">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= $editGov ? '✏ تعديل المحافظة' : '➕ إضافة محافظة جديدة' ?></h3>
        </div>
        <div class="card-body">
            <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $e) echo clean($e) . '<br>'; ?></div>
            <?php endif; ?>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="<?= $editGov ? 'edit' : 'add' ?>">
                <?php if ($editGov): ?>
                <input type="hidden" name="id" value="<?= $editGov['id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label required">الدولة</label>
                    <select name="country_id" class="form-control" required>
                        <?php foreach ($countries as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (($editGov['country_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                            <?= clean($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label required">اسم المحافظة</label>
                    <input type="text" name="name" class="form-control" required maxlength="100"
                           value="<?= clean($editGov['name'] ?? '') ?>" placeholder="مثال: صنعاء، عدن...">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $editGov ? '💾 حفظ التعديلات' : '➕ إضافة' ?>
                    </button>
                    <?php if ($editGov): ?>
                    <a href="governorates.php" class="btn btn-secondary">إلغاء</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">قائمة المحافظات (<?= count($governorates) ?>)</h3>
        </div>
        <div class="card-body p-0">
            <table class="table">
                <thead><tr><th>المحافظة</th><th>الدولة</th><th>الإجراءات</th></tr></thead>
                <tbody>
                <?php foreach ($governorates as $g): ?>
                <tr>
                    <td><?= clean($g['name']) ?></td>
                    <td><span class="badge badge-info"><?= clean($g['country_name'] ?? '—') ?></span></td>
                    <td>
                        <a href="governorates.php?action=edit&id=<?= $g['id'] ?>" class="btn btn-sm btn-warning">✏ تعديل</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('حذف هذه المحافظة؟')">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $g['id'] ?>">
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
