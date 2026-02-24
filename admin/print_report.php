<?php
/**
 * نظام الطباعة الاحترافي
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// هل طباعة شخص واحد أم قائمة؟
$personId = (int)get('person_id');

// إعدادات الطباعة المُختارة
$settings = [
    'orientation'   => post('orientation', get('orientation', 'portrait')),
    'show_logo'     => isset($_POST['show_logo'])   || (get('show_logo')   === '1'),
    'show_title'    => isset($_POST['show_title'])  || (get('show_title')  !== '0'),
    'show_date'     => isset($_POST['show_date'])   || (get('show_date')   !== '0'),
    'show_sign'     => isset($_POST['show_sign'])   || (get('show_sign')   !== '0'),
    'report_title'  => post('report_title', get('report_title', 'تقرير بيانات الأشخاص')),
    'footer_text'   => post('footer_text', get('footer_text', getSetting('print_footer', ''))),
    // الحقول المختارة للطباعة
    'fields'        => $_POST['fields'] ?? null, // null = كل الحقول
];

$isPrint = isset($_POST['do_print']) || get('print') === '1';

// جلب الأشخاص
if ($personId) {
    $person = getPersonById($personId);
    $persons = $person ? [$person] : [];
} else {
    // فلاتر البحث من URL
    $filters = [
        'name'        => get('name'),
        'category_id' => get('category_id'),
        'governorate' => get('governorate'),
    ];
    $result  = getPersons(array_filter($filters), 1, 1000);
    $persons = $result['data'];
    
    // جلب الحقول المخصصة لكل شخص
    foreach ($persons as &$p) {
        $customVals = dbQuery(
            "SELECT cf.field_label, cfv.value FROM custom_fields cf
             LEFT JOIN custom_field_values cfv ON cfv.field_id=cf.id AND cfv.person_id=?
             WHERE cf.is_active=1 ORDER BY cf.sort_order",
            [$p['id']]
        );
        $p['custom_fields'] = $customVals;
    }
    unset($p);
}

// الحقول الافتراضية للطباعة
$defaultFields = [
    'full_name'       => 'الاسم الكامل',
    'id_type'         => 'نوع الهوية',
    'id_number'       => 'رقم الهوية',
    'phone'           => 'رقم الهاتف',
    'category_name'   => 'التصنيف',
    'marital_status'  => 'الحالة الاجتماعية',
    'children_total'  => 'عدد الأطفال',
    'job_title'       => 'المسمى الوظيفي',
    'governorate'     => 'المحافظة',
    'district'        => 'الحي',
    'residence'       => 'عنوان السكن',
    'chronic_diseases'=> 'الأمراض المزمنة',
    'created_at'      => 'تاريخ التسجيل',
];

$selectedFields = $settings['fields'] ?? array_keys($defaultFields);

// جلب صور الهيدر والفوتير حسب الاتجاه
$orientation = $settings['orientation'] === 'landscape' ? 'landscape' : 'portrait';
$headerImg   = getSetting('header_' . $orientation);
$footerImg   = getSetting('footer_' . $orientation);

if ($isPrint):
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?= clean($settings['report_title']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', 'Tahoma', sans-serif; font-size: 11pt; color: #000; direction: rtl; padding-top: <?= $headerImg ? '0' : '20px' ?>; }
        @page { size: A4 <?= $settings['orientation'] ?>; margin: 1cm; }
        
        .report-header-img { width: 100%; display: block; margin-bottom: 20px; }
        .report-footer-img { width: 100%; display: block; position: fixed; bottom: 0; left: 0; }
        
        .print-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .print-header h1 { font-size: 16pt; margin-bottom: 5px; }
        .print-header .print-date { font-size: 9pt; color: #555; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #2c3e50; color: #fff; padding: 6px 8px; text-align: right; font-size: 10pt; }
        td { border: 1px solid #ccc; padding: 5px 8px; font-size: 10pt; vertical-align: top; }
        tr:nth-child(even) td { background: #f9f9f9; }
        
        .person-card { border: 1px solid #aaa; margin-bottom: 20px; padding: 15px; page-break-inside: avoid; }
        .person-card h3 { background: #2c3e50; color: #fff; margin: -15px -15px 10px; padding: 8px 15px; }
        .person-card .field-row { display: flex; border-bottom: 1px solid #eee; padding: 4px 0; }
        .person-card .field-label { width: 35%; font-weight: bold; font-size: 10pt; }
        .person-card .field-value { font-size: 10pt; }
        
        .print-footer { position: fixed; bottom: <?= $footerImg ? '40px' : '0' ?>; width: 100%; border-top: 1px solid #ccc; 
                         padding-top: 5px; text-align: center; font-size: 9pt; color: #555; background: #fff; }
        
        .page-break { page-break-before: always; }
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11pt; }
            .print-footer { position: fixed; bottom: <?= $footerImg ? '40px' : '0' ?>; }
        }
        .print-btn { 
            position: fixed; top: 20px; left: 20px; padding: 10px 20px; 
            background: #2c3e50; color: #fff; border: none; border-radius: 5px; 
            cursor: pointer; font-size: 14pt; z-index: 9999;
        }
    </style>
</head>
<body>
<button class="print-btn no-print" onclick="window.print()">🖨 طباعة</button>

<?php if ($headerImg && file_exists(UPLOAD_PATH . '/' . $headerImg)): ?>
    <img src="<?= UPLOAD_URL ?>/<?= clean($headerImg) ?>" class="report-header-img">
<?php endif; ?>

<!-- رأس التقرير النصي -->
<?php if (!$headerImg && ($settings['show_title'] || $settings['show_date'])): ?>
<div class="print-header">
    <?php if ($settings['show_title']): ?>
    <h1><?= clean($settings['report_title']) ?></h1>
    <?php endif; ?>
    <?php if ($settings['show_date']): ?>
    <div class="print-date">تاريخ الطباعة: <?= date('Y/m/d H:i') ?></div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($personId && count($persons) === 1):
    // طباعة بطاقة شخص واحد
    $p = $persons[0];
?>
<div class="person-card">
    <h3>📋 <?= clean($p['full_name']) ?></h3>
    <?php foreach ($defaultFields as $fk => $fl):
        if (!in_array($fk, $selectedFields)) continue;
        $val = '';
        if ($fk === 'id_type') $val = getIdTypeLabel($p[$fk] ?? '');
        elseif ($fk === 'marital_status') $val = getMaritalStatusLabel($p[$fk] ?? '');
        elseif ($fk === 'created_at') $val = formatDate($p[$fk] ?? '', true);
        else $val = $p[$fk] ?? '';
    ?>
    <?php if ($val): ?>
    <div class="field-row">
        <div class="field-label"><?= clean($fl) ?>:</div>
        <div class="field-value"><?= clean((string)$val) ?></div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
    
    <?php if (!empty($p['custom_fields'])): ?>
    <?php foreach ($p['custom_fields'] as $cf): ?>
    <?php if ($cf['value']): ?>
    <div class="field-row">
        <div class="field-label"><?= clean($cf['field_label']) ?>:</div>
        <div class="field-value"><?= clean($cf['value']) ?></div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php else: 
    // طباعة جدول الأشخاص
?>
<p style="margin-bottom:10px;font-size:10pt;">إجمالي السجلات: <strong><?= count($persons) ?></strong></p>
<table>
    <thead>
        <tr>
            <th>#</th>
            <?php foreach ($defaultFields as $fk => $fl): ?>
            <?php if (in_array($fk, $selectedFields)): ?>
            <th><?= clean($fl) ?></th>
            <?php endif; ?>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($persons as $i => $p): ?>
    <tr>
        <td><?= $i + 1 ?></td>
        <?php foreach ($defaultFields as $fk => $fl):
            if (!in_array($fk, $selectedFields)) continue;
            if ($fk === 'id_type') echo '<td>' . clean(getIdTypeLabel($p[$fk] ?? '')) . '</td>';
            elseif ($fk === 'marital_status') echo '<td>' . clean(getMaritalStatusLabel($p[$fk] ?? '')) . '</td>';
            elseif ($fk === 'created_at') echo '<td>' . formatDate($p[$fk] ?? '') . '</td>';
            else echo '<td>' . clean((string)($p[$fk] ?? '—')) . '</td>';
        endforeach; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- تذييل الطباعة -->
<?php if ($settings['show_sign'] || $settings['footer_text'] || $footerImg): ?>
    <?php if ($footerImg && file_exists(UPLOAD_PATH . '/' . $footerImg)): ?>
        <img src="<?= UPLOAD_URL ?>/<?= clean($footerImg) ?>" class="report-footer-img">
    <?php endif; ?>

    <div class="print-footer">
        <?php if ($settings['footer_text']): ?>
        <?= clean($settings['footer_text']) ?>
        <?php endif; ?>
        <?php if ($settings['show_sign']): ?>
         | توقيع المسؤول: ___________________
        <?php endif; ?>
         | الصفحة: <span class="page-number"></span>
    </div>
<?php endif; ?>

<script>window.onload = () => window.print();</script>
</body>
</html>

<?php
    exit;
endif; // $isPrint

// واجهة إعداد الطباعة
$pageTitle = 'إعداد الطباعة';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">🖨 إعداد الطباعة</h1>
        <p class="page-subtitle">خصّص التقرير قبل الطباعة</p>
    </div>
    <a href="persons.php" class="btn btn-secondary">← العودة</a>
</div>

<form method="POST" target="_blank" id="printForm">
    <?= csrfField() ?>
    <input type="hidden" name="do_print" value="1">
    <?php if ($personId): ?>
    <input type="hidden" name="person_id" value="<?= $personId ?>">
    <?php else: ?>
    <input type="hidden" name="name" value="<?= clean(get('name')) ?>">
    <input type="hidden" name="category_id" value="<?= clean(get('category_id')) ?>">
    <input type="hidden" name="governorate" value="<?= clean(get('governorate')) ?>">
    <?php endif; ?>
    
    <div class="two-col-layout">
        <!-- إعدادات الصفحة -->
        <div class="card">
            <div class="card-header"><h3 class="card-title">⚙ إعدادات الصفحة</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">اتجاه الصفحة</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="orientation" value="portrait" checked>
                            <span>📄 عمودي (Portrait)</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="orientation" value="landscape">
                            <span>📃 أفقي (Landscape)</span>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">عنوان التقرير</label>
                    <input type="text" name="report_title" class="form-control"
                           value="<?= clean($settings['report_title']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">نص التذييل</label>
                    <input type="text" name="footer_text" class="form-control"
                           value="<?= clean($settings['footer_text']) ?>">
                </div>
                <div class="checkbox-list">
                    <label class="checkbox-label"><input type="checkbox" name="show_title" value="1" checked><span>إظهار عنوان التقرير</span></label>
                    <label class="checkbox-label"><input type="checkbox" name="show_date" value="1" checked><span>إظهار تاريخ الطباعة</span></label>
                    <label class="checkbox-label"><input type="checkbox" name="show_sign" value="1" checked><span>توقيع المسؤول</span></label>
                </div>
            </div>
        </div>
        
        <!-- اختيار الحقول -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📋 الحقول المراد طباعتها</h3>
                <div>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="toggleAllFields(true)">تحديد الكل</button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="toggleAllFields(false)">إلغاء الكل</button>
                </div>
            </div>
            <div class="card-body">
                <div class="checkbox-list checkbox-list--cols">
                <?php foreach ($defaultFields as $fk => $fl): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="fields[]" value="<?= $fk ?>" checked class="field-check">
                    <span><?= clean($fl) ?></span>
                </label>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="form-footer">
        <button type="submit" class="btn btn-primary btn-lg">🖨 معاينة وطباعة</button>
        <?php if (!$personId): ?>
        <p class="text-muted mt-10">سيتم طباعة <strong><?= count($persons) ?></strong> سجل</p>
        <?php endif; ?>
    </div>
</form>

<script>
function toggleAllFields(state) {
    document.querySelectorAll('.field-check').forEach(cb => cb.checked = state);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
