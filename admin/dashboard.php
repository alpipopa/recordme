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
    // إعدادات الوضع المظلم للرسوم البيانية
    function getChartOptions(isDark) {
        const textColor = isDark ? '#cbd5e1' : '#718096';
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)';
        
        return {
            responsive: true,
            plugins: {
                legend: {
                    labels: { color: textColor }
                }
            },
            scales: {
                x: {
                    grid: { color: gridColor },
                    ticks: { color: textColor }
                },
                y: {
                    grid: { color: gridColor },
                    ticks: { color: textColor },
                    beginAtZero: true
                }
            }
        };
    }

    let isDark = document.body.classList.contains('dark-mode');
    Chart.defaults.font.family = "'Almarai', sans-serif";
    
    // 1. نمو الحالات
    const growthChart = new Chart(document.getElementById('growthChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($stats['monthly_growth'], 'month')) ?>,
            datasets: [{
                label: 'عدد المسجلين',
                data: <?= json_encode(array_column($stats['monthly_growth'], 'cnt')) ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3
            }]
        },
        options: getChartOptions(isDark)
    });

    // 2. توزيع التصنيفات
    const categoryChart = new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($stats['by_category'], 'name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($stats['by_category'], 'cnt')) ?>,
                backgroundColor: <?= json_encode(array_column($stats['by_category'], 'color')) ?>,
                borderWidth: 2,
                borderColor: isDark ? '#1e293b' : '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { color: isDark ? '#cbd5e1' : '#718096' } } },
            cutout: '70%'
        }
    });

    // 3. الحالة الاجتماعية
    const maritalChart = new Chart(document.getElementById('maritalChart'), {
        type: 'pie',
        data: {
            labels: [<?php foreach($stats['marital'] as $m) echo "'" . ($maritalLabels[$m['marital_status']] ?? $m['marital_status']) . "',"; ?>],
            datasets: [{
                data: <?= json_encode(array_column($stats['marital'], 'cnt')) ?>,
                backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                borderWidth: 2,
                borderColor: isDark ? '#1e293b' : '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { color: isDark ? '#cbd5e1' : '#718096' } } }
        }
    });

    // 4. توزيع المحافظات
    const govChart = new Chart(document.getElementById('govChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($stats['by_governorate'], 'governorate')) ?>,
            datasets: [{
                label: 'عدد الحالات',
                data: <?= json_encode(array_column($stats['by_governorate'], 'cnt')) ?>,
                backgroundColor: '#3b82f6',
                borderRadius: 5
            }]
        },
        options: getChartOptions(isDark)
    });

    // مستمع لتغيير الوضع لتحديث الرسوم البيانية
    document.getElementById('darkToggle')?.addEventListener('click', () => {
        setTimeout(() => {
            const newIsDark = document.body.classList.contains('dark-mode');
            const newOpts = getChartOptions(newIsDark);
            
            [growthChart, govChart].forEach(chart => {
                chart.options.scales.x.ticks.color = newOpts.scales.x.ticks.color;
                chart.options.scales.y.ticks.color = newOpts.scales.y.ticks.color;
                chart.options.scales.x.grid.color = newOpts.scales.x.grid.color;
                chart.options.scales.y.grid.color = newOpts.scales.y.grid.color;
                chart.options.plugins.legend.labels.color = newOpts.plugins.legend.labels.color;
                chart.update();
            });

            [categoryChart, maritalChart].forEach(chart => {
                chart.options.plugins.legend.labels.color = newIsDark ? '#cbd5e1' : '#718096';
                chart.data.datasets[0].borderColor = newIsDark ? '#1e293b' : '#fff';
                chart.update();
            });
        }, 100);
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
