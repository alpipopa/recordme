<?php
/**
 * حماية CSRF
 * Cross-Site Request Forgery Protection
 */

/**
 * توليد أو استرجاع CSRF token
 */
function getCsrfToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * التحقق من صحة CSRF token
 */
function verifyCsrfToken(): bool {
    $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token)) return false;
    $valid = hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
    // تجديد التوكن بعد التحقق لمزيد من الأمان
    if ($valid) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $valid;
}

/**
 * التحقق وإيقاف التنفيذ عند الفشل
 */
function requireCsrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyCsrfToken()) {
        http_response_code(403);
        die('<div style="direction:rtl;font-family:Arial;padding:40px;text-align:center;">
            <h2>خطأ أمني</h2>
            <p>طلب غير صحيح. يرجى المحاولة مرة أخرى.</p>
            <a href="javascript:history.back()">العودة</a>
        </div>');
    }
}

/**
 * حقل HTML مخفي للـ CSRF
 */
function csrfField(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . getCsrfToken() . '">';
}
