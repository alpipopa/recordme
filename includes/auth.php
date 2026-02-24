<?php
/**
 * نظام المصادقة وإدارة الجلسات
 * Authentication System
 */

require_once __DIR__ . '/../config/db.php';

// ==========================================
// دوال المصادقة
// ==========================================

/**
 * التحقق من تسجيل الدخول
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * الحصول على المستخدم الحالي
 */
function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = getDB()->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

/**
 * التحقق من الصلاحية
 */
function hasRole(string ...$roles): bool {
    $user = currentUser();
    if (!$user) return false;
    return in_array($user['role'], $roles);
}

/**
 * التحقق من كون المستخدم مديراً
 */
function isAdmin(): bool {
    return hasRole('admin');
}

/**
 * إجبار تسجيل الدخول - يُعيد توجيه إلى صفحة الدخول إن لم يكن مسجلاً
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        $redirect   = urlencode($currentUrl);
        header('Location: ' . APP_URL . '/login.php?redirect=' . $redirect);
        exit;
    }
    
    // التحقق من أن المستخدم لا يزال نشطاً
    $user = currentUser();
    if (!$user || !$user['is_active']) {
        session_destroy();
        header('Location: ' . APP_URL . '/login.php?msg=inactive');
        exit;
    }
}

/**
 * إجبار صلاحية معينة
 */
function requireRole(string ...$roles): void {
    requireLogin();
    if (!hasRole(...$roles)) {
        http_response_code(403);
        die('<div style="direction:rtl;font-family:Arial;padding:40px;text-align:center;">
            <h2>403 - غير مصرح</h2>
            <p>ليس لديك صلاحية لعرض هذه الصفحة.</p>
            <a href="' . APP_URL . '/admin/dashboard.php">العودة للوحة التحكم</a>
        </div>');
    }
}

/**
 * محاولة تسجيل الدخول
 */
function attemptLogin(string $username, string $password): bool {
    try {
        $stmt = getDB()->prepare(
            "SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1"
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // تجديد معرّف الجلسة لمنع تثبيت الجلسة (Session Fixation)
            session_regenerate_id(true);
            
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['login_at']  = time();
            
            // تحديث وقت آخر تسجيل دخول
            getDB()->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
                   ->execute([$user['id']]);
            
            return true;
        }
    } catch (Exception $e) {
        // تسجيل الخطأ في السجلات
        error_log('Login error: ' . $e->getMessage());
    }
    
    return false;
}

/**
 * تسجيل الخروج
 */
function logout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $p["path"], $p["domain"], $p["secure"], $p["httponly"]
        );
    }
    session_destroy();
}
