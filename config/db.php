<?php
/**
 * إعدادات قاعدة البيانات
 * Database Configuration File
 */

// إعدادات قاعدة البيانات - عدّل هذه القيم حسب بيئتك
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'contact');
define('DB_USER',    'root');
define('DB_PASS',    '1234');
define('DB_CHARSET', 'utf8mb4');

// إعدادات عامة للتطبيق
define('APP_NAME',    'نظام تسجيل بيانات الأشخاص');
define('APP_VERSION', '1.0.0');
define('APP_URL',     'http://localhost/contact');
define('BASE_PATH',   dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('UPLOAD_URL',  APP_URL . '/uploads');
define('SESSION_NAME','registry_sess');

// إعدادات الأمان
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_FILE_SIZE',   5 * 1024 * 1024); // 5 ميجابايت
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// وضع التطوير (اجعلها false في الإنتاج)
define('DEV_MODE', true);

// ==========================================
// اتصال PDO بقاعدة البيانات
// ==========================================
function getDB(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (DEV_MODE) {
                die('<div style="direction:rtl;font-family:Arial;padding:20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:4px;">
                    <strong>خطأ في الاتصال بقاعدة البيانات:</strong><br>' . 
                    htmlspecialchars($e->getMessage()) . '</div>');
            } else {
                die('<div style="direction:rtl;font-family:Arial;padding:20px;text-align:center;">
                    عذراً، حدث خطأ في النظام. يرجى المحاولة لاحقاً.</div>');
            }
        }
    }
    
    return $pdo;
}

// بدء الجلسة بإعدادات آمنة
function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $sessionConfig = [
            'cookie_httponly' => true,
            'cookie_secure'   => isset($_SERVER['HTTPS']),
            'cookie_samesite' => 'Strict',
            'use_strict_mode' => true,
            'gc_maxlifetime'  => 3600, // ساعة واحدة
        ];
        session_name(SESSION_NAME);
        session_start($sessionConfig);
    }
}

// بدء الجلسة تلقائياً
startSecureSession();
