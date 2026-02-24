<?php
/**
 * تعديل بيانات شخص
 */
$pageTitle = 'تعديل بيانات شخص';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$id     = (int)get('id');
$person = getPersonById($id);

if (!$person) {
    redirectWithMessage(APP_URL . '/admin/persons.php', 'danger', 'الشخص غير موجود.');
}

$categories   = getCategories();
$customFields = getCustomFields();
$governorates = dbQuery("SELECT * FROM governorates ORDER BY name");
$errors = [];

// قيم الحقول المخصصة الحالية
$currentCustomValues = [];
foreach ($person['custom_fields'] as $cf) {
    $currentCustomValues[$cf['field_name']] = $cf['value'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    
    $old = $person; // للسجل
    $new = [
        'category_id'     => post('category_id'),
        'full_name'       => post('full_name'),
        'id_type'         => post('id_type', 'national_id'),
        'id_number'       => post('id_number'),
        'phone'           => post('phone'),
        'phone2'          => post('phone2'),
        'marital_status'  => post('marital_status', 'single'),
        'children_total'  => post('children_total', '0'),
        'children_male'   => post('children_male', '0'),
        'children_female' => post('children_female', '0'),
        'job_title'       => post('job_title'),
        'residence'       => post('residence'),
        'residence_type'  => post('residence_type'),
        'district'        => post('district'),
        'governorate'     => post('governorate'),
        'chronic_diseases'=> post('chronic_diseases'),
        'notes'           => post('notes'),
    ];
    
    if (empty($new['full_name']))    $errors[] = 'الاسم الكامل مطلوب.';
    if (empty($new['category_id'])) $errors[] = 'التصنيف مطلوب.';
    
    // رفع صورة الهوية إذا تم اختيار واحدة
    $idImage = $person['id_image'];
    if (!empty($_FILES['id_image']['name'])) {
        $uploaded = uploadIdImage($_FILES['id_image']);
        if ($uploaded === false) {
            $errors[] = 'فشل رفع صورة الهوية.';
        } else {
            if ($idImage && file_exists(UPLOAD_PATH . '/' . $idImage)) @unlink(UPLOAD_PATH . '/' . $idImage);
            $idImage = $uploaded;
        }
    }
    if (isset($_POST['remove_id_image']) && $_POST['remove_id_image'] === '1') {
        if ($idImage && file_exists(UPLOAD_PATH . '/' . $idImage)) @unlink(UPLOAD_PATH . '/' . $idImage);
        $idImage = '';
    }

    // رفع الصورة الشخصية
    $personalPhoto = $person['personal_photo'];
    if (!empty($_FILES['personal_photo']['name'])) {
        $uploaded = uploadPersonImage($_FILES['personal_photo'], 'photo');
        if ($uploaded === false) {
            $errors[] = 'فشل رفع الصورة الشخصية.';
        } else {
            if ($personalPhoto && file_exists(UPLOAD_PATH . '/' . $personalPhoto)) @unlink(UPLOAD_PATH . '/' . $personalPhoto);
            $personalPhoto = $uploaded;
        }
    }
    if (isset($_POST['remove_personal_photo']) && $_POST['remove_personal_photo'] === '1') {
        if ($personalPhoto && file_exists(UPLOAD_PATH . '/' . $personalPhoto)) @unlink(UPLOAD_PATH . '/' . $personalPhoto);
        $personalPhoto = '';
    }
    
    if (empty($errors)) {
        try {
            $db = getDB();
            $db->beginTransaction();
            
            dbExecute(
                "UPDATE persons SET category_id=?, full_name=?, id_type=?, id_number=?, phone=?, phone2=?,
                    id_image=?, personal_photo=?, marital_status=?, children_total=?, children_male=?, children_female=?,
                    job_title=?, residence=?, residence_type=?, district=?, governorate=?, chronic_diseases=?, notes=?, updated_by=?
                 WHERE id=?",
                [
                    (int)$new['category_id'], $new['full_name'], $new['id_type'], $new['id_number'],
                    $new['phone'], $new['phone2'], $idImage, $personalPhoto, $new['marital_status'],
                    (int)$new['children_total'], (int)$new['children_male'], (int)$new['children_female'],
                    $new['job_title'], $new['residence'], $new['residence_type'], $new['district'], $new['governorate'],
                    $new['chronic_diseases'], $new['notes'], $_SESSION['user_id'] ?? null, $id,
                ]
            );
            
            // تحديث الحقول المخصصة
            foreach ($customFields as $field) {
                $val = $_POST['custom_' . $field['field_name']] ?? '';
                if (is_array($val)) $val = implode(',', $val);
                dbExecute(
                    "INSERT INTO custom_field_values (person_id, field_id, value) VALUES (?,?,?)
                     ON DUPLICATE KEY UPDATE value = VALUES(value)",
                    [$id, $field['id'], $val]
                );
            }
            
            $db->commit();
            logAction('update', 'persons', $id, $old, $new);
            redirectWithMessage(APP_URL . '/admin/persons.php', 'success', 'تم تحديث البيانات بنجاح.');
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'حدث خطأ: ' . $e->getMessage();
        }
    }
    
    // إعادة دمج البيانات مع القيم المُدخلة
    $person = array_merge($person, $new);
    $person['id_image'] = $idImage;
}

$p = $person; // alias قصير
?>

<div class="page-header">
    <div>
        <h1 class="page-title">✏ تعديل: <?= clean($p['full_name']) ?></h1>
        <p class="page-subtitle"># <?= $id ?> — مسجل في <?= formatDate($p['created_at']) ?></p>
    </div>
    <div class="page-actions">
        <a href="persons.php" class="btn btn-secondary">← العودة</a>
        <a href="print_report.php?person_id=<?= $id ?>" class="btn btn-info" target="_blank">🖨 طباعة</a>
    </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <strong>يرجى تصحيح الأخطاء التالية:</strong>
    <ul class="mb-0 mt-5"><?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" id="personForm" novalidate>
    <?= csrfField() ?>
    
    <div class="tabs-wrapper">
        <div class="tabs-nav">
            <button type="button" class="tab-btn active" data-tab="tab-personal">👤 البيانات الشخصية</button>
            <button type="button" class="tab-btn" data-tab="tab-social">💍 الاجتماعية</button>
            <button type="button" class="tab-btn" data-tab="tab-residence">🏠 السكن</button>
            <button type="button" class="tab-btn" data-tab="tab-health">🏥 الصحية</button>
            <?php if (!empty($customFields)): ?>
            <button type="button" class="tab-btn" data-tab="tab-custom">🔧 إضافية</button>
            <?php endif; ?>
        </div>
        
        <!-- البيانات الشخصية -->
        <div class="tab-content active" id="tab-personal">
            <div class="card"><div class="card-body">
                <div class="form-grid form-grid--2">
                    <div class="form-group form-group--full">
                        <label class="form-label required">الاسم الكامل</label>
                        <input type="text" name="full_name" class="form-control" required
                               value="<?= clean($p['full_name']) ?>" maxlength="255">
                    </div>
                    <div class="form-group">
                        <label class="form-label required">التصنيف</label>
                        <select name="category_id" class="form-control" required>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($p['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= clean($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">نوع الهوية</label>
                        <select name="id_type" class="form-control">
                            <option value="national_id"     <?= ($p['id_type']==='national_id')     ? 'selected':'' ?>>بطاقة وطنية</option>
                            <option value="passport"        <?= ($p['id_type']==='passport')        ? 'selected':'' ?>>جواز سفر</option>
                            <option value="driving_license" <?= ($p['id_type']==='driving_license') ? 'selected':'' ?>>رخصة قيادة</option>
                            <option value="other"           <?= ($p['id_type']==='other')           ? 'selected':'' ?>>أخرى</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">رقم الهوية</label>
                        <input type="text" name="id_number" class="form-control" value="<?= clean($p['id_number']) ?>" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label class="form-label">رقم الهاتف الرئيسي</label>
                        <input type="tel" name="phone" class="form-control" value="<?= clean($p['phone']) ?>" maxlength="50">
                    </div>
                    <div class="form-group">
                        <label class="form-label">رقم هاتف إضافي</label>
                        <input type="tel" name="phone2" class="form-control" value="<?= clean($p['phone2']) ?>" maxlength="50">
                    </div>
                    <!-- الصورة الشخصية -->
                    <div class="form-group">
                        <label class="form-label">الصورة الشخصية</label>
                        <?php if ($p['personal_photo']): ?>
                        <div class="current-image-wrap" id="photoWrap">
                            <img src="<?= UPLOAD_URL . '/' . clean($p['personal_photo']) ?>" alt="صورة" class="current-image" style="width:80px; height:80px; object-fit:cover;">
                            <label class="checkbox-label mt-5">
                                <input type="checkbox" name="remove_personal_photo" value="1">
                                <span>حذف</span>
                            </label>
                        </div>
                        <?php endif; ?>
                        <div class="file-upload-wrapper mt-10">
                            <input type="file" name="personal_photo" id="personal_photo" accept="image/*"
                                   class="file-upload-input" onchange="previewImage(this, 'previewPhotoImg', 'photoPreview')">
                            <label for="personal_photo" class="file-upload-label">
                                <span class="file-upload-icon">👤</span>
                                <span>تغيير الصورة الشخصية</span>
                            </label>
                        </div>
                        <div id="photoPreview" class="image-preview-wrap" style="display:none">
                            <img id="previewPhotoImg" src="" alt="معاينة" class="image-preview" style="width:80px; height:80px; object-fit:cover;">
                        </div>
                    </div>

                    <!-- صورة الهوية -->
                    <div class="form-group">
                        <label class="form-label">صورة الهوية</label>
                        <?php if ($p['id_image']): ?>
                        <div class="current-image-wrap" id="idWrap">
                            <img src="<?= UPLOAD_URL . '/' . clean($p['id_image']) ?>" alt="هوية" class="current-image">
                            <label class="checkbox-label mt-5">
                                <input type="checkbox" name="remove_id_image" value="1">
                                <span>حذف</span>
                            </label>
                        </div>
                        <?php endif; ?>
                        <div class="file-upload-wrapper mt-10">
                            <input type="file" name="id_image" id="id_image" accept="image/*"
                                   class="file-upload-input" onchange="previewImage(this, 'previewIdImg', 'idPreview')">
                            <label for="id_image" class="file-upload-label">
                                <span class="file-upload-icon">📷</span>
                                <span>تغيير صورة الهوية</span>
                            </label>
                        </div>
                        <div id="idPreview" class="image-preview-wrap" style="display:none">
                            <img id="previewIdImg" src="" alt="معاينة" class="image-preview">
                        </div>
                    </div>
                </div>
            </div></div>
        </div>
        
        <!-- الاجتماعية -->
        <div class="tab-content" id="tab-social">
            <div class="card"><div class="card-body">
                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label class="form-label">الحالة الاجتماعية</label>
                        <select name="marital_status" class="form-control">
                            <option value="single"   <?= ($p['marital_status']==='single')   ? 'selected':'' ?>>أعزب / عزباء</option>
                            <option value="married"  <?= ($p['marital_status']==='married')  ? 'selected':'' ?>>متزوج / متزوجة</option>
                            <option value="divorced" <?= ($p['marital_status']==='divorced') ? 'selected':'' ?>>مطلق / مطلقة</option>
                            <option value="widowed"  <?= ($p['marital_status']==='widowed')  ? 'selected':'' ?>>أرمل / أرملة</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">المسمى الوظيفي</label>
                        <input type="text" name="job_title" class="form-control" value="<?= clean($p['job_title']) ?>" maxlength="200">
                    </div>
                    <div class="form-group">
                        <label class="form-label">إجمالي الأطفال</label>
                        <input type="number" name="children_total" class="form-control" min="0" max="99" value="<?= (int)$p['children_total'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">عدد الذكور</label>
                        <input type="number" name="children_male" class="form-control" min="0" max="99" value="<?= (int)$p['children_male'] ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">عدد الإناث</label>
                        <input type="number" name="children_female" class="form-control" min="0" max="99" value="<?= (int)$p['children_female'] ?>">
                    </div>
                </div>
            </div></div>
        </div>
        
        <!-- السكن -->
        <div class="tab-content" id="tab-residence">
            <div class="card"><div class="card-body">
                <div class="form-grid form-grid--2">
                    <div class="form-group form-group--full">
                        <label class="form-label">عنوان السكن</label>
                        <input type="text" name="residence" class="form-control" value="<?= clean($p['residence']) ?>" maxlength="255">
                    </div>
                    <div class="form-group">
                        <label class="form-label">نوع السكن</label>
                        <select name="residence_type" class="form-control">
                            <option value="">-- اختر --</option>
                            <option value="owned"  <?= ($p['residence_type'] === 'owned')  ? 'selected' : '' ?>>ملـك</option>
                            <option value="rented" <?= ($p['residence_type'] === 'rented') ? 'selected' : '' ?>>إيجار</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">الحي / المنطقة</label>
                        <input type="text" name="district" class="form-control" value="<?= clean($p['district']) ?>" maxlength="200">
                    </div>
                    <div class="form-group">
                        <label class="form-label">المحافظة</label>
                        <select name="governorate" class="form-control">
                            <option value="">-- اختر --</option>
                            <?php foreach ($governorates as $gov): ?>
                            <option value="<?= clean($gov['name']) ?>" <?= ($p['governorate']===$gov['name']) ? 'selected' : '' ?>>
                                <?= clean($gov['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div></div>
        </div>
        
        <!-- الصحية -->
        <div class="tab-content" id="tab-health">
            <div class="card"><div class="card-body">
                <div class="form-group">
                    <label class="form-label">الأمراض المزمنة</label>
                    <textarea name="chronic_diseases" class="form-control" rows="4"><?= clean($p['chronic_diseases'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="4"><?= clean($p['notes'] ?? '') ?></textarea>
                </div>
            </div></div>
        </div>
        
        <!-- الحقول المخصصة -->
        <?php if (!empty($customFields)): ?>
        <div class="tab-content" id="tab-custom">
            <div class="card"><div class="card-body">
                <div class="form-grid form-grid--2">
                <?php foreach ($customFields as $field): ?>
                <div class="form-group <?= ($field['field_type']==='textarea') ? 'form-group--full' : '' ?>">
                    <label class="form-label <?= $field['is_required'] ? 'required' : '' ?>"><?= clean($field['field_label']) ?></label>
                    <?php
                    $fn  = 'custom_' . $field['field_name'];
                    $fv  = $currentCustomValues[$field['field_name']] ?? '';
                    switch ($field['field_type']):
                        case 'textarea': ?>
                        <textarea name="<?= clean($fn) ?>" class="form-control" rows="3"><?= clean($fv) ?></textarea>
                        <?php break;
                        case 'select':
                            $opts = json_decode($field['field_options'] ?? '[]', true) ?? [];
                        ?>
                        <select name="<?= clean($fn) ?>" class="form-control">
                            <option value="">-- اختر --</option>
                            <?php foreach ($opts as $opt): ?>
                            <option value="<?= clean($opt) ?>" <?= ($fv===$opt)?'selected':'' ?>><?= clean($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php break;
                        case 'checkbox': ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="<?= clean($fn) ?>" value="1" <?= $fv ? 'checked' : '' ?>>
                            <span>نعم</span>
                        </label>
                        <?php break;
                        default: ?>
                        <input type="<?= clean($field['field_type']) ?>" name="<?= clean($fn) ?>"
                               class="form-control" value="<?= clean($fv) ?>">
                        <?php break;
                    endswitch; ?>
                </div>
                <?php endforeach; ?>
                </div>
            </div></div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="form-footer">
        <button type="submit" class="btn btn-primary btn-lg">💾 حفظ التعديلات</button>
        <a href="persons.php" class="btn btn-secondary btn-lg">✖ إلغاء</a>
    </div>
</form>

<script>
function previewImage(input, imgId, wrapId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById(imgId).src = e.target.result;
            document.getElementById(wrapId).style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
