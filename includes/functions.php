<?php
/**
 * وظائف مساعدة عامة للنظام
 * General Helper Functions
 */

require_once __DIR__ . '/../config/db.php';

// ==========================================
// دوال الأمان والفلترة
// ==========================================

/**
 * تنظيف المدخلات من XSS
 */
function clean(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * التحقق من وجود القيمة وإعادتها منظفة
 */
function post(string $key, string $default = ''): string {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function get(string $key, string $default = ''): string {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

/**
 * إعادة التوجيه
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * توجيه برسالة flash
 */
function redirectWithMessage(string $url, string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    redirect($url);
}

/**
 * عرض رسالة Flash وحذفها
 */
function getFlashMessage(): string {
    if (!isset($_SESSION['flash'])) return '';
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $icons = ['success' => '✓', 'danger' => '✗', 'warning' => '⚠', 'info' => 'ℹ'];
    $icon  = $icons[$flash['type']] ?? '';
    return sprintf(
        '<div class="alert alert-%s alert-dismissible" role="alert">
            <span class="alert-icon">%s</span> %s
            <button type="button" class="btn-close" onclick="this.parentElement.remove()">×</button>
        </div>',
        clean($flash['type']),
        $icon,
        clean($flash['message'])
    );
}

// ==========================================
// دوال قاعدة البيانات
// ==========================================

/**
 * استعلام بسيط مع fetchAll
 */
function dbQuery(string $sql, array $params = []): array {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * استعلام يُعيد صف واحد
 */
function dbQueryOne(string $sql, array $params = []): ?array {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * تنفيذ استعلام (INSERT/UPDATE/DELETE)
 */
function dbExecute(string $sql, array $params = []): int {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * آخر ID مُدرج
 */
function dbLastId(): string {
    return getDB()->lastInsertId();
}

// ==========================================
// دوال الأشخاص
// ==========================================

/**
 * الحصول على جميع الأشخاص مع فلاتر البحث
 */
function getPersons(array $filters = [], int $page = 1, int $perPage = 25): array {
    $where  = ['1=1'];
    $params = [];
    
    if (!empty($filters['name'])) {
        $where[]  = 'p.full_name LIKE ?';
        $params[] = '%' . $filters['name'] . '%';
    }
    if (!empty($filters['category_id'])) {
        $where[]  = 'p.category_id = ?';
        $params[] = (int)$filters['category_id'];
    }
    if (!empty($filters['id_number'])) {
        $where[]  = 'p.id_number LIKE ?';
        $params[] = '%' . $filters['id_number'] . '%';
    }
    if (!empty($filters['phone'])) {
        $where[]  = '(p.phone LIKE ? OR p.phone2 LIKE ?)';
        $params[] = '%' . $filters['phone'] . '%';
        $params[] = '%' . $filters['phone'] . '%';
    }
    if (!empty($filters['governorate'])) {
        $where[]  = 'p.governorate LIKE ?';
        $params[] = '%' . $filters['governorate'] . '%';
    }
    if (!empty($filters['marital_status'])) {
        $where[]  = 'p.marital_status = ?';
        $params[] = $filters['marital_status'];
    }
    
    $whereStr = implode(' AND ', $where);
    
    // إجمالي عدد السجلات
    $countSql  = "SELECT COUNT(*) as total FROM persons p WHERE $whereStr";
    $totalRow  = dbQueryOne($countSql, $params);
    $total     = (int)($totalRow['total'] ?? 0);
    
    // حساب الصفحات
    $offset = ($page - 1) * $perPage;
    
    $sql = "SELECT p.*, c.name AS category_name, c.color AS category_color
            FROM persons p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE $whereStr
            ORDER BY p.created_at DESC
            LIMIT $perPage OFFSET $offset";
    
    $rows = dbQuery($sql, $params);
    
    return [
        'data'       => $rows,
        'total'      => $total,
        'page'       => $page,
        'per_page'   => $perPage,
        'last_page'  => (int)ceil($total / $perPage),
    ];
}

/**
 * الحصول على شخص واحد مع حقوله المخصصة
 */
function getPersonById(int $id): ?array {
    $person = dbQueryOne(
        "SELECT p.*, c.name AS category_name
         FROM persons p
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.id = ?",
        [$id]
    );
    
    if (!$person) return null;
    
    // جلب قيم الحقول المخصصة
    $customValues = dbQuery(
        "SELECT cf.id, cf.field_label, cf.field_type, cf.field_name, cfv.value
         FROM custom_fields cf
         LEFT JOIN custom_field_values cfv ON cfv.field_id = cf.id AND cfv.person_id = ?
         WHERE cf.is_active = 1
         ORDER BY cf.sort_order",
        [$id]
    );
    $person['custom_fields'] = $customValues;
    
    return $person;
}

// ==========================================
// دوال التصنيفات
// ==========================================

function getCategories(): array {
    return dbQuery("SELECT * FROM categories ORDER BY sort_order, name");
}

function getCategoryById(int $id): ?array {
    return dbQueryOne("SELECT * FROM categories WHERE id = ?", [$id]);
}

// ==========================================
// دوال الحقول الديناميكية
// ==========================================

function getCustomFields(bool $activeOnly = true): array {
    $sql = "SELECT * FROM custom_fields";
    if ($activeOnly) $sql .= " WHERE is_active = 1";
    $sql .= " ORDER BY sort_order, id";
    return dbQuery($sql);
}

// ==========================================
// دوال رفع الصور
// ==========================================

/**
 * رفع صورة هوية الشخص
 */
function uploadIdImage(array $file): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_FILE_SIZE) return false;
    
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) return false;
    
    $ext      = match($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        default      => 'jpg'
    };
    
    $filename = 'id_' . uniqid('', true) . '.' . $ext;
    $destPath = UPLOAD_PATH . '/' . $filename;
    
    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }
    
    if (!move_uploaded_file($file['tmp_name'], $destPath)) return false;
    
    return $filename;
}

/**
 * رفع مستندات إضافية لشخص
 */
function uploadPersonDocument(int $personId, array $file): bool {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    
    // أنواع الملفات المسموحة (صور، PDF، مستندات Word)
    $allowedMime = [
        'image/jpeg', 'image/png', 'image/webp', 'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedMime)) return false;

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'doc_' . $personId . '_' . uniqid() . '.' . $ext;
    
    $docDir   = UPLOAD_PATH . '/documents';
    if (!is_dir($docDir)) mkdir($docDir, 0755, true);
    
    $destPath = $docDir . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        return (bool)dbExecute(
            "INSERT INTO person_documents (person_id, file_name, file_path, file_type, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)",
            [$personId, $file['name'], $filename, $mimeType, $file['size'], $_SESSION['user_id'] ?? null]
        );
    }
    
    return false;
}

/**
 * جلب مستندات شخص
 */
function getPersonDocuments(int $personId): array {
    return dbQuery("SELECT * FROM person_documents WHERE person_id = ? ORDER BY created_at DESC", [$personId]);
}

/**
 * حذف مستند
 */
function deleteDocument(int $docId): bool {
    $doc = dbQueryOne("SELECT * FROM person_documents WHERE id = ?", [$docId]);
    if ($doc) {
        $path = UPLOAD_PATH . '/documents/' . $doc['file_path'];
        if (file_exists($path)) @unlink($path);
        return (bool)dbExecute("DELETE FROM person_documents WHERE id = ?", [$docId]);
    }
    return false;
}

// ==========================================
// دوال إحصائيات الداشبورد
// ==========================================

function getDashboardStats(): array {
    $stats = [];
    
    // إجمالي الأشخاص
    $stats['total_persons'] = (int)(dbQueryOne("SELECT COUNT(*) as c FROM persons")['c'] ?? 0);
    
    // تصنيف مفصل
    $rows = dbQuery(
        "SELECT c.name, COUNT(p.id) as cnt, c.color
         FROM categories c
         LEFT JOIN persons p ON p.category_id = c.id
         GROUP BY c.id, c.name, c.color
         ORDER BY c.sort_order"
    );
    $stats['by_category'] = $rows;
    
    // إحصاء الأشخاص المسجلين اليوم
    $stats['today_persons'] = (int)(dbQueryOne(
        "SELECT COUNT(*) as c FROM persons WHERE DATE(created_at) = CURDATE()"
    )['c'] ?? 0);
    
    // إحصاء هذا الشهر
    $stats['month_persons'] = (int)(dbQueryOne(
        "SELECT COUNT(*) as c FROM persons WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())"
    )['c'] ?? 0);
    
    // توزيع الحالة الاجتماعية
    $stats['marital'] = dbQuery(
        "SELECT marital_status, COUNT(*) as cnt FROM persons GROUP BY marital_status"
    );
    
    // إجمالي المحافظات
    $stats['by_governorate'] = dbQuery(
        "SELECT governorate, COUNT(*) as cnt FROM persons WHERE governorate != '' GROUP BY governorate ORDER BY cnt DESC LIMIT 10"
    );
    
    // آخر 5 مسجلين
    $stats['latest'] = dbQuery(
        "SELECT p.full_name, p.created_at, c.name as category_name
         FROM persons p LEFT JOIN categories c ON c.id = p.category_id
         ORDER BY p.created_at DESC LIMIT 5"
    );

    // إحصائيات التسجيل الشهري لآخر 12 شهر
    $stats['monthly_growth'] = dbQuery(
        "SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as cnt
         FROM persons 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
         GROUP BY month
         ORDER BY month ASC"
    );
    
    return $stats;
}

// ==========================================
// دوال التنبيهات
// ==========================================

/**
 * إرسال تنبيه لمستخدم معين أو لجميع المسؤولين
 */
function sendNotification(string $title, string $message, string $type = 'info', ?string $link = null, ?int $userId = null): bool {
    // إذا لم يحدد مستخدم، نرسل لكل الأدمينات والمديرين
    if ($userId === null) {
        $admins = dbQuery("SELECT id FROM users WHERE role IN ('admin', 'manager') AND is_active = 1");
        foreach ($admins as $admin) {
            dbExecute(
                "INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)",
                [$admin['id'], $title, $message, $type, $link]
            );
        }
        return true;
    }
    
    return (bool)dbExecute(
        "INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)",
        [$userId, $title, $message, $type, $link]
    );
}

/**
 * جلب تنبيهات المستخدم الحالي
 */
function getMyNotifications(int $limit = 5, bool $unreadOnly = false): array {
    $userId = $_SESSION['user_id'] ?? 0;
    $where = "user_id = ?";
    if ($unreadOnly) $where .= " AND is_read = 0";
    
    return dbQuery(
        "SELECT * FROM notifications WHERE $where ORDER BY created_at DESC LIMIT $limit",
        [$userId]
    );
}

/**
 * الحصول على عدد التنبيهات غير المقروءة
 */
function getUnreadCount(): int {
    $userId = $_SESSION['user_id'] ?? 0;
    $row = dbQueryOne("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0", [$userId]);
    return (int)($row['cnt'] ?? 0);
}

/**
 * تحديد التنبيه كمقروء
 */
function markAsRead(int $notificationId): bool {
    $userId = $_SESSION['user_id'] ?? 0;
    return (bool)dbExecute(
        "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
        [$notificationId, $userId]
    );
}

// ==========================================
// دوال سجل التعديلات
// ==========================================

function logAction(string $action, string $table, ?int $recordId = null, array $oldVal = [], array $newVal = []): void {
    try {
        $userId = $_SESSION['user_id'] ?? null;
        dbExecute(
            "INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $action,
                $table,
                $recordId,
                $oldVal ? json_encode($oldVal, JSON_UNESCAPED_UNICODE) : null,
                $newVal ? json_encode($newVal, JSON_UNESCAPED_UNICODE) : null,
                $_SERVER['REMOTE_ADDR'] ?? '',
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]
        );
    } catch (Exception $e) {
        // لا نوقف التطبيق بسبب فشل تسجيل الحدث
    }
}

// ==========================================
// دوال عرض التسميات
// ==========================================

function getIdTypeLabel(string $type): string {
    return match($type) {
        'national_id'      => 'بطاقة وطنية',
        'passport'         => 'جواز سفر',
        'driving_license'  => 'رخصة قيادة',
        'other'            => 'أخرى',
        default            => $type,
    };
}

function getMaritalStatusLabel(string $status): string {
    return match($status) {
        'single'   => 'أعزب / عزباء',
        'married'  => 'متزوج / متزوجة',
        'divorced' => 'مطلق / مطلقة',
        'widowed'  => 'أرمل / أرملة',
        default    => $status,
    };
}

function getResidenceTypeLabel(?string $type): string {
    return match($type) {
        'owned'  => 'ملـك',
        'rented' => 'إيجار',
        default  => '—',
    };
}

function getFieldTypeLabel(string $type): string {
    return match($type) {
        'text'     => 'نص',
        'number'   => 'رقم',
        'date'     => 'تاريخ',
        'select'   => 'قائمة اختيار',
        'textarea' => 'مربع نص كبير',
        'checkbox' => 'خانة اختيار',
        'email'    => 'بريد إلكتروني',
        'phone'    => 'رقم هاتف',
        default    => $type,
    };
}

/**
 * عرض ترقيم الصفحات
 */
function renderPagination(int $currentPage, int $lastPage, string $baseUrl): string {
    if ($lastPage <= 1) return '';
    
    $html = '<nav class="pagination-nav" aria-label="التنقل بين الصفحات"><ul class="pagination">';
    
    // الرابط السابق
    if ($currentPage > 1) {
        $url  = $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'page=' . ($currentPage - 1);
        $html .= '<li><a href="' . $url . '" class="page-link">‹ السابق</a></li>';
    }
    
    // أرقام الصفحات
    $start = max(1, $currentPage - 2);
    $end   = min($lastPage, $currentPage + 2);
    
    if ($start > 1) {
        $html .= '<li><a href="' . $baseUrl . '?page=1" class="page-link">1</a></li>';
        if ($start > 2) $html .= '<li><span class="page-ellipsis">…</span></li>';
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $url     = $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'page=' . $i;
        $active  = ($i === $currentPage) ? ' active' : '';
        $html   .= '<li><a href="' . $url . '" class="page-link' . $active . '">' . $i . '</a></li>';
    }
    
    if ($end < $lastPage) {
        if ($end < $lastPage - 1) $html .= '<li><span class="page-ellipsis">…</span></li>';
        $url   = $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'page=' . $lastPage;
        $html .= '<li><a href="' . $url . '" class="page-link">' . $lastPage . '</a></li>';
    }
    
    // الرابط التالي
    if ($currentPage < $lastPage) {
        $url  = $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'page=' . ($currentPage + 1);
        $html .= '<li><a href="' . $url . '" class="page-link">التالي ›</a></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * الحصول على إعداد من قاعدة البيانات
 */
function getSetting(string $key, string $default = ''): string {
    $row = dbQueryOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    return $row ? ($row['setting_value'] ?? $default) : $default;
}

/**
 * تحديث إعداد في قاعدة البيانات
 */
function updateSetting(string $key, string $value): bool {
    $exists = dbQueryOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
    if ($exists) {
        return (bool)dbExecute("UPDATE settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
    } else {
        return (bool)dbExecute("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)", [$key, $value]);
    }
}

/**
 * تنسيق التاريخ بالعربي
 */
function formatDate(string $date, bool $withTime = false): string {
    if (empty($date) || $date === '0000-00-00') return '—';
    $ts  = strtotime($date);
    $fmt = $withTime ? 'd/m/Y H:i' : 'd/m/Y';
    return date($fmt, $ts);
}
