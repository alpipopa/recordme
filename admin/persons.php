<?php
/**
 * قائمة الأشخاص مع بحث متقدم
 */
$pageTitle = 'قائمة الأشخاص';
require_once __DIR__ . '/../includes/header.php';

// فلاتر البحث
$filters = [
    'q'              => get('q'),
    'name'           => get('name'),
    'category_id'    => get('category_id'),
    'id_number'      => get('id_number'),
    'phone'          => get('phone'),
    'governorate'    => get('governorate'),
    'marital_status' => get('marital_status'),
    'residence_type' => get('residence_type'),
    'job_title'      => get('job_title'),
];

$page    = max(1, (int)get('page', '1'));
$perPage = 25;
$result  = getPersons($filters, $page, $perPage);
$persons = $result['data'];
$total   = $result['total'];

$categories  = getCategories();
$governorates = dbQuery("SELECT * FROM governorates ORDER BY name");

// بناء query string للترقيم
$queryArr = array_filter($filters);
$queryStr = http_build_query($queryArr);
$baseUrl  = APP_URL . '/admin/persons.php' . ($queryStr ? '?' . $queryStr : '');
?>

<div class="page-header">
    <div>
        <h1 class="page-title">👥 قائمة الأشخاص</h1>
        <p class="page-subtitle">إجمالي: <strong><?= number_format($total) ?></strong> شخص</p>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/admin/person_add.php" class="btn btn-primary">➕ إضافة شخص</a>
        <a href="<?= APP_URL ?>/admin/import_csv.php" class="btn btn-outline-primary">📥 استيراد جماعي</a>
        <a href="<?= APP_URL ?>/admin/export_csv.php?<?= $queryStr ?>" class="btn btn-success">📊 تصدير CSV</a>
        <a href="<?= APP_URL ?>/admin/print_report.php?<?= $queryStr ?>" class="btn btn-info" target="_blank">🖨 طباعة</a>
    </div>
</div>

<?= getFlashMessage() ?>

<!-- نموذج البحث المتقدم -->
<div class="card mb-20">
    <div class="card-header collapsible" data-target="searchForm">
        <h3 class="card-title">🔍 بحث متقدم</h3>
        <span class="collapse-icon">▼</span>
    </div>
    <div class="card-body" id="searchForm">
        <form method="GET" action="persons.php" class="search-form">
            <div class="form-group mb-20">
                <label class="form-label">🔍 بحث شامل (في جميع الحقول)</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="q" class="form-control" style="flex: 1;" 
                           value="<?= clean($filters['q']) ?>" placeholder="ابحث بالاسم، الرقم، الهاتف، العنوان، الوظيفة...">
                    <button type="submit" class="btn btn-primary" style="padding: 0 30px;">🔍 ابحث الآن</button>
                </div>
            </div>

            <div class="form-grid form-grid--4">
                <div class="form-group">
                    <label class="form-label">الاسم الكامل</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?= clean($filters['name']) ?>" placeholder="ابحث بالاسم...">
                </div>
                <div class="form-group">
                    <label class="form-label">رقم الهوية</label>
                    <input type="text" name="id_number" class="form-control"
                           value="<?= clean($filters['id_number']) ?>" placeholder="رقم الهوية...">
                </div>
                <div class="form-group">
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= clean($filters['phone']) ?>" placeholder="رقم الهاتف...">
                </div>
                <div class="form-group">
                    <label class="form-label">التصنيف</label>
                    <select name="category_id" class="form-control">
                        <option value="">-- الكل --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($filters['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                            <?= clean($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">المحافظة</label>
                    <select name="governorate" class="form-control">
                        <option value="">-- الكل --</option>
                        <?php foreach ($governorates as $gov): ?>
                        <option value="<?= clean($gov['name']) ?>" <?= ($filters['governorate'] === $gov['name']) ? 'selected' : '' ?>>
                            <?= clean($gov['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">الحالة الاجتماعية</label>
                    <select name="marital_status" class="form-control">
                        <option value="">-- الكل --</option>
                        <option value="single"   <?= ($filters['marital_status']==='single')   ? 'selected' : '' ?>>أعزب/عزباء</option>
                        <option value="married"  <?= ($filters['marital_status']==='married')  ? 'selected' : '' ?>>متزوج/متزوجة</option>
                        <option value="divorced" <?= ($filters['marital_status']==='divorced') ? 'selected' : '' ?>>مطلق/مطلقة</option>
                        <option value="widowed"  <?= ($filters['marital_status']==='widowed')  ? 'selected' : '' ?>>أرمل/أرملة</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">نوع السكن</label>
                    <select name="residence_type" class="form-control">
                        <option value="">-- الكل --</option>
                        <option value="owned"  <?= ($filters['residence_type'] === 'owned')  ? 'selected' : '' ?>>ملـك</option>
                        <option value="rented" <?= ($filters['residence_type'] === 'rented') ? 'selected' : '' ?>>إيجار</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">الوظيفة / المسمى</label>
                    <input type="text" name="job_title" class="form-control"
                           value="<?= clean($filters['job_title']) ?>" placeholder="ابحث بالوظيفة...">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">🔍 بحث</button>
                <a href="persons.php" class="btn btn-secondary">✖ مسح الفلاتر</a>
            </div>
        </form>
    </div>
</div>

<!-- جدول البيانات -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($persons)): ?>
        <div class="empty-state">
            <div class="empty-icon">👥</div>
            <h3>لا توجد نتائج</h3>
            <p>لم يتم العثور على أشخاص بالمعايير المحددة.</p>
            <a href="person_add.php" class="btn btn-primary">إضافة شخص جديد</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-hover" id="personsTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم الكامل</th>
                    <th>نوع الهوية</th>
                    <th>رقم الهوية</th>
                    <th>الهاتف</th>
                    <th>التصنيف</th>
                    <th>المحافظة</th>
                    <th>الحالة الاجتماعية</th>
                    <th>تاريخ التسجيل</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($persons as $person): ?>
            <tr>
                <td><?= $person['id'] ?></td>
                <td>
                    <strong><?= clean($person['full_name']) ?></strong>
                    <?php if ($person['id_image']): ?>
                    <span class="badge badge-sm badge-info ms-1" title="يوجد صورة هوية">📷</span>
                    <?php endif; ?>
                </td>
                <td><?= clean(getIdTypeLabel($person['id_type'])) ?></td>
                <td><code><?= clean($person['id_number']) ?></code></td>
                <td><?= clean($person['phone']) ?></td>
                <td>
                    <span class="badge" style="background:<?= clean($person['category_color'] ?? '#6c757d') ?>">
                        <?= clean($person['category_name'] ?? '—') ?>
                    </span>
                </td>
                <td><?= clean($person['governorate']) ?: '—' ?></td>
                <td><?= clean(getMaritalStatusLabel($person['marital_status'])) ?></td>
                <td><?= formatDate($person['created_at']) ?></td>
                <td>
                    <div class="action-btns">
                        <a href="person_view.php?id=<?= $person['id'] ?>" class="btn btn-sm btn-outline-info" title="عرض الملف">👁</a>
                        <a href="person_edit.php?id=<?= $person['id'] ?>" class="btn btn-sm btn-warning" title="تعديل">✏</a>
                        <a href="print_report.php?person_id=<?= $person['id'] ?>" class="btn btn-sm btn-info" title="طباعة" target="_blank">🖨</a>
                        <a href="person_delete.php?id=<?= $person['id'] ?>" class="btn btn-sm btn-danger btn-confirm"
                           data-confirm="هل تريد حذف هذا الشخص؟ لا يمكن التراجع!" title="حذف">🗑</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        
        <!-- ترقيم الصفحات -->
        <div class="pagination-wrapper">
            <?= renderPagination($page, $result['last_page'], $baseUrl) ?>
            <div class="pagination-info">
                عرض <?= count($persons) ?> من أصل <?= number_format($total) ?> سجل
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
