<?php
/**
 * Action Logs
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();

// LOG CLEANING
if (get('action') === 'clear_all') {
    $pdo->exec("TRUNCATE TABLE logs");
    logAction('Logs Cleared', 'All system logs cleared by user.');
    setFlash('success', __('success'));
    redirect('logs.php');
}

// Filtreler
$search = get('search');
$dateFrom = get('date_from');
$dateTo = get('date_to');

$where = ['1=1'];
$params = [];

if ($search !== '') {
    $where[] = '(l.action LIKE :s OR l.detail LIKE :s OR l.user LIKE :s)';
    $params[':s'] = '%' . $search . '%';
}
if ($dateFrom !== '') {
    $where[] = 'DATE(l.timestamp) >= :df';
    $params[':df'] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = 'DATE(l.timestamp) <= :dt';
    $params[':dt'] = $dateTo;
}

$whereStr = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM logs l WHERE {$whereStr}");
$stmt->execute($params);
$totalLogs = $stmt->fetchColumn();

// Sayfalama (İstenirse eklenebilir, şimdilik statik 250)
$stmt = $pdo->prepare("SELECT l.* FROM logs l WHERE {$whereStr} ORDER BY l.timestamp DESC LIMIT 250");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$pageTitle = __('action_logs');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>
<!-- Filtreler -->
<div class="panel mb-4">
    <div class="panel-body">
        <form method="GET" action="logs.php" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label-dark">
                    <?= __('search') ?>
                </label>
                <input type="text" name="search" class="form-control-dark" placeholder="<?= __('search') ?>..."
                    value="<?= e($search) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label-dark">
                    <?= __('date_start') ?>
                </label>
                <input type="date" name="date_from" class="form-control-dark" value="<?= e($dateFrom) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label-dark">
                    <?= __('date_end') ?>
                </label>
                <input type="date" name="date_to" class="form-control-dark" value="<?= e($dateTo) ?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn-accent"><i class="bi bi-search"></i>
                    <?= __('filter') ?>
                </button>
                <a href="logs.php" class="btn btn-outline-secondary btn-sm align-self-end">
                    <?= __('all') ?>
                </a>
                <a href="export.php?type=logs&df=<?= urlencode($dateFrom) ?>&dt=<?= urlencode($dateTo) ?>"
                    class="btn btn-outline-secondary btn-sm align-self-end"><i class="bi bi-filetype-csv me-1"></i>
                    <?= __('export_csv') ?>
                </a>
                <a href="?action=clear_all" class="btn btn-outline-danger btn-sm align-self-end"
                    onclick="return confirm('<?= __('confirm_delete') ?>')">
                    <i class="bi bi-trash me-1"></i> <?= __('clear_all') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tablo -->
<div class="panel">
    <div class="panel-header">
        <h5><i class="bi bi-journal-text me-2"></i>
            <?= __('action_logs') ?><span class="badge bg-secondary ms-2">
                <?= count($logs) ?>
            </span>
            <span style="font-size:12px;color:var(--text-muted);margin-left:8px;">
                <?= __('total') ?>:
                <?= number_format($totalLogs) ?>
                <?= __('items') ?>
            </span>
        </h5>
    </div>
    <div class="table-responsive">
        <table class="table-dark-custom">
            <thead>
                <tr>
                    <th>#</th>
                    <th>
                        <?= __('date') ?>
                    </th>
                    <th>
                        <?= __('action') ?>
                    </th>
                    <th>
                        <?= __('detail') ?>
                    </th>
                    <th>
                        <?= __('user') ?>
                    </th>
                    <th>
                        IP
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5" style="color:var(--text-muted);">
                            <i class="bi bi-journal" style="font-size:36px;display:block;margin-bottom:8px;"></i>
                            <?= __('no_data') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log):
                        // İşlem rengini belirle
                        $action = strtolower($log['action']);
                        $color = 'var(--text-primary)';
                        $icon = 'bi-circle';
                        if (str_contains($action, 'added') || str_contains($action, 'sale')) {
                            $color = 'var(--success)';
                            $icon = 'bi-plus-circle';
                        } elseif (str_contains($action, 'deleted') || str_contains($action, 'cancelled')) {
                            $color = 'var(--danger)';
                            $icon = 'bi-trash';
                        } elseif (str_contains($action, 'edited') || str_contains($action, 'updated')) {
                            $color = 'var(--warning)';
                            $icon = 'bi-pencil-square';
                        } elseif (str_contains($action, 'payment')) {
                            $color = '#a78bfa';
                            $icon = 'bi-cash-coin';
                        }
                        ?>
                        <tr>
                            <td style="color:var(--text-muted);font-size:12px;">
                                <?= $log['id'] ?>
                            </td>
                            <td style="font-size:12px;white-space:nowrap;">
                                <?= date('d.m.Y', strtotime($log['timestamp'])) ?>
                                <br><span style="color:var(--text-muted);">
                                    <?= date('H:i:s', strtotime($log['timestamp'])) ?>
                                </span>
                            </td>
                            <td>
                                <span style="color:<?= $color ?>;">
                                    <i class="bi <?= $icon ?> me-1"></i>
                                    <?= e($log['action']) ?>
                                </span>
                            </td>
                            <td style="font-size:12px;color:var(--text-muted);max-width:300px;">
                                <?= e($log['detail'] ?? '—') ?>
                            </td>
                            <td style="font-size:12px;">
                                <?= e($log['user'] ?? 'system') ?>
                            </td>
                            <td style="font-size:12px;color:var(--text-muted);">
                                <?= e($log['ip'] ?? '—') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>