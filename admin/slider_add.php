<?php
/**
 * إضافة شريحة سلايدر جديدة
 */
$pageTitle = 'إضافة شريحة';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $title      = post('title');
    $desc       = post('description');
    $linkUrl    = post('link_url');
    $sortOrder  = (int)post('sort_order', '0');
    $status     = post('status', 'active');
    $imageName  = null;

    // التحقق
    if (empty($title)) $errors[] = 'عنوان الشريحة مطلوب.';

    // رفع الصورة
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploaded = uploadSliderImage($_FILES['image']);
        if ($uploaded === false) {
            $errors[] = 'فشل رفع الصورة. تأكد من أن الصيغة (JPG/PNG/WEBP) وأن الحجم أقل من 5 ميجابايت.';
        } else {
            $imageName = $uploaded;
        }
    }

    if (empty($errors)) {
        dbExecute(
            "INSERT INTO sliders (title, description, image, link_url, sort_order, status)
             VALUES (?,?,?,?,?,?)",
            [$title, $desc, $imageName, $linkUrl, $sortOrder, $status]
        );
        redirectWithMessage(APP_URL . '/admin/sliders.php', 'success', 'تم إضافة الشريحة بنجاح.');
    }
}

/**
 * رفع صورة السلايدر بأمان
 */
function uploadSliderImage(array $file): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK)  return false;
    if ($file['size'] > MAX_FILE_SIZE)     return false;

    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) return false;

    $ext  = match($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        default      => 'jpg',
    };

    $dir = UPLOAD_PATH . '/sliders';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = 'slide_' . uniqid('', true) . '.' . $ext;
    $destPath = $dir . '/' . $filename;

    return move_uploaded_file($file['tmp_name'], $destPath) ? $filename : false;
}
?>

<div class="page-header">
    <h1 class="page-title">➕ إضافة شريحة جديدة</h1>
    <a href="sliders.php" class="btn btn-secondary">← العودة</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <span class="alert-icon">⚠</span>
    <div><?php foreach ($errors as $e) echo clean($e) . '<br>'; ?></div>
</div>
<?php endif; ?>

<div class="card" style="max-width:750px">
    <div class="card-header"><h3 class="card-title">🖼 بيانات الشريحة</h3></div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="sliderForm">
            <?= csrfField() ?>

            <div class="form-group">
                <label class="form-label required">عنوان الشريحة</label>
                <input type="text" name="title" class="form-control" required maxlength="255"
                       value="<?= clean(post('title')) ?>"
                       placeholder="مثال: نظام إدارة متكامل">
            </div>

            <div class="form-group">
                <label class="form-label">وصف الشريحة</label>
                <textarea name="description" class="form-control" rows="4" maxlength="1000"
                          placeholder="اكتب وصفاً مختصراً للشريحة..."><?= clean(post('description')) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">رابط الشريحة (اختياري)</label>
                <input type="url" name="link_url" class="form-control"
                       value="<?= clean(post('link_url')) ?>"
                       placeholder="https://...">
                <span class="form-hint">يظهر كزر "اكتشف المزيد" على الشريحة</span>
            </div>

            <div class="form-group">
                <label class="form-label">صورة الشريحة</label>
                <div class="file-upload-wrapper">
                    <input type="file" name="image" class="file-upload-input" id="sliderImgInput"
                           accept="image/jpeg,image/png,image/gif,image/webp">
                    <label for="sliderImgInput" class="file-upload-label">
                        <span class="file-upload-icon">🖼</span>
                        <span id="sliderFileName">اختر صورة (JPG, PNG, WEBP — حد 5 ميجابايت)</span>
                    </label>
                </div>
                <!-- معاينة الصورة -->
                <div class="image-preview-wrap" id="sliderPreviewWrap" style="display:none;margin-top:10px">
                    <img src="" id="sliderPreview" class="image-preview" alt="معاينة">
                </div>
                <span class="form-hint">إذا تُركت فارغة، سيُعرض خلفية لونية تلقائياً</span>
            </div>

            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label class="form-label">ترتيب العرض</label>
                    <input type="number" name="sort_order" class="form-control" min="0" max="999"
                           value="<?= (int)(post('sort_order') ?: 0) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-control">
                        <option value="active"   <?= post('status','active')==='active'  ?'selected':'' ?>>✅ فعّال</option>
                        <option value="inactive" <?= post('status','active')==='inactive'?'selected':'' ?>>⭕ معطّل</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">💾 حفظ الشريحة</button>
                <a href="sliders.php" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>

<script>
// معاينة الصورة
document.getElementById('sliderImgInput')?.addEventListener('change', function () {
    const file = this.files[0];
    const nameEl    = document.getElementById('sliderFileName');
    const previewWrap = document.getElementById('sliderPreviewWrap');
    const preview   = document.getElementById('sliderPreview');
    if (file) {
        nameEl.textContent = file.name;
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            previewWrap.style.display = '';
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
