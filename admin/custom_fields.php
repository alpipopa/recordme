<?php
/**
 * إدارة الحقول الديناميكية المخصصة
 */
$pageTitle = 'الحقول المخصصة';
require_once __DIR__ . '/../includes/header.php';
requireLogin();

$errors  = [];
$editField = null;

if (get('action') === 'edit' && get('id')) {
    $editField = dbQueryOne("SELECT * FROM custom_fields WHERE id=?", [(int)get('id')]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = post('action');
    
    if ($action === 'add' || $action === 'edit') {
        $label    = post('field_label');
        $name     = post('field_name');
        $type     = post('field_type', 'text');
        $opts     = post('field_options');
        $required = isset($_POST['is_required']) ? 1 : 0;
        $order    = (int)post('sort_order', '0');
        
        // توليد field_name تلقائياً إذا لم يُدخل
        if (empty($name)) {
            $name = 'field_' . preg_replace('/[^a-z0-9_]/', '_', strtolower(transliterate($label)));
        }
        $name = preg_replace('/[^a-z0-9_]/', '_', strtolower($name));
        
        if (empty($label)) $errors[] = 'تسمية الحقل مطلوبة.';
        if (empty($name))  $errors[] = 'معرّف الحقل مطلوب.';
        
        // تحويل الخيارات إلى JSON
        $optsJson = null;
        if ($type === 'select' && !empty($opts)) {
            $optsArr  = array_filter(array_map('trim', explode("\n", $opts)));
            $optsJson = json_encode($optsArr, JSON_UNESCAPED_UNICODE);
        }
        
        if (empty($errors)) {
            if ($action === 'add') {
                dbExecute(
                    "INSERT INTO custom_fields (field_name, field_label, field_type, field_options, is_required, sort_order)
                     VALUES (?,?,?,?,?,?)",
                    [$name, $label, $type, $optsJson, $required, $order]
                );
                redirectWithMessage(APP_URL . '/admin/custom_fields.php', 'success', 'تم إضافة الحقل بنجاح.');
            } else {
                $fid = (int)post('id');
                dbExecute(
                    "UPDATE custom_fields SET field_name=?, field_label=?, field_type=?, field_options=?, is_required=?, sort_order=?
                     WHERE id=?",
                    [$name, $label, $type, $optsJson, $required, $order, $fid]
                );
                redirectWithMessage(APP_URL . '/admin/custom_fields.php', 'success', 'تم تحديث الحقل.');
            }
        }
    } elseif ($action === 'delete') {
        $fid = (int)post('id');
        dbExecute("DELETE FROM custom_fields WHERE id=?", [$fid]);
        redirectWithMessage(APP_URL . '/admin/custom_fields.php', 'success', 'تم حذف الحقل.');
    } elseif ($action === 'toggle') {
        $fid    = (int)post('id');
        $active = (int)post('is_active');
        dbExecute("UPDATE custom_fields SET is_active=? WHERE id=?", [$active ? 0 : 1, $fid]);
        redirect(APP_URL . '/admin/custom_fields.php');
    }
}

/**
 * دالة بسيطة لتحويل الأحرف العربية إلى لاتينية
 */
function transliterate(string $text): string {
    $map = [
        'ا'=>'a','ب'=>'b','ت'=>'t','ث'=>'th','ج'=>'j','ح'=>'h','خ'=>'kh',
        'د'=>'d','ذ'=>'dh','ر'=>'r','ز'=>'z','س'=>'s','ش'=>'sh','ص'=>'ss',
        'ض'=>'dd','ط'=>'tt','ظ'=>'zz','ع'=>'aa','غ'=>'gh','ف'=>'f','ق'=>'q',
        'ك'=>'k','ل'=>'l','م'=>'m','ن'=>'n','ه'=>'h','و'=>'w','ي'=>'y',
        ' '=>'_',
    ];
    return strtr($text, $map);
}

$allFields   = getCustomFields(false);
$fieldTypes  = ['text','number','date','select','textarea','checkbox','email','phone'];
?>

<div class="page-header">
    <h1 class="page-title">🔧 الحقول المخصصة الديناميكية</h1>
    <p class="page-subtitle">أضف حقولاً مخصصة تظهر في نموذج إدخال بيانات الأشخاص</p>
</div>

<?= getFlashMessage() ?>

<?php if ($errors): ?>
<div class="alert alert-danger"><?php foreach ($errors as $e) echo clean($e) . '<br>'; ?></div>
<?php endif; ?>

<div class="two-col-layout">
    <!-- نموذج الإضافة/التعديل -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= $editField ? '✏ تعديل الحقل' : '➕ إضافة حقل جديد' ?></h3>
        </div>
        <div class="card-body">
            <form method="POST" id="fieldForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="<?= $editField ? 'edit' : 'add' ?>">
                <?php if ($editField): ?>
                <input type="hidden" name="id" value="<?= $editField['id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label required">تسمية الحقل (للعرض)</label>
                    <input type="text" name="field_label" class="form-control" required
                           value="<?= clean($editField['field_label'] ?? '') ?>"
                           placeholder="مثال: رقم العقد، تاريخ الانتهاء">
                </div>
                <div class="form-group">
                    <label class="form-label">المعرّف البرمجي</label>
                    <input type="text" name="field_name" class="form-control"
                           value="<?= clean($editField['field_name'] ?? '') ?>"
                           placeholder="يتم توليده تلقائياً إن تُرك فارغاً"
                           pattern="[a-z0-9_]+">
                    <small class="form-hint">أحرف إنجليزية صغيرة وأرقام وشرطة سفلية فقط</small>
                </div>
                <div class="form-group">
                    <label class="form-label required">نوع الحقل</label>
                    <select name="field_type" class="form-control" id="fieldTypeSelect">
                        <?php foreach ($fieldTypes as $ft): ?>
                        <option value="<?= $ft ?>" <?= (($editField['field_type'] ?? 'text')===$ft) ? 'selected' : '' ?>>
                            <?= getFieldTypeLabel($ft) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- خيارات القائمة (تظهر فقط عند اختيار select) -->
                <div class="form-group" id="optionsGroup" style="<?= (($editField['field_type'] ?? '') !== 'select') ? 'display:none' : '' ?>">
                    <label class="form-label">خيارات القائمة</label>
                    <?php
                    $existingOpts = '';
                    if ($editField && $editField['field_options']) {
                        $optsArr = json_decode($editField['field_options'], true) ?? [];
                        $existingOpts = implode("\n", $optsArr);
                    }
                    ?>
                    <textarea name="field_options" class="form-control" rows="5"
                              placeholder="أدخل كل خيار في سطر منفصل&#10;مثال:&#10;خيار 1&#10;خيار 2&#10;خيار 3"><?= clean($existingOpts) ?></textarea>
                    <small class="form-hint">كل سطر = خيار واحد في القائمة</small>
                </div>
                
                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label class="form-label">ترتيب العرض</label>
                        <input type="number" name="sort_order" class="form-control" min="0"
                               value="<?= (int)($editField['sort_order'] ?? 0) ?>">
                    </div>
                    <div class="form-group" style="padding-top:28px">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_required" value="1"
                                   <?= ($editField['is_required'] ?? 0) ? 'checked' : '' ?>>
                            <span>حقل إلزامي</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $editField ? '💾 حفظ التعديلات' : '➕ إضافة الحقل' ?>
                    </button>
                    <?php if ($editField): ?>
                    <a href="custom_fields.php" class="btn btn-secondary">إلغاء</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <!-- قائمة الحقول -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">الحقول المخصصة (<?= count($allFields) ?>)</h3>
        </div>
        <div class="card-body p-0">
            <?php if (empty($allFields)): ?>
            <div class="empty-state p-30">
                <div class="empty-icon">🔧</div>
                <p>لا توجد حقول مخصصة بعد. أضف حقلاً جديداً.</p>
            </div>
            <?php else: ?>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>التسمية</th>
                        <th>النوع</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($allFields as $field): ?>
                <tr class="<?= !$field['is_active'] ? 'row-inactive' : '' ?>">
                    <td>
                        <?= clean($field['field_label']) ?>
                        <br><small class="text-muted"><code><?= clean($field['field_name']) ?></code></small>
                    </td>
                    <td><?= clean(getFieldTypeLabel($field['field_type'])) ?></td>
                    <td>
                        <form method="POST" style="display:inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $field['id'] ?>">
                            <input type="hidden" name="is_active" value="<?= $field['is_active'] ?>">
                            <button type="submit" class="badge <?= $field['is_active'] ? 'badge-success' : 'badge-secondary' ?>" 
                                    style="border:none;cursor:pointer;">
                                <?= $field['is_active'] ? 'فعّال' : 'معطّل' ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <a href="custom_fields.php?action=edit&id=<?= $field['id'] ?>" class="btn btn-sm btn-warning">✏</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('حذف هذا الحقل وجميع قيمه؟')">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $field['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">🗑</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// إظهار/إخفاء خيارات القائمة
document.getElementById('fieldTypeSelect')?.addEventListener('change', function(){
    document.getElementById('optionsGroup').style.display = this.value === 'select' ? '' : 'none';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
