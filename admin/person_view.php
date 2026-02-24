<?php
/**
 * عرض بيانات الشخص والمستندات
 */
$pageTitle = 'عرض بيانات الشخص';
require_once __DIR__ . '/../includes/header.php';

$id = (int)get('id');
$person = getPersonById($id);

if (!$person) {
    redirectWithMessage(APP_URL . '/admin/persons.php', 'danger', 'الشخص غير موجود.');
}

// معالجة رفع مستند جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_doc'])) {
    requireCsrf();
    if (!empty($_FILES['document']['name'])) {
        if (uploadPersonDocument($id, $_FILES['document'])) {
            logAction('create', 'person_documents', $id, [], ['file' => $_FILES['document']['name']]);
            redirectWithMessage(APP_URL . '/admin/person_view.php?id=' . $id, 'success', 'تم رفع المستند بنجاح.');
        } else {
            redirectWithMessage(APP_URL . '/admin/person_view.php?id=' . $id, 'danger', 'فشل في رفع المستند. تأكد من النوع والحجم.');
        }
    }
}

// معالجة حذف مستند
if (isset($_GET['delete_doc'])) {
    $docId = (int)$_GET['delete_doc'];
    if (deleteDocument($docId)) {
        logAction('delete', 'person_documents', $docId);
        redirectWithMessage(APP_URL . '/admin/person_view.php?id=' . $id, 'success', 'تم حذف المستند بنجاح.');
    }
}

$documents = getPersonDocuments($id);
?>

<div class="page-header">
    <div class="page-title-wrap">
        <h1 class="page-title">👤 ملف الشخص: <?= clean($person['full_name']) ?></h1>
        <p class="page-subtitle">عرض البيانات الشخصية وإدارة المستندات المرفقة</p>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/admin/person_id_card.php?id=<?= $id ?>" target="_blank" class="btn btn-info">🛡 بطاقة الهوية</a>
        <a href="<?= APP_URL ?>/admin/person_edit.php?id=<?= $id ?>" class="btn btn-warning">✏ تعديل البيانات</a>
        <a href="<?= APP_URL ?>/admin/persons.php" class="btn btn-secondary">🔙 العودة للقائمة</a>
    </div>
</div>

<?= getFlashMessage() ?>

<div class="dashboard-grid" style="grid-template-columns: 1fr 350px;">
    
    <!-- الجانب الأيمن: البيانات والمستندات -->
    <div class="card-column">
        
        <!-- التبويبات -->
        <div class="card" style="margin-bottom: 0px; border-bottom: none; border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
            <div class="card-header" style="padding: 0 20px;">
                <div class="nav-tabs" style="display: flex; gap: 20px;">
                    <button class="tab-btn active" onclick="showTab('info')" id="tab-info-btn" style="padding: 15px 0; background: none; border: none; border-bottom: 3px solid var(--primary); color: var(--primary); font-weight: 700; cursor: pointer;">📄 البيانات الأساسية</button>
                    <button class="tab-btn" onclick="showTab('docs')" id="tab-docs-btn" style="padding: 15px 0; background: none; border: none; border-bottom: 3px solid transparent; color: var(--secondary); font-weight: 700; cursor: pointer;">📂 المستندات المرفقة (<?= count($documents) ?>)</button>
                </div>
            </div>
        </div>

        <!-- محتوى تبويب البيانات -->
        <div id="tab-info" class="tab-content">
            <div class="card" style="border-top-left-radius: 0; border-top-right-radius: 0;">
                <div class="card-body">
                    <div class="info-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="info-item">
                            <label style="display: block; color: var(--secondary); font-size: 13px; margin-bottom: 5px;">نوع الهوية</label>
                            <strong><?= getIdTypeLabel($person['id_type']) ?></strong>
                        </div>
                        <div class="info-item">
                            <label style="display: block; color: var(--secondary); font-size: 13px; margin-bottom: 5px;">رقم الهوية</label>
                            <strong><?= clean($person['id_number']) ?: '—' ?></strong>
                        </div>
                        <div class="info-item">
                            <label style="display: block; color: var(--secondary); font-size: 13px; margin-bottom: 5px;">رقم الهاتف</label>
                            <strong><?= clean($person['phone']) ?: '—' ?></strong>
                        </div>
                        <div class="info-item">
                            <label style="display: block; color: var(--secondary); font-size: 13px; margin-bottom: 5px;">الحالة الاجتماعية</label>
                            <strong><?= getMaritalStatusLabel($person['marital_status']) ?></strong>
                        </div>
                        <div class="info-item">
                            <label style="display: block; color: var(--secondary); font-size: 13px; margin-bottom: 5px;">المحافظة</label>
                            <strong><?= clean($person['governorate']) ?: '—' ?></strong>
                        </div>
                        <div class="info-item">
                            <label style="display: block; color: var(--secondary); font-size: 13px; margin-bottom: 5px;">نوع السكن</label>
                            <strong><?= getResidenceTypeLabel($person['residence_type']) ?></strong>
                        </div>
                        <div class="info-item">
                            <label style="display: block; color: var(--secondary); font-size: 13px; margin-bottom: 5px;">الوظيفة</label>
                            <strong><?= clean($person['job_title']) ?: '—' ?></strong>
                        </div>
                    </div>

                    <h4 style="margin: 30px 0 15px; border-bottom: 2px solid var(--light); padding-bottom: 10px;">🔧 الحقول المخصصة</h4>
                    <div class="info-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <?php foreach ($person['custom_fields'] as $cf): ?>
                        <div class="info-item">
                            <label style="display: block; color: var(--secondary); font-size: 13px; margin-bottom: 5px;"><?= clean($cf['field_label']) ?></label>
                            <strong><?= clean($cf['value']) ?: '—' ?></strong>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- محتوى تبويب المستندات -->
        <div id="tab-docs" class="tab-content" style="display: none;">
            <div class="card" style="border-top-left-radius: 0; border-top-right-radius: 0;">
                <div class="card-body">
                    <div class="doc-upload-form" style="background: var(--light); padding: 20px; border-radius: 12px; margin-bottom: 30px;">
                        <h4 style="margin-bottom: 15px;">📥 رفع مستند جديد</h4>
                        <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 15px; align-items: flex-end;">
                            <?= csrfField() ?>
                            <div style="flex: 1;">
                                <label class="form-label">اختر الملف (PDF, صور, Word)</label>
                                <input type="file" name="document" class="form-control" required>
                            </div>
                            <button type="submit" name="upload_doc" class="btn btn-primary">🚀 رفع المستند</button>
                        </form>
                    </div>

                    <div class="docs-list">
                        <?php if (empty($documents)): ?>
                            <p class="text-center text-muted">لا توجد مستندات إضافية مرفوعة لهذا الشخص.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>اسم الملف</th>
                                            <th>النوع</th>
                                            <th>الحجم</th>
                                            <th>تاريخ الرفع</th>
                                            <th style="width: 150px;">الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <td><strong><?= clean($doc['file_name']) ?></strong></td>
                                            <td><span class="badge"><?= strtoupper(explode('/', $doc['file_type'])[1] ?? 'File') ?></span></td>
                                            <td class="text-muted"><?= round($doc['file_size'] / 1024, 1) ?> KB</td>
                                            <td><?= formatDate($doc['created_at']) ?></td>
                                            <td>
                                                <div style="display: flex; gap: 8px;">
                                                    <a href="<?= APP_URL ?>/uploads/documents/<?= $doc['file_path'] ?>" target="_blank" class="btn btn-sm btn-outline-info">👁 عرض</a>
                                                    <a href="?id=<?= $id ?>&delete_doc=<?= $doc['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنت متأكد من حذف هذا المستند؟')">🗑 حذف</a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- الجانب الأيسر: الصور والوثائق -->
    <div class="card-column">
        <!-- الصورة الشخصية -->
        <div class="card mb-20">
            <div class="card-header"><h3 class="card-title">👤 الصورة الشخصية</h3></div>
            <div class="card-body text-center">
                <?php if ($person['personal_photo'] && file_exists(UPLOAD_PATH . '/' . $person['personal_photo'])): ?>
                    <img src="<?= UPLOAD_URL ?>/<?= clean($person['personal_photo']) ?>" alt="Photo" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--border); box-shadow: var(--shadow);">
                <?php else: ?>
                    <div style="width: 150px; height: 150px; background: var(--light); border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 50px; color: var(--secondary);">👤</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- صورة الهوية -->
        <div class="card">
            <div class="card-header"><h3 class="card-title">🆔 صورة الهوية الرئيسية</h3></div>
            <div class="card-body text-center">
                <?php if ($person['id_image'] && file_exists(UPLOAD_PATH . '/' . $person['id_image'])): ?>
                    <a href="<?= UPLOAD_URL ?>/<?= clean($person['id_image']) ?>" target="_blank">
                        <img src="<?= UPLOAD_URL ?>/<?= clean($person['id_image']) ?>" alt="ID Image" style="width: 100%; border-radius: 8px; box-shadow: var(--shadow);">
                    </a>
                    <p class="mt-10"><small class="text-muted">انقر على الصورة للعرض بالحجم الكامل</small></p>
                <?php else: ?>
                    <div style="aspect-ratio: 3/2; background: var(--light); border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--secondary);">
                        <span style="font-size: 40px; margin-bottom: 10px;">🖼</span>
                        <span>لا توجد صورة هوية</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-20">
            <div class="card-header"><h3 class="card-title">⏲ التوقيت</h3></div>
            <div class="card-body">
                <div style="font-size: 13px;">
                    <p><strong>تاريخ التسجيل:</strong><br> <?= formatDate($person['created_at'], true) ?></p>
                    <p class="mt-10"><strong>آخر تحديث:</strong><br> <?= formatDate($person['updated_at'], true) ?></p>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function showTab(tabName) {
    // إخفاء الكل
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.style.color = 'var(--secondary)';
        b.style.borderBottomColor = 'transparent';
    });

    // إظهار التبويب المختار
    document.getElementById('tab-' + tabName).style.display = 'block';
    
    // تمييز الزر
    const btn = document.getElementById('tab-' + tabName + '-btn');
    btn.style.color = 'var(--primary)';
    btn.style.borderBottomColor = 'var(--primary)';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
