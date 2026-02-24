<?php
/**
 * لوحة التحكم الرئيسية - Dashboard
 */
require_once __DIR__ . '/../includes/header.php';

$stats = getDashboardStats();

$maritalLabels = [
    'single'   => 'أعزب/عزباء',
    'married'  => 'متزوج/متزوجة',
    'divorced' => 'مطلق/مطلقة',
    'widowed'  => 'أرمل/أرملة',
];

$pageTitle = 'لوحة التحكم';
?>

<div class="page-header">
    <h1 class="page-title">🏠 لوحة التحكم</h1>
    <p class="page-subtitle">نظرة عامة على البيانات المسجلة</p>
</div>

<?= getFlashMessage() ?>

<!-- بطاقات الإحصائيات السريعة -->
<div class="stats-grid">
    <div class="stat-card stat-card--primary">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
            <div class="stat-number"><?= number_format($stats['total_persons']) ?></div>
            <div class="stat-label">إجمالي الأشخاص</div>
        </div>
    </div>
    <div class="stat-card stat-card--success">
        <div class="stat-icon">📅</div>
        <div class="stat-info">
            <div class="stat-number"><?= number_format($stats['today_persons']) ?></div>
            <div class="stat-label">مسجلون اليوم</div>
        </div>
    </div>
    <div class="stat-card stat-card--warning">
        <div class="stat-icon">📆</div>
        <div class="stat-info">
            <div class="stat-number"><?= number_format($stats['month_persons']) ?></div>
            <div class="stat-label">مسجلون هذا الشهر</div>
        </div>
    </div>
    <div class="stat-card stat-card--info">
        <div class="stat-icon">🏷</div>
        <div class="stat-info">
            <div class="stat-number"><?= count($stats['by_category']) ?></div>
            <div class="stat-label">عدد التصنيفات</div>
        </div>
    </div>
</div>

<!-- الصف الثاني -->
<div class="dashboard-grid">
    <!-- معدل النمو (رسم بياني خطي) -->
    <div class="card" style="grid-column: span 2;">
        <div class="card-header">
            <h3 class="card-title">📈 معدل نمو تسجيل الحالات (آخر 12 شهر)</h3>
        </div>
        <div class="card-body">
            <canvas id="growthChart" height="100"></canvas>
        </div>
    </div>

    <!-- توزيع التصنيفات (رسم بياني دائري) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🏷 توزيع الحالات حسب التصنيف</h3>
        </div>
        <div class="card-body">
            <div style="max-width: 300px; margin: 0 auto;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- الحالة الاجتماعية (رسم بياني دائري) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">💍 الحالة الاجتماعية</h3>
        </div>
        <div class="card-body">
            <div style="max-width: 300px; margin: 0 auto;">
                <canvas id="maritalChart"></canvas>
            </div>
        </div>
    </div>

    <!-- أبرز المحافظات (رسم بياني أعمدة) -->
    <div class="card" style="grid-column: span 2;">
        <div class="card-header">
            <h3 class="card-title">📍 توزيع الحالات حسب المحافظات</h3>
        </div>
        <div class="card-body">
            <canvas id="govChart" height="100"></canvas>
        </div>
    </div>

    <!-- آخر المسجلين -->
    <div class="card" style="grid-column: span 2;">
        <div class="card-header">
            <h3 class="card-title">🕐 آخر المسجلين</h3>
            <a href="<?= APP_URL ?>/admin/persons.php" class="card-header-link">عرض الكل</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($stats['latest'])): ?>
                <p class="text-muted text-center p-20">لا توجد بيانات حتى الآن</p>
            <?php else: ?>
            <table class="table table-sm">
                <thead><tr><th>الاسم</th><th>التصنيف</th><th>التاريخ</th></tr></thead>
                <tbody>
                <?php foreach ($stats['latest'] as $p): ?>
                <tr>
                    <td><?= clean($p['full_name']) ?></td>
                    <td><span class="badge badge-info"><?= clean($p['category_name'] ?? '—') ?></span></td>
                    <td><?= formatDate($p['created_at'], true) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- JavaScript للرسوم البيانية -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.font.family = "'Almarai', sans-serif";
    Chart.defaults.color = '#718096';

    // 1. رسم بياني للنمو الشهري
    const ctxGrowth = document.getElementById('growthChart').getContext('2d');
    new Chart(ctxGrowth, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($stats['monthly_growth'], 'month')) ?>,
            datasets: [{
                label: 'عدد المسجلين',
                data: <?= json_encode(array_column($stats['monthly_growth'], 'cnt')) ?>,
                borderColor: '#2c5282',
                backgroundColor: 'rgba(44, 82, 130, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#2c5282',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // 2. توزيع التصنيفات
    const ctxCategory = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxCategory, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($stats['by_category'], 'name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($stats['by_category'], 'cnt')) ?>,
                backgroundColor: <?= json_encode(array_column($stats['by_category'], 'color')) ?>,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            cutout: '70%'
        }
    });

    // 3. الحالة الاجتماعية
    const ctxMarital = document.getElementById('maritalChart').getContext('2d');
    new Chart(ctxMarital, {
        type: 'pie',
        data: {
            labels: [<?php foreach($stats['marital'] as $m) echo "'" . ($maritalLabels[$m['marital_status']] ?? $m['marital_status']) . "',"; ?>],
            datasets: [{
                data: <?= json_encode(array_column($stats['marital'], 'cnt')) ?>,
                backgroundColor: ['#48bb78', '#4299e1', '#ed8936', '#f56565'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // 4. توزيع المحافظات
    const ctxGov = document.getElementById('govChart').getContext('2d');
    new Chart(ctxGov, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($stats['by_governorate'], 'governorate')) ?>,
            datasets: [{
                label: 'عدد الحالات',
                data: <?= json_encode(array_column($stats['by_governorate'], 'cnt')) ?>,
                backgroundColor: '#2b6cb0',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>

<!-- روابط سريعة -->
<div class="card mt-20">
    <div class="card-header">
        <h3 class="card-title">⚡ إجراءات سريعة</h3>
    </div>
    <div class="card-body">
        <div class="quick-actions">
            <a href="<?= APP_URL ?>/admin/person_add.php" class="quick-btn quick-btn--primary">
                <span>➕</span> إضافة شخص جديد
            </a>
            <a href="<?= APP_URL ?>/admin/persons.php" class="quick-btn quick-btn--info">
                <span>👥</span> قائمة الأشخاص
            </a>
            <a href="<?= APP_URL ?>/admin/print_report.php" class="quick-btn quick-btn--success">
                <span>🖨</span> طباعة تقرير
            </a>
            <a href="<?= APP_URL ?>/admin/export_csv.php" class="quick-btn quick-btn--warning">
                <span>📊</span> تصدير CSV
            </a>
            <a href="<?= APP_URL ?>/admin/custom_fields.php" class="quick-btn quick-btn--secondary">
                <span>🔧</span> إدارة الحقول
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
