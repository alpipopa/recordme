<?php
/**
 * إضافة شخص جديد
 */
$pageTitle = 'إضافة شخص جديد';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$categories   = getCategories();
$customFields = getCustomFields();
$governorates = dbQuery("SELECT * FROM governorates ORDER BY name");
$errors       = [];
$old          = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    
    // جمع البيانات
    $old = [
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
    
    // التحقق من صحة البيانات
    if (empty($old['full_name']))    $errors[] = 'الاسم الكامل مطلوب.';
    if (empty($old['category_id'])) $errors[] = 'التصنيف مطلوب.';
    if (strlen($old['full_name']) > 255) $errors[] = 'الاسم طويل جداً.';
    
    // رفع صورة الهوية
    $idImage = '';
    if (!empty($_FILES['id_image']['name'])) {
        $uploaded = uploadIdImage($_FILES['id_image']);
        if ($uploaded === false) {
            $errors[] = 'فشل رفع صورة الهوية. تأكد من أن الصورة بصيغة JPG/PNG/GIF/WebP وأقل من 5MB.';
        } else {
            $idImage = $uploaded;
        }
    }

    // رفع الصورة الشخصية
    $personalPhoto = '';
    if (!empty($_FILES['personal_photo']['name'])) {
        $uploaded = uploadPersonImage($_FILES['personal_photo'], 'photo');
        if ($uploaded === false) {
            $errors[] = 'فشل رفع الصورة الشخصية.';
        } else {
            $personalPhoto = $uploaded;
        }
    }
    
    if (empty($errors)) {
        try {
            $db = getDB();
            $db->beginTransaction();
            
            // إدراج الشخص
            dbExecute(
                "INSERT INTO persons (category_id, full_name, id_type, id_number, phone, phone2, id_image, personal_photo,
                    marital_status, children_total, children_male, children_female, job_title,
                    residence, residence_type, district, governorate, chronic_diseases, notes, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    (int)$old['category_id'],
                    $old['full_name'],
                    $old['id_type'],
                    $old['id_number'],
                    $old['phone'],
                    $old['phone2'],
                    $idImage,
                    $personalPhoto,
                    $old['marital_status'],
                    (int)$old['children_total'],
                    (int)$old['children_male'],
                    (int)$old['children_female'],
                    $old['job_title'],
                    $old['residence'],
                    $old['residence_type'],
                    $old['district'],
                    $old['governorate'],
                    $old['chronic_diseases'],
                    $old['notes'],
                    $_SESSION['user_id'] ?? null,
                ]
            );
            $personId = (int)dbLastId();
            
            // حفظ قيم الحقول المخصصة
            foreach ($customFields as $field) {
                $val = $_POST['custom_' . $field['field_name']] ?? '';
                if (is_array($val)) $val = implode(',', $val);
                dbExecute(
                    "INSERT INTO custom_field_values (person_id, field_id, value) VALUES (?,?,?)
                     ON DUPLICATE KEY UPDATE value = VALUES(value)",
                    [$personId, $field['id'], $val]
                );
            }
            
            $db->commit();
            logAction('create', 'persons', $personId, [], $old);
            
            // إرسال تنبيه للمسؤولين
            sendNotification(
                "👤 إضافة شخص جديد",
                "قام " . clean($_SESSION['full_name']) . " بإضافة " . clean($old['full_name']),
                "success",
                APP_URL . "/admin/person_view.php?id=" . $personId
            );

            redirectWithMessage(APP_URL . '/admin/persons.php', 'success', 'تم إضافة الشخص بنجاح.');
        } catch (Exception $e) {
            getDB()->rollBack();
            $errors[] = 'حدث خطأ أثناء الحفظ: ' . ($e->getMessage());
        }
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">➕ إضافة شخص جديد</h1>
        <p class="page-subtitle">أدخل بيانات الشخص المراد تسجيله</p>
    </div>
    <a href="persons.php" class="btn btn-secondary">← العودة للقائمة</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <strong>يرجى تصحيح الأخطاء التالية:</strong>
    <ul class="mb-0 mt-5">
        <?php foreach ($errors as $e): ?><li><?= clean($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" id="personForm" novalidate>
    <?= csrfField() ?>
    
    <!-- Tabs -->
    <div class="tabs-wrapper">
        <div class="tabs-nav">
            <button type="button" class="tab-btn active" data-tab="tab-personal">👤 البيانات الشخصية</button>
            <button type="button" class="tab-btn" data-tab="tab-social">💍 البيانات الاجتماعية</button>
            <button type="button" class="tab-btn" data-tab="tab-residence">🏠 بيانات السكن</button>
            <button type="button" class="tab-btn" data-tab="tab-health">🏥 البيانات الصحية</button>
            <?php if (!empty($customFields)): ?>
            <button type="button" class="tab-btn" data-tab="tab-custom">🔧 حقول إضافية</button>
            <?php endif; ?>
        </div>
        
        <!-- تبويب البيانات الشخصية -->
        <div class="tab-content active" id="tab-personal">
            <div class="card">
                <div class="card-header"><h3 class="card-title">البيانات الشخصية</h3></div>
                <div class="card-body">
                    <div class="form-grid form-grid--2">
                        <!-- الاسم الكامل -->
                        <div class="form-group form-group--full">
                            <label class="form-label required">الاسم الكامل</label>
                            <input type="text" name="full_name" class="form-control" required
                                   value="<?= clean($old['full_name'] ?? '') ?>" maxlength="255"
                                   placeholder="أدخل الاسم الرباعي كاملاً">
                        </div>
                        <!-- التصنيف -->
                        <div class="form-group">
                            <label class="form-label required">التصنيف</label>
                            <select name="category_id" class="form-control" required>
                                <option value="">-- اختر التصنيف --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (($old['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                    <?= clean($cat['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- نوع الهوية -->
                        <div class="form-group">
                            <label class="form-label">نوع الهوية</label>
                            <select name="id_type" class="form-control">
                                <option value="national_id"     <?= (($old['id_type'] ?? 'national_id') === 'national_id')     ? 'selected':'' ?>>بطاقة وطنية</option>
                                <option value="passport"        <?= (($old['id_type'] ?? '') === 'passport')        ? 'selected':'' ?>>جواز سفر</option>
                                <option value="driving_license" <?= (($old['id_type'] ?? '') === 'driving_license') ? 'selected':'' ?>>رخصة قيادة</option>
                                <option value="other"           <?= (($old['id_type'] ?? '') === 'other')           ? 'selected':'' ?>>أخرى</option>
                            </select>
                        </div>
                        <!-- رقم الهوية -->
                        <div class="form-group">
                            <label class="form-label">رقم الهوية</label>
                            <input type="text" name="id_number" class="form-control"
                                   value="<?= clean($old['id_number'] ?? '') ?>" maxlength="100">
                        </div>
                        <!-- رقم الهاتف -->
                        <div class="form-group">
                            <label class="form-label">رقم الهاتف الرئيسي</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="<?= clean($old['phone'] ?? '') ?>" maxlength="50">
                        </div>
                        <!-- رقم الهاتف 2 -->
                        <div class="form-group">
                            <label class="form-label">رقم هاتف إضافي</label>
                            <input type="tel" name="phone2" class="form-control"
                                   value="<?= clean($old['phone2'] ?? '') ?>" maxlength="50">
                        </div>
                        <!-- صورة الشخص -->
                        <div class="form-group">
                            <label class="form-label">الصورة الشخصية</label>
                            <div class="file-upload-wrapper">
                                <input type="file" name="personal_photo" id="personal_photo" accept="image/*"
                                       class="file-upload-input" onchange="previewImage(this, 'previewPhotoImg', 'photoPreviewWrap')">
                                <label for="personal_photo" class="file-upload-label">
                                    <span class="file-upload-icon">👤</span>
                                    <span class="file-upload-text">اختر الصورة الشخصية</span>
                                </label>
                            </div>
                            <div id="photoPreviewWrap" class="image-preview-wrap" style="display:none">
                                <img id="previewPhotoImg" src="" alt="معاينة" class="image-preview" style="width:100px; height:100px; object-fit:cover;">
                                <button type="button" class="btn btn-sm btn-danger mt-5" onclick="clearPreview('personal_photo', 'photoPreviewWrap')">✖ إزالة</button>
                            </div>
                        </div>
                        <!-- صورة الهوية -->
                        <div class="form-group">
                            <label class="form-label">صورة الهوية</label>
                            <div class="file-upload-wrapper">
                                <input type="file" name="id_image" id="id_image" accept="image/*"
                                       class="file-upload-input" onchange="previewImage(this, 'previewIdImg', 'idPreviewWrap')">
                                <label for="id_image" class="file-upload-label">
                                    <span class="file-upload-icon">📷</span>
                                    <span class="file-upload-text">اختر صورة الهوية</span>
                                </label>
                            </div>
                            <div id="idPreviewWrap" class="image-preview-wrap" style="display:none">
                                <img id="previewIdImg" src="" alt="معاينة" class="image-preview">
                                <button type="button" class="btn btn-sm btn-danger mt-5" onclick="clearPreview('id_image', 'idPreviewWrap')">✖ إزالة</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- تبويب البيانات الاجتماعية -->
        <div class="tab-content" id="tab-social">
            <div class="card">
                <div class="card-header"><h3 class="card-title">البيانات الاجتماعية والوظيفية</h3></div>
                <div class="card-body">
                    <div class="form-grid form-grid--2">
                        <div class="form-group">
                            <label class="form-label">الحالة الاجتماعية</label>
                            <select name="marital_status" class="form-control">
                                <option value="single"   <?= (($old['marital_status'] ?? 'single') === 'single')   ? 'selected':'' ?>>أعزب / عزباء</option>
                                <option value="married"  <?= (($old['marital_status'] ?? '') === 'married')  ? 'selected':'' ?>>متزوج / متزوجة</option>
                                <option value="divorced" <?= (($old['marital_status'] ?? '') === 'divorced') ? 'selected':'' ?>>مطلق / مطلقة</option>
                                <option value="widowed"  <?= (($old['marital_status'] ?? '') === 'widowed')  ? 'selected':'' ?>>أرمل / أرملة</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">المسمى الوظيفي</label>
                            <input type="text" name="job_title" class="form-control"
                                   value="<?= clean($old['job_title'] ?? '') ?>" maxlength="200">
                        </div>
                        <div class="form-group">
                            <label class="form-label">إجمالي عدد الأطفال</label>
                            <input type="number" name="children_total" class="form-control" min="0" max="99"
                                   value="<?= (int)($old['children_total'] ?? 0) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">عدد الذكور</label>
                            <input type="number" name="children_male" class="form-control" min="0" max="99"
                                   value="<?= (int)($old['children_male'] ?? 0) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">عدد الإناث</label>
                            <input type="number" name="children_female" class="form-control" min="0" max="99"
                                   value="<?= (int)($old['children_female'] ?? 0) ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- تبويب بيانات السكن -->
        <div class="tab-content" id="tab-residence">
            <div class="card">
                <div class="card-header"><h3 class="card-title">بيانات السكن والموقع</h3></div>
                <div class="card-body">
                    <div class="form-grid form-grid--2">
                        <div class="form-group form-group--full">
                            <label class="form-label">عنوان السكن</label>
                            <input type="text" name="residence" class="form-control"
                                   value="<?= clean($old['residence'] ?? '') ?>" maxlength="255"
                                   placeholder="العنوان التفصيلي">
                        </div>
                        <div class="form-group">
                            <label class="form-label">نوع السكن</label>
                            <select name="residence_type" class="form-control">
                                <option value="">-- اختر --</option>
                                <option value="owned"  <?= (($old['residence_type'] ?? '') === 'owned')  ? 'selected' : '' ?>>ملـك</option>
                                <option value="rented" <?= (($old['residence_type'] ?? '') === 'rented') ? 'selected' : '' ?>>إيجار</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">الحي / المنطقة</label>
                            <input type="text" name="district" class="form-control"
                                   value="<?= clean($old['district'] ?? '') ?>" maxlength="200">
                        </div>
                        <div class="form-group">
                            <label class="form-label">المحافظة</label>
                            <select name="governorate" class="form-control">
                                <option value="">-- اختر المحافظة --</option>
                                <?php foreach ($governorates as $gov): ?>
                                <option value="<?= clean($gov['name']) ?>" <?= (($old['governorate'] ?? '') === $gov['name']) ? 'selected' : '' ?>>
                                    <?= clean($gov['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- تبويب البيانات الصحية -->
        <div class="tab-content" id="tab-health">
            <div class="card">
                <div class="card-header"><h3 class="card-title">البيانات الصحية</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">الأمراض المزمنة</label>
                        <textarea name="chronic_diseases" class="form-control" rows="4"
                                  placeholder="سرد الأمراض المزمنة إن وجدت..."><?= clean($old['chronic_diseases'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ملاحظات إضافية</label>
                        <textarea name="notes" class="form-control" rows="4"
                                  placeholder="أي ملاحظات أخرى..."><?= clean($old['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- تبويب الحقول المخصصة -->
        <?php if (!empty($customFields)): ?>
        <div class="tab-content" id="tab-custom">
            <div class="card">
                <div class="card-header"><h3 class="card-title">🔧 الحقول الإضافية</h3></div>
                <div class="card-body">
                    <div class="form-grid form-grid--2">
                    <?php foreach ($customFields as $field): ?>
                    <div class="form-group <?= in_array($field['field_type'], ['textarea']) ? 'form-group--full' : '' ?>">
                        <label class="form-label <?= $field['is_required'] ? 'required' : '' ?>">
                            <?= clean($field['field_label']) ?>
                        </label>
                        <?php
                        $fieldName = 'custom_' . $field['field_name'];
                        $fieldVal  = $old[$fieldName] ?? '';
                        switch ($field['field_type']):
                            case 'textarea': ?>
                            <textarea name="<?= clean($fieldName) ?>" class="form-control" rows="3"
                                      <?= $field['is_required'] ? 'required' : '' ?>><?= clean($fieldVal) ?></textarea>
                            <?php break;
                            case 'select':
                                $opts = json_decode($field['field_options'] ?? '[]', true) ?? [];
                            ?>
                            <select name="<?= clean($fieldName) ?>" class="form-control" <?= $field['is_required'] ? 'required' : '' ?>>
                                <option value="">-- اختر --</option>
                                <?php foreach ($opts as $opt): ?>
                                <option value="<?= clean($opt) ?>" <?= ($fieldVal === $opt) ? 'selected' : '' ?>><?= clean($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php break;
                            case 'checkbox': ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="<?= clean($fieldName) ?>" value="1"
                                       <?= $fieldVal ? 'checked' : '' ?>>
                                <span>نعم</span>
                            </label>
                            <?php break;
                            default: ?>
                            <input type="<?= clean($field['field_type']) ?>" name="<?= clean($fieldName) ?>"
                                   class="form-control" value="<?= clean($fieldVal) ?>"
                                   <?= $field['is_required'] ? 'required' : '' ?>>
                            <?php break;
                        endswitch; ?>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- أزرار الحفظ -->
    <div class="form-footer">
        <button type="submit" class="btn btn-primary btn-lg">💾 حفظ البيانات</button>
        <a href="persons.php" class="btn btn-secondary btn-lg">✖ إلغاء</a>
    </div>
</form>

<script>
function previewImage(input, imgId, wrapId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(imgId).src = e.target.result;
            document.getElementById(wrapId).style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function clearPreview(inputId, wrapId) {
    document.getElementById(inputId).value = '';
    document.getElementById(wrapId).style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
