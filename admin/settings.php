<?php
/**
 * إعدادات النظام
 */
$pageTitle = 'إعدادات النظام';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin');

// فئات الإعدادات
$settings = [
    'site_name'         => getSetting('site_name', 'نظام تسجيل بيانات الأشخاص'),
    'site_subtitle'     => getSetting('site_subtitle', 'لوحة التحكم الإدارية'),
    'items_per_page'    => getSetting('items_per_page', '25'),
    'print_header'      => getSetting('print_header', 'الجمهورية اليمنية'),
    'print_footer'      => getSetting('print_footer', 'نظام تسجيل البيانات - سري'),
    'logo_path'         => getSetting('logo_path', ''),
    'header_portrait'   => getSetting('header_portrait', ''),
    'footer_portrait'   => getSetting('footer_portrait', ''),
    'header_landscape'  => getSetting('header_landscape', ''),
    'footer_landscape'  => getSetting('footer_landscape', ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    requireCsrf();
    
    $updatedCount = 0;
    $errors = [];

    // 1. تحديث النصوص البسيطة
    $toUpdate = ['site_name', 'site_subtitle', 'items_per_page', 'print_header', 'print_footer'];
    foreach ($toUpdate as $key) {
        $val = post($key);
        if (updateSetting($key, $val)) {
            $updatedCount++;
        }
    }

    // 2. معالجة رفع الصور والشعارات
    $imageFields = [
        'logo'             => 'logo_path',
        'header_portrait'  => 'header_portrait',
        'footer_portrait'  => 'footer_portrait',
        'header_landscape' => 'header_landscape',
        'footer_landscape' => 'footer_landscape'
    ];

    foreach ($imageFields as $fileKey => $settingKey) {
        if (!empty($_FILES[$fileKey]['name'])) {
            $file = $_FILES[$fileKey];
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (in_array($mime, $allowed)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = $fileKey . '_' . time() . '.' . $ext;
                $dest = UPLOAD_PATH . '/' . $filename;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    // حذف الملف القديم إذا وجد
                    if (!empty($settings[$settingKey]) && file_exists(UPLOAD_PATH . '/' . $settings[$settingKey])) {
                        @unlink(UPLOAD_PATH . '/' . $settings[$settingKey]);
                    }
                    updateSetting($settingKey, $filename);
                    $updatedCount++;
                } else {
                    $errors[] = 'فشل في رفع ملف: ' . $fileKey;
                }
            } else {
                $label = str_replace('_', ' ', $fileKey);
                $errors[] = "نوع ملف ($label) غير مدعوم. يرجى اختيار JPG, PNG, WEBP أو SVG.";
            }
        }
    }

    if ($updatedCount > 0 || empty($errors)) {
        logAction('update', 'settings', null, $settings, dbQuery("SELECT * FROM settings"));
        redirectWithMessage(APP_URL . '/admin/settings.php', 'success', 'تم حفظ الإعدادات بنجاح.');
    } else {
        $errorMsg = implode('<br>', $errors);
        redirectWithMessage(APP_URL . '/admin/settings.php', 'danger', $errorMsg);
    }
}
?>

<div class="page-header">
    <div class="page-title-wrap">
        <h1 class="page-title">⚙ إعدادات النظام</h1>
        <p class="page-subtitle">تخصيص هوية الموقع، التقارير، والخيارات العامة</p>
    </div>
</div>

<?= getFlashMessage() ?>

<form method="POST" action="" enctype="multipart/form-data">
    <?= csrfField() ?>
    
    <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr;">
        
        <!-- الهوية العامة -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🏢 هوية الموقع</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">اسم الموقع</label>
                    <input type="text" name="site_name" class="form-control" value="<?= clean($settings['site_name']) ?>" required>
                    <small class="text-muted">يظهر في الهيدر وعنوان المتصفح.</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">العنوان الفرعي</label>
                    <input type="text" name="site_subtitle" class="form-control" value="<?= clean($settings['site_subtitle']) ?>">
                    <small class="text-muted">يظهر تحت العنوان الرئيسي في بعض الصفحات.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">عدد العناصر في الصفحة</label>
                    <input type="number" name="items_per_page" class="form-control" value="<?= clean($settings['items_per_page']) ?>" min="5" max="100">
                    <small class="text-muted">عدد السجلات المعروضة في جداول البيانات.</small>
                </div>
            </div>
        </div>

        <!-- شعار الموقع -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🖼 شعار الموقع (Logo)</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-20">
                    <?php if (!empty($settings['logo_path']) && file_exists(UPLOAD_PATH . '/' . $settings['logo_path'])): ?>
                        <img src="<?= UPLOAD_URL ?>/<?= clean($settings['logo_path']) ?>" alt="Logo" style="max-height: 100px; border: 1px solid var(--border); padding: 5px; border-radius: 8px;">
                        <p class="mt-10"><small class="text-muted">الشعار الحالي</small></p>
                    <?php else: ?>
                        <div style="height: 100px; width: 100px; background: var(--light); border-radius: 8px; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 30px;">📋</div>
                        <p class="mt-10"><small class="text-muted">لا يوجد شعار مخصص</small></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">تغيير الشعار</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <small class="text-muted">يفضل استخدام خلفية شفافة (PNG) أو بصيغة SVG.</small>
                </div>
            </div>
        </div>

        <!-- إعدادات الطباعة -->
        <div class="card" style="grid-column: span 2;">
            <div class="card-header">
                <h3 class="card-title">🖨 نصوص وصور تقارير الطباعة</h3>
            </div>
            <div class="card-body">
                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label class="form-label">رأس التقرير الرسمي (نص)</label>
                        <textarea name="print_header" class="form-control" rows="2"><?= clean($settings['print_header']) ?></textarea>
                        <small class="text-muted">النص الافتراضي في أعلى التقارير.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">تذييل التقرير الرسمي (نص)</label>
                        <textarea name="print_footer" class="form-control" rows="2"><?= clean($settings['print_footer']) ?></textarea>
                        <small class="text-muted">النص الافتراضي في أسفل كل صفحة.</small>
                    </div>
                </div>

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--border);">
                
                <div class="form-grid form-grid--2">
                    <!-- Portrait Settings -->
                    <div style="background: var(--light); padding: 15px; border-radius: 8px;">
                        <h4 style="margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">📄 الطباعة الرأسية (Portrait)</h4>
                        
                        <div class="form-group">
                            <label class="form-label">صورة الرأس (Header Image)</label>
                            <?php if ($settings['header_portrait']): ?>
                                <img src="<?= UPLOAD_URL ?>/<?= clean($settings['header_portrait']) ?>" style="max-width: 100%; height: 50px; display: block; margin-bottom: 5px; border: 1px solid #ccc;">
                            <?php endif; ?>
                            <input type="file" name="header_portrait" class="form-control" accept="image/*">
                        </div>

                        <div class="form-group">
                            <label class="form-label">صورة التذييل (Footer Image)</label>
                            <?php if ($settings['footer_portrait']): ?>
                                <img src="<?= UPLOAD_URL ?>/<?= clean($settings['footer_portrait']) ?>" style="max-width: 100%; height: 50px; display: block; margin-bottom: 5px; border: 1px solid #ccc;">
                            <?php endif; ?>
                            <input type="file" name="footer_portrait" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <!-- Landscape Settings -->
                    <div style="background: var(--light); padding: 15px; border-radius: 8px;">
                        <h4 style="margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">📃 الطباعة العرضية (Landscape)</h4>
                        
                        <div class="form-group">
                            <label class="form-label">صورة الرأس (Header Image)</label>
                            <?php if ($settings['header_landscape']): ?>
                                <img src="<?= UPLOAD_URL ?>/<?= clean($settings['header_landscape']) ?>" style="max-width: 100%; height: 50px; display: block; margin-bottom: 5px; border: 1px solid #ccc;">
                            <?php endif; ?>
                            <input type="file" name="header_landscape" class="form-control" accept="image/*">
                        </div>

                        <div class="form-group">
                            <label class="form-label">صورة التذييل (Footer Image)</label>
                            <?php if ($settings['footer_landscape']): ?>
                                <img src="<?= UPLOAD_URL ?>/<?= clean($settings['footer_landscape']) ?>" style="max-width: 100%; height: 50px; display: block; margin-bottom: 5px; border: 1px solid #ccc;">
                            <?php endif; ?>
                            <input type="file" name="footer_landscape" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="card mt-20">
        <div class="card-body text-center">
            <button type="submit" name="save_settings" class="btn btn-primary" style="padding: 12px 40px;">
                💾 حفظ جميع الإعدادات
            </button>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
