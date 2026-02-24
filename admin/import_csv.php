<?php
/**
 * استيراد البيانات من ملف CSV
 */
$pageTitle = 'استيراد من CSV';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin', 'manager');

$stats = [
    'total'   => 0,
    'success' => 0,
    'failed'  => 0,
    'errors'  => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv'])) {
    requireCsrf();
    
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        // جلب أسماء الأعمدة (Header)
        $header = fgetcsv($handle);
        
        // خريطة للأعمدة (Mapping)
        // المتوقع: الاسم، الرقم الوطني، الهاتف، المحافظة، التصنيف، الحالة الاجتماعية
        
        $categories = dbQuery("SELECT id, name FROM categories");
        $catMap = [];
        foreach($categories as $c) $catMap[trim($c['name'])] = $c['id'];

        while (($row = fgetcsv($handle)) !== FALSE) {
            $stats['total']++;
            
            try {
                // ماب بسيط للأعمدة (نفترض ترتيب معين أو نبحث بالهيدر)
                // للتسهيل في هذا الإصدار سنفترض الترتيب التالي:
                // 0: الاسم، 1: نوع الهوية، 2: رقم الهوية، 3: الهاتف، 4: المحافظة، 5: التصنيف، 6: الحالة الاجتماعية
                
                $fullName  = trim($row[0] ?? '');
                $idType    = trim($row[1] ?? 'national_id');
                $idNumber  = trim($row[2] ?? '');
                $phone     = trim($row[3] ?? '');
                $gov       = trim($row[4] ?? '');
                $catName   = trim($row[5] ?? '');
                $marital   = trim($row[6] ?? 'single');

                if (empty($fullName)) {
                    $stats['failed']++;
                    $stats['errors'][] = "سطر " . ($stats['total']+1) . ": الاسم فارغ.";
                    continue;
                }

                $catId = $catMap[$catName] ?? 1; // الافتراضي أول تصنيف

                $sql = "INSERT INTO persons (full_name, id_type, id_number, phone, governorate, category_id, marital_status, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                dbExecute($sql, [$fullName, $idType, $idNumber, $phone, $gov, $catId, $marital, $_SESSION['user_id']]);
                $stats['success']++;

            } catch (Exception $e) {
                $stats['failed']++;
                $stats['errors'][] = "سطر " . ($stats['total']+1) . ": " . $e->getMessage();
            }
        }
        fclose($handle);
        
        logAction('import', 'persons', null, [], ['total' => $stats['total'], 'success' => $stats['success']]);
        
        // إرسال تنبيه للمسؤولين
        if ($stats['success'] > 0) {
            sendNotification(
                "📥 استيراد جماعي مكتمل",
                "تم استيراد " . $stats['success'] . " سجل جديد بنجاح بواسطة " . clean($_SESSION['full_name']),
                "info",
                APP_URL . "/admin/persons.php"
            );
        }
    }
}
?>

<div class="page-header">
    <div class="page-title-wrap">
        <h1 class="page-title">📥 استيراد بيانات جماعي</h1>
        <p class="page-subtitle">رفع ملف CSV لإضافة مئات السجلات بضغطة واحدة</p>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/admin/persons.php" class="btn btn-secondary">🔙 العودة للأشخاص</a>
    </div>
</div>

<?= getFlashMessage() ?>

<?php if ($stats['total'] > 0): ?>
<div class="alert alert-info">
    <strong>ملخص الاستيراد:</strong><br>
    ✅ تم بنجاح: <?= $stats['success'] ?><br>
    ❌ فشل: <?= $stats['failed'] ?><br>
    📊 الإجمالي: <?= $stats['total'] ?>
</div>

<?php if (!empty($stats['errors'])): ?>
<div class="card mb-20" style="border-color: #fca5a5;">
    <div class="card-header" style="background: #fef2f2; color: #991b1b;">
        <h3 class="card-title">⚠️ تفاصيل الأخطاء</h3>
    </div>
    <div class="card-body" style="max-height: 200px; overflow-y: auto; font-size: 13px;">
        <?php foreach($stats['errors'] as $err): ?>
            <div style="margin-bottom: 5px; border-bottom: 1px solid #fee2e2; padding-bottom: 5px;"><?= clean($err) ?></div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<div class="dashboard-grid" style="grid-template-columns: 1fr 400px;">
    
    <div class="card">
        <div class="card-header"><h3 class="card-title">📁 اختيار ملف الاستيراد</h3></div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                <div class="form-group">
                    <label class="form-label">ملف CSV (UTF-8)</label>
                    <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    <small class="text-muted">تأكد أن الملف مشفر بصيغة UTF-8 لضمان ظهور اللغة العربية بشكل صحيح.</small>
                </div>
                <div class="mt-20">
                    <button type="submit" name="import_csv" class="btn btn-primary" style="padding: 12px 30px;">🚀 بدء عملية الاستيراد</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">📝 تعليمات وشروط الملف</h3></div>
        <div class="card-body" style="font-size: 13px; line-height: 1.8;">
            <p>يجب أن يحتوي ملف CSV على الأعمدة التالية بالترتيب:</p>
            <ol style="padding-right: 20px;">
                <li><strong>الاسم الكامل</strong> (إجباري)</li>
                <li><strong>نوع الهوية</strong> (national_id, passport, etc)</li>
                <li><strong>رقم الهوية</strong></li>
                <li><strong>رقم الهاتف</strong></li>
                <li><strong>المحافظة</strong></li>
                <li><strong>اسم التصنيف</strong> (مطابق للأسماء في النظام)</li>
                <li><strong>الحالة الاجتماعية</strong> (single, married, etc)</li>
            </ol>
            <div class="alert alert-warning" style="padding: 10px; margin-top: 15px; font-size: 12px;">
                ملاحظة: الصدر الأول في الملف (Header) يتم تجاهله تلقائياً.
            </div>
            <a href="#" class="btn btn-sm btn-outline-info btn-block" onclick="generateSampleCSV(); return false;">📂 تحميل نموذج تجريبي</a>
        </div>
    </div>

</div>

<script>
function generateSampleCSV() {
    const headers = "الاسم,نوع الهوية,رقم الهوية,الهاتف,المحافظة,التصنيف,الحالة الاجتماعية\n";
    const row1 = "أحمد محمد علي,national_id,010101010,777777777,صنعاء,شخص عادي,married\n";
    const row2 = "سارة أحمد,passport,A1234567,711111111,عدن,موظف,single\n";
    
    const csvContent = "\uFEFF" + headers + row1 + row2; // \uFEFF for Excel Arabic support
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", "sample_import.csv");
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
