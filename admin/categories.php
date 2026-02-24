<?php
/**
 * إدارة التصنيفات
 */
$pageTitle = 'إدارة التصنيفات';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$errors = [];
$editCat = null;

// جلب التصنيف للتعديل
if (get('action') === 'edit' && get('id')) {
    $editCat = getCategoryById((int)get('id'));
}

// معالجة الطلبات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = post('action');
    
    if ($action === 'add') {
        $name  = post('name');
        $color = post('color', '#6c757d');
        $order = (int)post('sort_order', '0');
        
        if (empty($name)) { $errors[] = 'اسم التصنيف مطلوب.'; }
        else {
            dbExecute("INSERT INTO categories (name, color, sort_order) VALUES (?,?,?)", [$name, $color, $order]);
            redirectWithMessage(APP_URL . '/admin/categories.php', 'success', 'تم إضافة التصنيف بنجاح.');
        }
    } elseif ($action === 'edit') {
        $catId = (int)post('id');
        $name  = post('name');
        $color = post('color', '#6c757d');
        $order = (int)post('sort_order', '0');
        
        if (empty($name)) { $errors[] = 'اسم التصنيف مطلوب.'; }
        else {
            dbExecute("UPDATE categories SET name=?, color=?, sort_order=? WHERE id=?", [$name, $color, $order, $catId]);
            redirectWithMessage(APP_URL . '/admin/categories.php', 'success', 'تم تحديث التصنيف.');
        }
    } elseif ($action === 'delete') {
        $catId = (int)post('id');
        // التحقق من وجود أشخاص مرتبطين
        $count = (int)(dbQueryOne("SELECT COUNT(*) as c FROM persons WHERE category_id=?", [$catId])['c'] ?? 0);
        if ($count > 0) {
            $_SESSION['flash'] = ['type'=>'danger','message'=>"لا يمكن حذف التصنيف، يوجد $count شخص مرتبط به."];
        } else {
            dbExecute("DELETE FROM categories WHERE id=?", [$catId]);
            $_SESSION['flash'] = ['type'=>'success','message'=>'تم حذف التصنيف.'];
        }
        redirect(APP_URL . '/admin/categories.php');
    }
}

$categories = getCategories();
?>

<div class="page-header">
    <h1 class="page-title">🏷 إدارة التصنيفات</h1>
</div>

<?= getFlashMessage() ?>

<div class="two-col-layout">
    <!-- نموذج الإضافة/التعديل -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= $editCat ? '✏ تعديل التصنيف' : '➕ إضافة تصنيف جديد' ?></h3>
        </div>
        <div class="card-body">
            <?php if ($errors): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $e) echo clean($e) . '<br>'; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="<?= $editCat ? 'edit' : 'add' ?>">
                <?php if ($editCat): ?>
                <input type="hidden" name="id" value="<?= $editCat['id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label required">اسم التصنيف</label>
                    <input type="text" name="name" class="form-control" required maxlength="200"
                           value="<?= clean($editCat['name'] ?? '') ?>" placeholder="مثال: موظف، عامل...">
                </div>
                <div class="form-group">
                    <label class="form-label">لون التصنيف</label>
                    <div class="color-input-wrap">
                        <input type="color" name="color" class="form-control-color"
                               value="<?= clean($editCat['color'] ?? '#6c757d') ?>">
                        <span class="color-hex" id="colorHex"><?= clean($editCat['color'] ?? '#6c757d') ?></span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">ترتيب العرض</label>
                    <input type="number" name="sort_order" class="form-control" min="0"
                           value="<?= (int)($editCat['sort_order'] ?? 0) ?>">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $editCat ? '💾 حفظ التعديلات' : '➕ إضافة' ?>
                    </button>
                    <?php if ($editCat): ?>
                    <a href="categories.php" class="btn btn-secondary">إلغاء</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <!-- قائمة التصنيفات -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">قائمة التصنيفات (<?= count($categories) ?>)</h3>
        </div>
        <div class="card-body p-0">
            <table class="table">
                <thead><tr><th>اللون</th><th>الاسم</th><th>الترتيب</th><th>الإجراءات</th></tr></thead>
                <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><span class="color-circle" style="background:<?= clean($cat['color']) ?>"></span></td>
                    <td><?= clean($cat['name']) ?></td>
                    <td><?= (int)$cat['sort_order'] ?></td>
                    <td>
                        <a href="categories.php?action=edit&id=<?= $cat['id'] ?>" class="btn btn-sm btn-warning">✏ تعديل</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('حذف هذا التصنيف؟')">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
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

<script>
document.querySelector('[name="color"]')?.addEventListener('input', function() {
    document.getElementById('colorHex').textContent = this.value;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
