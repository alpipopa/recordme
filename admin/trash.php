<?php
/**
 * سلة المهملات - Trash
 */
$pageTitle = 'سلة المهملات';
require_once __DIR__ . '/../includes/header.php';

$filters = [
    'q'            => get('q'),
    'only_deleted' => true
];

$page    = max(1, (int)get('page', '1'));
$perPage = 25;
$result  = getPersons($filters, $page, $perPage);
$persons = $result['data'];
$total   = $result['total'];

$baseUrl  = APP_URL . '/admin/trash.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">🗑 سلة المهملات</h1>
        <p class="page-subtitle">إجمالي العناصر المحذوفة: <strong><?= number_format($total) ?></strong></p>
    </div>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/admin/persons.php" class="btn btn-secondary">⬅ العودة للقائمة النشطة</a>
    </div>
</div>

<?= getFlashMessage() ?>

<div class="card mb-20">
    <div class="card-body">
        <form method="GET" action="trash.php" class="search-form">
            <div style="display: flex; gap: 10px;">
                <input type="text" name="q" class="form-control" style="flex: 1;" 
                       value="<?= clean($filters['q'] ?? '') ?>" placeholder="ابحث في سلة المهملات...">
                <button type="submit" class="btn btn-primary" style="padding: 0 30px;">🔍 بحث</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>التصنيف</th>
                        <th>تاريخ الحذف</th>
                        <th class="text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($persons)): ?>
                    <tr>
                        <td colspan="4" class="text-center p-40">
                            <div style="font-size: 40px; margin-bottom: 20px;">♻</div>
                            <p class="text-muted">سلة المهملات فارغة حالياً.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($persons as $p): ?>
                        <tr>
                            <td>
                                <strong><?= clean($p['full_name']) ?></strong>
                            </td>
                            <td>
                                <span class="badge" style="background-color: <?= $p['category_color'] ?>20; color: <?= $p['category_color'] ?>;">
                                    <?= clean($p['category_name']) ?>
                                </span>
                            </td>
                            <td><?= formatDate($p['deleted_at'], true) ?></td>
                            <td class="text-center">
                                <div class="action-btns">
                                    <form action="person_restore.php" method="POST" style="display:inline;" class="btn-confirm" data-confirm="هل تريد استعادة هذا الشخص؟">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="استعادة">♻ استعادة</button>
                                    </form>
                                    <form action="person_force_delete.php" method="POST" style="display:inline;" class="btn-confirm" data-confirm="سيتم حذف البيانات والصور نهائياً. هل أنت متأكد؟">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="حذف نهائي">🗑 حذف نهائي</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-20">
    <?= renderPagination($page, ceil($total / $perPage), $baseUrl) ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
