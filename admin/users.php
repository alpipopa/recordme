<?php
/**
 * إدارة المستخدمين
 */
$pageTitle = 'إدارة المستخدمين';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin'); // للمدير فقط

$errors  = [];
$editUser = null;

if (get('action') === 'edit' && get('id')) {
    $editUser = dbQueryOne("SELECT * FROM users WHERE id=?", [(int)get('id')]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = post('action');
    
    if ($action === 'add') {
        $username  = post('username');
        $password  = post('password');
        $fullName  = post('full_name');
        $email     = post('email');
        $role      = post('role', 'viewer');
        
        if (empty($username))  $errors[] = 'اسم المستخدم مطلوب.';
        if (empty($password))  $errors[] = 'كلمة المرور مطلوبة.';
        if (strlen($password) < 8) $errors[] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.';
        
        // التحقق من عدم تكرار اسم المستخدم
        if (empty($errors)) {
            $exists = dbQueryOne("SELECT id FROM users WHERE username=?", [$username]);
            if ($exists) $errors[] = 'اسم المستخدم موجود مسبقاً.';
        }
        
        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            dbExecute(
                "INSERT INTO users (username, password, full_name, email, role) VALUES (?,?,?,?,?)",
                [$username, $hash, $fullName, $email, $role]
            );
            redirectWithMessage(APP_URL . '/admin/users.php', 'success', 'تم إنشاء المستخدم بنجاح.');
        }
    } elseif ($action === 'edit') {
        $uid      = (int)post('id');
        $fullName = post('full_name');
        $email    = post('email');
        $role     = post('role', 'viewer');
        $password = post('password');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // منع تعطيل المدير الوحيد
        if ($uid === (int)$_SESSION['user_id'] && $isActive === 0) {
            $errors[] = 'لا يمكنك تعطيل حسابك الخاص.';
        }
        
        if (empty($errors)) {
            if (!empty($password)) {
                if (strlen($password) < 8) {
                    $errors[] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    dbExecute("UPDATE users SET full_name=?, email=?, role=?, is_active=?, password=? WHERE id=?",
                        [$fullName, $email, $role, $isActive, $hash, $uid]);
                }
            } else {
                dbExecute("UPDATE users SET full_name=?, email=?, role=?, is_active=? WHERE id=?",
                    [$fullName, $email, $role, $isActive, $uid]);
            }
            if (empty($errors)) {
                redirectWithMessage(APP_URL . '/admin/users.php', 'success', 'تم تحديث بيانات المستخدم.');
            }
        }
    } elseif ($action === 'delete') {
        $uid = (int)post('id');
        if ($uid === (int)$_SESSION['user_id']) {
            $_SESSION['flash'] = ['type'=>'danger','message'=>'لا يمكنك حذف حسابك الخاص.'];
        } else {
            dbExecute("DELETE FROM users WHERE id=?", [$uid]);
            $_SESSION['flash'] = ['type'=>'success','message'=>'تم حذف المستخدم.'];
        }
        redirect(APP_URL . '/admin/users.php');
    }
}

$users = dbQuery("SELECT * FROM users ORDER BY role, username");
?>

<div class="page-header">
    <h1 class="page-title">👤 إدارة المستخدمين</h1>
</div>

<?= getFlashMessage() ?>

<div class="two-col-layout">
    <!-- نموذج الإضافة/التعديل -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= $editUser ? '✏ تعديل المستخدم' : '➕ إضافة مستخدم جديد' ?></h3>
        </div>
        <div class="card-body">
            <?php if ($errors): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e) echo clean($e) . '<br>'; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="<?= $editUser ? 'edit' : 'add' ?>">
                <?php if ($editUser): ?>
                <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
                <?php endif; ?>
                
                <?php if (!$editUser): ?>
                <div class="form-group">
                    <label class="form-label required">اسم المستخدم</label>
                    <input type="text" name="username" class="form-control" required maxlength="100"
                           placeholder="بالإنجليزية، بدون مسافات">
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label required">الاسم الكامل</label>
                    <input type="text" name="full_name" class="form-control" required maxlength="200"
                           value="<?= clean($editUser['full_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control" maxlength="200"
                           value="<?= clean($editUser['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">الصلاحية</label>
                    <select name="role" class="form-control">
                        <option value="admin"   <?= (($editUser['role'] ?? '') === 'admin')   ? 'selected' : '' ?>>مدير النظام</option>
                        <option value="manager" <?= (($editUser['role'] ?? '') === 'manager') ? 'selected' : '' ?>>مشرف</option>
                        <option value="viewer"  <?= (($editUser['role'] ?? 'viewer') === 'viewer')  ? 'selected' : '' ?>>مشاهد فقط</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= $editUser ? 'كلمة المرور الجديدة (اتركها فارغة إن لم ترد تغييرها)' : 'كلمة المرور *' ?></label>
                    <input type="password" name="password" class="form-control"
                           <?= !$editUser ? 'required' : '' ?> minlength="8"
                           placeholder="8 أحرف على الأقل">
                </div>
                <?php if ($editUser): ?>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" <?= $editUser['is_active'] ? 'checked' : '' ?>>
                        <span>الحساب مفعّل</span>
                    </label>
                </div>
                <?php endif; ?>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $editUser ? '💾 حفظ' : '➕ إضافة' ?>
                    </button>
                    <?php if ($editUser): ?>
                    <a href="users.php" class="btn btn-secondary">إلغاء</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <!-- قائمة المستخدمين -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">المستخدمون (<?= count($users) ?>)</h3>
        </div>
        <div class="card-body p-0">
            <table class="table">
                <thead>
                    <tr>
                        <th>المستخدم</th>
                        <th>الصلاحية</th>
                        <th>الحالة</th>
                        <th>آخر دخول</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <strong><?= clean($u['full_name']) ?></strong><br>
                        <small class="text-muted">@<?= clean($u['username']) ?></small>
                    </td>
                    <td>
                        <span class="badge <?= match($u['role']) { 'admin'=>'badge-danger', 'manager'=>'badge-warning', default=>'badge-info' } ?>">
                            <?= match($u['role']) { 'admin'=>'مدير', 'manager'=>'مشرف', default=>'مشاهد' } ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $u['is_active'] ? 'badge-success' : 'badge-secondary' ?>">
                            <?= $u['is_active'] ? 'فعّال' : 'معطّل' ?>
                        </span>
                    </td>
                    <td><?= $u['last_login'] ? formatDate($u['last_login'], true) : '—' ?></td>
                    <td>
                        <a href="users.php?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-warning">✏</a>
                        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                        <form method="POST" style="display:inline" onsubmit="return confirm('حذف هذا المستخدم؟')">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">🗑</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
