<?php
/**
 * صفحة تسجيل الدخول
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// إذا كان مسجلاً دخوله، اذهب للداشبورد
if (isLoggedIn()) {
    redirect(APP_URL . '/admin/dashboard.php');
}

$error = '';
$loginAttempts = $_SESSION['login_attempts'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'طلب غير صحيح. أعد المحاولة.';
    } elseif ($loginAttempts >= 5) {
        $error = 'تم تجاوز عدد المحاولات المسموح بها. انتظر 10 دقائق.';
    } else {
        $username = post('username');
        $password = post('password');
        
        if (empty($username) || empty($password)) {
            $error = 'يرجى إدخال اسم المستخدم وكلمة المرور.';
        } elseif (attemptLogin($username, $password)) {
            $_SESSION['login_attempts'] = 0;
            logAction('login', 'users', (int)$_SESSION['user_id']);
            $redirect = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : APP_URL . '/admin/dashboard.php';
            redirect($redirect);
        } else {
            $_SESSION['login_attempts'] = $loginAttempts + 1;
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
            sleep(1); // تأخير بسيط لمنع القوة الغاشمة
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | <?= clean(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="login-body">
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">📋</div>
            <h1 class="login-title"><?= clean(APP_NAME) ?></h1>
            <p class="login-subtitle">لوحة التحكم الإدارية</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <span class="alert-icon">✗</span> <?= clean($error) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'inactive'): ?>
        <div class="alert alert-warning">
            <span class="alert-icon">⚠</span> تم تعطيل حسابك. تواصل مع المدير.
        </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php" class="login-form" autocomplete="off" novalidate>
            <?= csrfField() ?>
            
            <div class="form-group">
                <label for="username" class="form-label">اسم المستخدم</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input type="text" id="username" name="username" class="form-control"
                           placeholder="أدخل اسم المستخدم"
                           value="<?= clean(post('username')) ?>"
                           required autocomplete="username" maxlength="100">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">كلمة المرور</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="أدخل كلمة المرور"
                           required autocomplete="current-password" maxlength="255">
                    <button type="button" class="input-icon-btn" id="togglePassword" title="إظهار/إخفاء">👁</button>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                🔐 تسجيل الدخول
            </button>
        </form>
        
        <div class="login-footer">
            <p class="text-muted">حساب المدير الافتراضي: <code>admin</code> / <code>password</code></p>
        </div>
    </div>
</div>

<script>
// إظهار/إخفاء كلمة المرور
document.getElementById('togglePassword')?.addEventListener('click', function() {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
    this.textContent = input.type === 'password' ? '👁' : '🙈';
});
</script>
</body>
</html>
