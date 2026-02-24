<?php
/**
 * سجل الأنشطة والعمليات (Audit Log)
 * يظهر للمديرين فقط
 */
$pageTitle = 'سجل الأنشطة';
require_once __DIR__ . '/../includes/header.php';
requireRole('admin'); // حماية الصفحة للمدير فقط

$page    = (int)get('page', '1');
$perPage = 50;
$offset  = ($page - 1) * $perPage;

// جلب السجلات مع بيانات المستخدم
$sql = "SELECT al.*, u.full_name as user_name, u.username 
        FROM audit_log al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT $perPage OFFSET $offset";

$logs = dbQuery($sql);

// إجمالي السجلات للترقيم
$totalLogs = (int)dbQueryOne("SELECT COUNT(*) as cnt FROM audit_log")['cnt'];
$totalPages = ceil($totalLogs / $perPage);

// ترجمة العمليات
function getActionLabel(string $action): string {
    return match($action) {
        'create' => '<span class="badge badge-success">➕ إضافة</span>',
        'update' => '<span class="badge badge-warning">✏ تعديل</span>',
        'delete' => '<span class="badge badge-danger">🗑 حذف</span>',
        'login'  => '<span class="badge badge-info">🔑 دخول</span>',
        'logout' => '<span class="badge badge-secondary">🚪 خروج</span>',
        'export' => '<span class="badge badge-primary">📊 تصدير</span>',
        default  => $action
    };
}

// ترجمة الجداول
function getTableLabel(string $table): string {
    return match($table) {
        'persons'        => 'الأشخاص',
        'users'          => 'المستخدمين',
        'categories'     => 'التصنيفات',
        'custom_fields'  => 'الحقول المخصصة',
        'sliders'        => 'السلايدر',
        default          => $table
    };
}
?>

<div class="page-header">
    <div class="page-title-wrap">
        <h1 class="page-title">🛡 سجل الأنشطة والرقابة</h1>
        <p class="page-subtitle">تتبع جميع العمليات التي تمت على النظام ومن قام بها</p>
    </div>
</div>

<?= getFlashMessage() ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">آخر العمليات (<?= number_format($totalLogs) ?>)</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th style="width:180px">التاريخ والوقت</th>
                    <th>المستخدم</th>
                    <th>العملية</th>
                    <th>الجدول</th>
                    <th>رقم السجل</th>
                    <th>التفاصيل</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr><td colspan="6" class="text-center p-20">لا توجد سجلات حتى الآن</td></tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="text-muted" style="font-size: 12px;">
                        <?= formatDate($log['created_at'], true) ?>
                    </td>
                    <td>
                        <strong><?= clean($log['user_name'] ?: 'نظام غير معروف') ?></strong><br>
                        <small class="text-muted">@<?= clean($log['username'] ?: 'Guest') ?></small>
                    </td>
                    <td><?= getActionLabel($log['action']) ?></td>
                    <td><?= getTableLabel($log['table_name']) ?></td>
                    <td class="text-center"><code>#<?= $log['record_id'] ?: '—' ?></code></td>
                    <td>
                        <?php if ($log['old_values'] || $log['new_values']): ?>
                            <button type="button" class="btn btn-sm btn-outline-info" 
                                    onclick='viewLogDetails(<?= json_encode($log['old_values']) ?>, <?= json_encode($log['new_values']) ?>)'>
                                👁 عرض التغييرات
                            </button>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="card-footer">
        <?= renderPagination($page, $totalPages, APP_URL . '/admin/audit_log.php') ?>
    </div>
</div>

<!-- مـودال عرض التفاصيل -->
<div id="logModal" class="modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; display:none; align-items:center; justify-content:center; padding:20px;">
    <div class="card" style="width:100%; max-width:800px; max-height:90vh; overflow-y:auto;">
        <div class="card-header">
            <h3 class="card-title">تفاصيل التعديلات</h3>
            <button type="button" onclick="closeLogModal()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>
        <div class="card-body">
            <div id="logCompare" class="log-compare-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <!-- المحتوى سيُدرج عبر JS -->
            </div>
        </div>
    </div>
</div>

<style>
.log-compare-grid h4 { margin-bottom: 10px; font-size: 14px; border-bottom: 2px solid var(--border); padding-bottom: 5px; }
.json-view { background: var(--light); padding: 10px; border-radius: 8px; font-family: monospace; font-size: 12px; white-space: pre-wrap; overflow-x: auto; }
.diff-highlight-old { background: #fee2e2; color: #991b1b; padding: 2px 4px; border-radius: 3px; }
.diff-highlight-new { background: #dcfce7; color: #166534; padding: 2px 4px; border-radius: 3px; }
</style>

<script>
function viewLogDetails(oldVal, newVal) {
    const modal = document.getElementById('logModal');
    const container = document.getElementById('logCompare');
    
    const oldObj = oldVal ? JSON.parse(oldVal) : null;
    const newObj = newVal ? JSON.parse(newVal) : null;
    
    let html = '';
    
    // القيم القديمة
    html += '<div><h4>⬅ القيم السابقة</h4><div class="json-view">';
    if (oldObj) {
        for (let key in oldObj) {
            html += `<div><strong>${key}:</strong> <span class="diff-highlight-old">${oldObj[key]}</span></div>`;
        }
    } else {
        html += '<em class="text-muted">لا توجد قيم سابقة (إضافة جديدة)</em>';
    }
    html += '</div></div>';
    
    // القيم الجديدة
    html += '<div><h4>➡ القيم الجديدة</h4><div class="json-view">';
    if (newObj) {
        for (let key in newObj) {
            html += `<div><strong>${key}:</strong> <span class="diff-highlight-new">${newObj[key]}</span></div>`;
        }
    } else {
        html += '<em class="text-muted">السجل تم حذفه</em>';
    }
    html += '</div></div>';
    
    container.innerHTML = html;
    modal.style.display = 'flex';
}

function closeLogModal() {
    document.getElementById('logModal').style.display = 'none';
}

// إغلاق عند النقر خارج المودال
window.onclick = function(event) {
    const modal = document.getElementById('logModal');
    if (event.target == modal) closeLogModal();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
