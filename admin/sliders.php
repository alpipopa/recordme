<?php
/**
 * إدارة السلايدر
 */
$pageTitle = 'إدارة السلايدر';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

// معالجة الطلبات السريعة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    // تفعيل/تعطيل
    if (post('action') === 'toggle') {
        $sid    = (int)post('id');
        $status = post('status') === 'active' ? 'inactive' : 'active';
        dbExecute("UPDATE sliders SET status=? WHERE id=?", [$status, $sid]);
        redirect(APP_URL . '/admin/sliders.php');
    }

    // حذف
    if (post('action') === 'delete') {
        $sid  = (int)post('id');
        $row  = dbQueryOne("SELECT image FROM sliders WHERE id=?", [$sid]);
        if ($row && $row['image']) {
            $imgPath = UPLOAD_PATH . '/sliders/' . $row['image'];
            if (file_exists($imgPath)) @unlink($imgPath);
        }
        dbExecute("DELETE FROM sliders WHERE id=?", [$sid]);
        $_SESSION['flash'] = ['type'=>'success','message'=>'تم حذف الشريحة بنجاح.'];
        redirect(APP_URL . '/admin/sliders.php');
    }

    // تحديث الترتيب
    if (post('action') === 'reorder') {
        $orders = $_POST['orders'] ?? [];
        foreach ($orders as $sid => $ord) {
            dbExecute("UPDATE sliders SET sort_order=? WHERE id=?", [(int)$ord, (int)$sid]);
        }
        redirect(APP_URL . '/admin/sliders.php');
    }
}

$sliders = dbQuery("SELECT * FROM sliders ORDER BY sort_order ASC, id ASC");
?>

<div class="page-header">
    <h1 class="page-title">🖼 إدارة السلايدر</h1>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/index.php" target="_blank" class="btn btn-info">👁 معاينة</a>
        <a href="<?= APP_URL ?>/admin/slider_add.php" class="btn btn-primary">➕ إضافة شريحة</a>
    </div>
</div>

<?= getFlashMessage() ?>

<?php if (empty($sliders)): ?>
<div class="empty-state">
    <div class="empty-icon">🖼</div>
    <h3>لا توجد شرائح حتى الآن</h3>
    <p>أضف شريحة جديدة لتظهر في الصفحة الرئيسية</p>
    <a href="<?= APP_URL ?>/admin/slider_add.php" class="btn btn-primary">➕ إضافة أولى شريحة</a>
</div>
<?php else: ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">الشرائح (<?= count($sliders) ?>)</h3>
        <small class="text-muted">يمكنك إعادة الترتيب بتغيير أرقام الترتيب</small>
    </div>
    <div class="table-responsive">
        <form method="POST" id="reorderForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="reorder">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th style="width:100px">الصورة</th>
                        <th>العنوان</th>
                        <th>الوصف</th>
                        <th style="width:90px">الترتيب</th>
                        <th style="width:90px">الحالة</th>
                        <th style="width:150px">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sliders as $i => $slide): ?>
                <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td>
                        <?php if ($slide['image'] && file_exists(UPLOAD_PATH . '/sliders/' . $slide['image'])): ?>
                        <img src="<?= UPLOAD_URL ?>/sliders/<?= clean($slide['image']) ?>"
                             alt="<?= clean($slide['title']) ?>"
                             class="slider-thumb" loading="lazy">
                        <?php else: ?>
                        <div class="slider-thumb-ph">🖼</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= clean($slide['title']) ?></strong><br>
                        <small class="text-muted"><?= formatDate($slide['created_at']) ?></small>
                    </td>
                    <td>
                        <span class="slide-desc-preview">
                            <?= clean(mb_substr($slide['description'] ?? '', 0, 80)) ?>
                            <?= mb_strlen($slide['description'] ?? '') > 80 ? '...' : '' ?>
                        </span>
                    </td>
                    <td>
                        <input type="number" name="orders[<?= $slide['id'] ?>]"
                               value="<?= (int)$slide['sort_order'] ?>"
                               class="form-control form-control-sm text-center"
                               min="0" max="999">
                    </td>
                    <td>
                        <form method="POST" style="display:inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id"     value="<?= $slide['id'] ?>">
                            <input type="hidden" name="status" value="<?= $slide['status'] ?>">
                            <button type="submit"
                                    class="badge <?= $slide['status']==='active' ? 'badge-success' : 'badge-secondary' ?>"
                                    style="border:none;cursor:pointer;padding:5px 10px;">
                                <?= $slide['status'] === 'active' ? '✅ فعّال' : '⭕ معطّل' ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="<?= APP_URL ?>/admin/slider_edit.php?id=<?= $slide['id'] ?>"
                               class="btn btn-sm btn-warning">✏</a>
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('حذف هذه الشريحة نهائياً؟')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id"     value="<?= $slide['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div style="padding:12px 16px;border-top:1px solid var(--border)">
                <button type="submit" class="btn btn-primary btn-sm">💾 حفظ الترتيب</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
