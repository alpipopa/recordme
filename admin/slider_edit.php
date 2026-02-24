<?php
/**
 * تعديل شريحة السلايدر
 */
$pageTitle = 'تعديل الشريحة';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$id = (int)get('id');
if (!$id) redirect(APP_URL . '/admin/sliders.php');

$slide  = dbQueryOne("SELECT * FROM sliders WHERE id=?", [$id]);
if (!$slide) redirect(APP_URL . '/admin/sliders.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $title     = post('title');
    $desc      = post('description');
    $linkUrl   = post('link_url');
    $sortOrder = (int)post('sort_order', '0');
    $status    = post('status', 'active');
    $imageName = $slide['image']; // الصورة الحالية
    $deleteImg = isset($_POST['delete_image']);

    if (empty($title)) $errors[] = 'عنوان الشريحة مطلوب.';

    // حذف الصورة القديمة
    if ($deleteImg && $imageName) {
        $imgPath = UPLOAD_PATH . '/sliders/' . $imageName;
        if (file_exists($imgPath)) @unlink($imgPath);
        $imageName = null;
    }

    // رفع صورة جديدة
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // حذف القديمة أولاً
        if ($imageName) {
            $imgPath = UPLOAD_PATH . '/sliders/' . $imageName;
            if (file_exists($imgPath)) @unlink($imgPath);
        }
        $uploaded = uploadSliderImage($_FILES['image']);
        if ($uploaded === false) {
            $errors[] = 'فشل رفع الصورة. تأكد من الصيغة (JPG/PNG/WEBP) والحجم أقل من 5 MB.';
        } else {
            $imageName = $uploaded;
        }
    }

    if (empty($errors)) {
        dbExecute(
            "UPDATE sliders SET title=?, description=?, image=?, link_url=?, sort_order=?, status=? WHERE id=?",
            [$title, $desc, $imageName, $linkUrl, $sortOrder, $status, $id]
        );
        redirectWithMessage(APP_URL . '/admin/sliders.php', 'success', 'تم تحديث الشريحة بنجاح.');
    }
}

function uploadSliderImage(array $file): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK)  return false;
    if ($file['size'] > MAX_FILE_SIZE)     return false;

    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) return false;

    $ext = match($mimeType) {
        'image/jpeg' => 'jpg', 'image/png' => 'png',
        'image/gif'  => 'gif', 'image/webp' => 'webp',
        default      => 'jpg',
    };

    $dir = UPLOAD_PATH . '/sliders';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = 'slide_' . uniqid('', true) . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], $dir . '/' . $filename) ? $filename : false;
}
?>

<div class="page-header">
    <h1 class="page-title">✏ تعديل الشريحة</h1>
    <a href="sliders.php" class="btn btn-secondary">← العودة</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <span class="alert-icon">⚠</span>
    <div><?php foreach ($errors as $e) echo clean($e) . '<br>'; ?></div>
</div>
<?php endif; ?>

<div class="card" style="max-width:750px">
    <div class="card-header"><h3 class="card-title">🖼 تعديل بيانات الشريحة</h3></div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>

            <div class="form-group">
                <label class="form-label required">عنوان الشريحة</label>
                <input type="text" name="title" class="form-control" required maxlength="255"
                       value="<?= clean($slide['title']) ?>">
            </div>

            <div class="form-group">
                <label class="form-label">وصف الشريحة</label>
                <textarea name="description" class="form-control" rows="4"><?= clean($slide['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">رابط الشريحة (اختياري)</label>
                <input type="url" name="link_url" class="form-control"
                       value="<?= clean($slide['link_url'] ?? '') ?>"
                       placeholder="https://...">
            </div>

            <!-- الصورة الحالية -->
            <div class="form-group">
                <label class="form-label">الصورة</label>

                <?php if ($slide['image'] && file_exists(UPLOAD_PATH . '/sliders/' . $slide['image'])): ?>
                <div class="current-image-wrap" style="margin-bottom:10px">
                    <img src="<?= UPLOAD_URL ?>/sliders/<?= clean($slide['image']) ?>"
                         class="current-image" alt="الصورة الحالية">
                    <label class="checkbox-label" style="margin-top:5px">
                        <input type="checkbox" name="delete_image" value="1">
                        <span class="text-danger">🗑 حذف الصورة الحالية</span>
                    </label>
                </div>
                <label class="form-label" style="font-weight:normal;color:var(--secondary)">استبدال بصورة جديدة (اختياري):</label>
                <?php endif; ?>

                <div class="file-upload-wrapper">
                    <input type="file" name="image" class="file-upload-input" id="editImgInput"
                           accept="image/jpeg,image/png,image/gif,image/webp">
                    <label for="editImgInput" class="file-upload-label">
                        <span class="file-upload-icon">🖼</span>
                        <span id="editFileName">اختر صورة (JPG, PNG, WEBP — حد 5 ميجابايت)</span>
                    </label>
                </div>
                <div class="image-preview-wrap" id="editPreviewWrap" style="display:none;margin-top:10px">
                    <img src="" id="editPreview" class="image-preview" alt="معاينة">
                </div>
            </div>

            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label class="form-label">ترتيب العرض</label>
                    <input type="number" name="sort_order" class="form-control" min="0" max="999"
                           value="<?= (int)$slide['sort_order'] ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-control">
                        <option value="active"   <?= $slide['status']==='active'  ?'selected':'' ?>>✅ فعّال</option>
                        <option value="inactive" <?= $slide['status']==='inactive'?'selected':'' ?>>⭕ معطّل</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">💾 حفظ التعديلات</button>
                <a href="sliders.php" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('editImgInput')?.addEventListener('change', function () {
    const file = this.files[0];
    if (file) {
        document.getElementById('editFileName').textContent = file.name;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('editPreview').src = e.target.result;
            document.getElementById('editPreviewWrap').style.display = '';
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
