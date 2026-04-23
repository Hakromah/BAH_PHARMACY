<?php
/**
 * Backup & Restore Module
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();

// Yedekleme dizinini kontrol et ve oluştur
$backupDir = dirname(__DIR__, 2) . '/storage/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
    // Güvenlik dosyası
    $deny = "Order deny,allow\nDeny from all\n";
    file_put_contents($backupDir . '/.htaccess', $deny);
}

$action = $_GET['action'] ?? null;
$file = $_GET['file'] ?? null;

// Geri Yükleme, Silme veya İndirme işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        die(__('error'));
    }

    $postAction = $_POST['action'] ?? null;
    $postFile = $_POST['file'] ?? null;

    if ($postAction === 'create') {
        try {
            $tables = [];
            $stmt = $pdo->query("SHOW TABLES");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            $sql = "SET FOREIGN_KEY_CHECKS=0;\n\n";
            foreach ($tables as $table) {
                // Cannot be view to recreate table properly if mixed but let's ignore views for now
                $row2 = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= $row2[1] . ";\n\n";
                $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    $vals = array_map(function ($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote($v);
                    }, array_values($row));
                    $sql .= "INSERT INTO `{$table}` VALUES(" . implode(", ", $vals) . ");\n";
                }
                $sql .= "\n";
            }
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            $filename = "bah_backup_" . date('Ymd_His') . ".sql";
            file_put_contents($backupDir . '/' . $filename, $sql);
            setFlash('success', sprintf(__('backup_created'), $filename));
        } catch (Exception $e) {
            setFlash('error', sprintf(__('backup_create_error'), $e->getMessage()));
        }
        redirect('backup.php');

    } elseif ($postAction === 'restore') {
        try {
            $f = str_replace(['..', '/', '\\'], '', $postFile);
            $path = $backupDir . '/' . $f;
            if ($f && file_exists($path)) {
                $pdo->exec($sql);
                setFlash('success', __('backup_restored'));
            } else {
                setFlash('error', __('backup_not_found'));
            }
        } catch (Exception $e) {
            setFlash('error', sprintf(__('restore_error'), $e->getMessage()));
        }
        redirect('backup.php');

    } elseif ($postAction === 'delete') {
        $f = str_replace(['..', '/', '\\'], '', $postFile);
        $path = $backupDir . '/' . $f;
        if ($f && file_exists($path)) {
            unlink($path);
            setFlash('success', __('backup_deleted'));
        }
        redirect('backup.php');
    }
}

if ($action === 'download' && $file) {
    $f = str_replace(['..', '/', '\\'], '', $file);
    $path = $backupDir . '/' . $f;
    if (file_exists($path)) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $f . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}

// Dosyaları listele
$files = [];
foreach (glob($backupDir . '/*.sql') as $p) {
    $files[] = [
        'name' => basename($p),
        'size' => filesize($p),
        'date' => filemtime($p)
    ];
}
usort($files, fn($a, $b) => $b['date'] <=> $a['date']);

$pageTitle = __('backup_restore');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">
            <?= __('system_backups') ?>
        </h4>
        <p class="text-muted mb-0" style="font-size:13px;">
            <?= __('db_backup_help') ?>
        </p>
    </div>
    <form method="POST" action="backup.php" id="backupForm">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="action" value="create">
        <button type="submit" class="btn-accent" onclick="return confirm('<?= __('confirm_backup_now') ?>');">
            <i class="bi bi-cloud-arrow-down me-1"></i>
            <?= __('create_new_backup') ?>
        </button>
    </form>
</div>

<div class="panel">
    <div class="panel-header">
        <h5><i class="bi bi-hdd-network me-2"></i>
            <?= __('available_backups') ?>
        </h5>
    </div>
    <div class="table-responsive">
        <table class="table-dark-custom">
            <thead>
                <tr>
                    <th>
                        <?= __('filename') ?>
                    </th>
                    <th>
                        <?= __('size') ?>
                    </th>
                    <th>
                        <?= __('creation_date') ?>
                    </th>
                    <th class="text-end">
                        <?= __('actions') ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($files)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">
                            <?= __('no_backups_found') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($files as $f): ?>
                        <tr>
                            <td>
                                <i class="bi bi-filetype-sql" style="color:var(--accent); font-size:16px;"></i>
                                <?= e($f['name']) ?>
                            </td>
                            <td>
                                <?= formatBytes($f['size']) ?>
                            </td>
                            <td>
                                <?= date('d.m.Y H:i:s', $f['date']) ?>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="backup.php" style="display:inline-block;" class="me-1">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="restore">
                                    <input type="hidden" name="file" value="<?= e($f['name']) ?>">
                                    <button type="submit" class="btn-sm-icon btn-edit"
                                        onclick="return confirm('<?= __('confirm_restore') ?>')" title="<?= __('restore') ?>">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </form>
                                <a href="backup.php?action=download&file=<?= e($f['name']) ?>" class="btn-sm-icon btn-edit me-1"
                                    title="<?= __('download') ?>">
                                    <i class="bi bi-download"></i>
                                </a>
                                <form method="POST" action="backup.php" style="display:inline-block;">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="file" value="<?= e($f['name']) ?>">
                                    <button type="submit" class="btn-sm-icon btn-delete"
                                        onclick="return confirm('<?= __('confirm_delete_backup') ?>')"
                                        title="<?= __('delete') ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div
        style="background:rgba(239,68,68,0.1); border-left:3px solid var(--danger); padding:12px 16px; margin: 20px; border-radius:6px;">
        <i class="bi bi-exclamation-triangle" style="color:var(--danger); margin-right:8px;"></i>
        <strong style="color:var(--danger);">
            <?= __('warning') ?>
        </strong>
        <?= __('db_overwrite_warning') ?>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>