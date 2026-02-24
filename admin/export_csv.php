<?php
/**
 * تصدير بيانات الأشخاص إلى CSV
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$doExport = isset($_GET['export']) && $_GET['export'] === '1';

// فلاتر
$filters = [
    'name'           => get('name'),
    'category_id'    => get('category_id'),
    'governorate'    => get('governorate'),
    'marital_status' => get('marital_status'),
];

if ($doExport) {
    // جلب البيانات (حتى 10000 سجل)
    $result  = getPersons(array_filter($filters), 1, 10000);
    $persons = $result['data'];
    
    // سجل التصدير
    logAction('export', 'persons', null, [], ['count' => count($persons), 'filters' => $filters]);
    
    // إعداد الاستجابة
    $filename = 'persons_export_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // BOM لدعم الخط العربي في Excel
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // رأس الجدول
    fputcsv($output, [
        'م', 'الاسم الكامل', 'التصنيف', 'نوع الهوية', 'رقم الهوية',
        'الهاتف الرئيسي', 'هاتف إضافي', 'الحالة الاجتماعية',
        'إجمالي الأطفال', 'ذكور', 'إناث', 'المسمى الوظيفي',
        'عنوان السكن', 'الحي', 'المحافظة', 'الأمراض المزمنة',
        'ملاحظات', 'تاريخ التسجيل',
    ]);
    
    foreach ($persons as $i => $p) {
        fputcsv($output, [
            $i + 1,
            $p['full_name'],
            $p['category_name'] ?? '',
            getIdTypeLabel($p['id_type']),
            $p['id_number'],
            $p['phone'],
            $p['phone2'],
            getMaritalStatusLabel($p['marital_status']),
            $p['children_total'],
            $p['children_male'],
            $p['children_female'],
            $p['job_title'],
            $p['residence'],
            $p['district'],
            $p['governorate'],
            $p['chronic_diseases'],
            $p['notes'],
            formatDate($p['created_at'], true),
        ]);
    }
    
    fclose($output);
    exit;
}

// صفحة إعداد التصدير
$pageTitle = 'تصدير CSV';
require_once __DIR__ . '/../includes/header.php';

$categories   = getCategories();
$governorates = dbQuery("SELECT * FROM governorates ORDER BY name");

// معاينة عدد السجلات
$previewResult = getPersons(array_filter($filters), 1, 1);
$totalRecords  = $previewResult['total'];
?>

<div class="page-header">
    <h1 class="page-title">📊 تصدير البيانات CSV</h1>
    <p class="page-subtitle">تصدير بيانات الأشخاص إلى ملف Excel/CSV</p>
</div>

<?= getFlashMessage() ?>

<div class="card" style="max-width:700px">
    <div class="card-header"><h3 class="card-title">⚙ فلاتر التصدير</h3></div>
    <div class="card-body">
        <form method="GET" action="export_csv.php" id="exportForm">
            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label class="form-label">الاسم</label>
                    <input type="text" name="name" class="form-control" value="<?= clean($filters['name']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">التصنيف</label>
                    <select name="category_id" class="form-control">
                        <option value="">-- الكل --</option>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($filters['category_id']==$c['id'])?'selected':'' ?>>
                            <?= clean($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">المحافظة</label>
                    <select name="governorate" class="form-control">
                        <option value="">-- الكل --</option>
                        <?php foreach ($governorates as $gov): ?>
                        <option value="<?= clean($gov['name']) ?>" <?= ($filters['governorate']===$gov['name'])?'selected':'' ?>>
                            <?= clean($gov['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">الحالة الاجتماعية</label>
                    <select name="marital_status" class="form-control">
                        <option value="">-- الكل --</option>
                        <option value="single"   <?= ($filters['marital_status']==='single')?  'selected':'' ?>>أعزب/عزباء</option>
                        <option value="married"  <?= ($filters['marital_status']==='married')? 'selected':'' ?>>متزوج/متزوجة</option>
                        <option value="divorced" <?= ($filters['marital_status']==='divorced')?'selected':'' ?>>مطلق/مطلقة</option>
                        <option value="widowed"  <?= ($filters['marital_status']==='widowed')? 'selected':'' ?>>أرمل/أرملة</option>
                    </select>
                </div>
            </div>
            
            <!-- معاينة عدد السجلات -->
            <div class="export-preview-box">
                <span class="export-preview-icon">📊</span>
                <div>
                    <strong id="recordCount"><?= number_format($totalRecords) ?></strong> سجل سيتم تصديره
                    <?php if ($totalRecords === 0): ?>
                    <br><small class="text-warning">لا توجد سجلات تطابق الفلاتر المحددة</small>
                    <?php elseif ($totalRecords > 5000): ?>
                    <br><small class="text-warning">قد يستغرق التصدير بعض الوقت لعدد السجلات الكبير</small>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="export" value="1" class="btn btn-success btn-lg"
                        <?= ($totalRecords === 0) ? 'disabled' : '' ?>>
                    📥 تنزيل ملف CSV
                </button>
                <button type="submit" class="btn btn-secondary">🔄 تحديث الفلاتر</button>
            </div>
        </form>
    </div>
</div>

<!-- معلومات الملف -->
<div class="card mt-20" style="max-width:700px">
    <div class="card-header"><h3 class="card-title">ℹ معلومات عن الملف</h3></div>
    <div class="card-body">
        <ul class="info-list">
            <li>📌 الملف بصيغة CSV متوافق مع Microsoft Excel</li>
            <li>📌 يتضمن BOM لدعم اللغة العربية في Excel</li>
            <li>📌 يشمل 18 حقلاً للبيانات الأساسية</li>
            <li>📌 يتم تسجيل عملية التصدير في سجل النشاط</li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
