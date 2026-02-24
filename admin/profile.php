<?php
/**
 * صفحة الملف الشخصي - Profile
 * تسمح للمستخدم بتغيير بياناته الخاصة وكلمة المرور
 */
$pageTitle = 'الملف الشخصي';
require_once __DIR__ . '/../includes/header.php';

$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    requireCsrf();
    
    $fullName = post('full_name');
    $email    = post('email');
    $password = post('password');
    $confirm  = post('confirm_password');
    
    $errors = [];
    
    if (empty($fullName)) $errors[] = 'الاسم الكامل مطلوب.';
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صالح.';
    
    // تحديث كلمة المرور إذا مدخلة
    $passwordSql = "";
    $params = [$fullName, $email];
    
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.';
        } elseif ($password !== $confirm) {
            $errors[] = 'كلمتا المرور غير متطابقتين.';
        } else {
            $passwordSql = ", password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
    }

    // معالجة رفع الصورة الشخصية
    $avatarSql = "";
    if (!empty($_FILES['avatar']['name'])) {
        $file = $_FILES['avatar'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime, $allowed)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $user['id'] . '_' . time() . '.' . $ext;
            $dest = UPLOAD_PATH . '/avatars/' . $filename;

            if (!is_dir(UPLOAD_PATH . '/avatars')) {
                mkdir(UPLOAD_PATH . '/avatars', 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // حذف الصورة القديمة
                if (!empty($user['avatar']) && file_exists(UPLOAD_PATH . '/avatars/' . $user['avatar'])) {
                    @unlink(UPLOAD_PATH . '/avatars/' . $user['avatar']);
                }
                $avatarSql = ", avatar = ?";
                $params[] = $filename;
            } else {
                $errors[] = 'فشل في رفع الصورة الشخصية.';
            }
        } else {
            $errors[] = 'نوع الصورة غير مدعوم (JPG, PNG, WEBP فقط).';
        }
    }
    
    if (empty($errors)) {
        $params[] = $user['id'];
        $sql = "UPDATE users SET full_name = ?, email = ? $passwordSql $avatarSql WHERE id = ?";
        
        try {
            if (dbExecute($sql, $params)) {
                logAction('update', 'users', $user['id'], $user, dbQueryOne("SELECT * FROM users WHERE id = ?", [$user['id']]));
                redirectWithMessage(APP_URL . '/admin/profile.php', 'success', 'تم تحديث بيانات الملف الشخصي بنجاح.');
            } else {
                redirectWithMessage(APP_URL . '/admin/profile.php', 'info', 'لم يتم تغيير أي بيانات.');
            }
        } catch (Exception $e) {
            // ربما لم يقم المستخدم بتشغيل الهجرة بعد، فلنحاول التحديث بدون الصورة إذا فشل
            if (str_contains($e->getMessage(), 'Unknown column \'avatar\'')) {
                // محاولة تشغيل الهجرة برمجياً إذا فشلت الأوامر السابقة
                @dbExecute("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL DEFAULT NULL AFTER email");
                // إعادة المحاولة مرة واحدة فقط
                if (dbExecute($sql, $params)) {
                    redirectWithMessage(APP_URL . '/admin/profile.php', 'success', 'تم تحديث البيانات (وإصلاح هيكل القاعدة تلقائياً).');
                }
            } else {
                redirectWithMessage(APP_URL . '/admin/profile.php', 'danger', 'حدث خطأ: ' . $e->getMessage());
            }
        }
    } else {
        $errorMsg = implode('<br>', $errors);
        redirectWithMessage(APP_URL . '/admin/profile.php', 'danger', $errorMsg);
    }
}
?>

<div class="page-header">
    <div class="page-title-wrap">
        <h1 class="page-title">👤 الملف الشخصي</h1>
        <p class="page-subtitle">إدارة بياناتك الشخصية وتغيير كلمة المرور</p>
    </div>
</div>

<?= getFlashMessage() ?>

<div class="dashboard-grid" style="grid-template-columns: 1fr 400px;">
    
    <!-- بيانات الحساب -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📝 البيانات الأساسية</h3>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                
                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label class="form-label required">اسم المستخدم</label>
                        <input type="text" class="form-control" value="<?= clean($user['username']) ?>" disabled>
                        <small class="text-muted">اسم المستخدم لا يمكن تغييره.</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">الرتبة / الصلاحية</label>
                        <input type="text" class="form-control" value="<?= clean($user['role']) ?>" disabled>
                        <small class="text-muted">تواصل مع المدير لتغيير الصلاحيات.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">الاسم الكامل</label>
                        <input type="text" name="full_name" class="form-control" value="<?= clean($user['full_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" class="form-control" value="<?= clean($user['email']) ?>">
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">الصورة الشخصية (Avatar)</label>
                        <input type="file" name="avatar" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <small class="text-muted">يفضل صورة مربعة، الحد الأقصى 2MB.</small>
                    </div>
                </div>

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--border);">
                
                <h3 class="mb-20" style="font-size: 16px;">🔒 تغيير كلمة المرور <small style="font-weight: normal; color: var(--secondary);">(اتركها فارغة إذا لم ترد التغيير)</small></h3>
                
                <div class="form-grid form-grid--2">
                    <div class="form-group">
                        <label class="form-label">كلمة المرور الجديدة</label>
                        <input type="password" name="password" class="form-control" placeholder="6 أحرف على الأقل">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">تأكيد كلمة المرور</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="أعد إدخال كلمة المرور">
                    </div>
                </div>

                <div class="mt-20">
                    <button type="submit" name="update_profile" class="btn btn-primary" style="padding: 12px 30px;">
                        💾 حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- بطاقة المعلومات -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">ℹ️ معلومات الحساب</h3>
        </div>
        <div class="card-body">
            <div class="text-center mb-20">
                <div class="user-avatar-wrap mb-20">
                    <?php if (!empty($user['avatar']) && file_exists(UPLOAD_PATH . '/avatars/' . $user['avatar'])): ?>
                        <img src="<?= UPLOAD_URL ?>/avatars/<?= clean($user['avatar']) ?>" alt="Avatar" 
                             style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-lt); padding: 3px;">
                    <?php else: ?>
                        <div class="user-avatar" style="width: 100px; height: 100px; font-size: 40px; margin: 0 auto;">
                            <?= mb_substr($user['full_name'] ?? 'م', 0, 1) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <h4 style="margin: 0;"><?= clean($user['full_name']) ?></h4>
                <p class="text-muted" style="font-size: 13px;">@<?= clean($user['username']) ?></p>
                <span class="badge badge-info"><?= clean($user['role']) ?></span>
            </div>
            
            <div class="info-list" style="padding-right: 0; list-style: none;">
                <li style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border);">
                    <span class="text-muted">تاريخ الإنشاء:</span>
                    <span><?= formatDate($user['created_at']) ?></span>
                </li>
                <li style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border);">
                    <span class="text-muted">آخر تسجيل دخول:</span>
                    <span><?= $user['last_login'] ? formatDate($user['last_login'], true) : 'لأول مرة' ?></span>
                </li>
                <li style="display: flex; justify-content: space-between; padding: 10px 0;">
                    <span class="text-muted">الحالة:</span>
                    <span class="text-success">● نشط</span>
                </li>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
