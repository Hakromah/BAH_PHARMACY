<?php
/**
 * Batch Delete Sales
 */
require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/modules/sales/index.php');
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
    die(__('error'));
}

$pdo = Database::getInstance();
$saleIds = $_POST['sale_ids'] ?? [];

if (empty($saleIds) || !is_array($saleIds)) {
    setFlash('error', __('no_data_selected') ?? 'No items selected for deletion.');
    redirect(BASE_URL . '/modules/sales/index.php');
}

$deletedCount = 0;
$skippedCount = 0;

foreach ($saleIds as $id) {
    $id = (int) $id;
    if ($id <= 0) continue;

    $stmt = $pdo->prepare("SELECT * FROM sales WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $sale = $stmt->fetch();

    if (!$sale) {
        $skippedCount++;
        continue;
    }

    $itemsStmt = $pdo->prepare("SELECT * FROM sale_items WHERE sale_id = :sid");
    $itemsStmt->execute([':sid' => $id]);
    $items = $itemsStmt->fetchAll();

    $pdo->beginTransaction();
    try {
        foreach ($items as $item) {
            $pdo->prepare("
                UPDATE products SET stock_quantity = stock_quantity + :qty WHERE id = :pid
            ")->execute([':qty' => $item['quantity'], ':pid' => $item['product_id']]);

            $pdo->prepare("
                INSERT INTO stock_movements (product_id, type, quantity, reference, note)
                VALUES (:pid, 'in', :qty, :ref, :note)
            ")->execute([
                ':pid' => $item['product_id'],
                ':qty' => $item['quantity'],
                ':ref' => sprintf(__('sale_cancelled_success'), $id),
                ':note' => __('sale_cancellation')
            ]);
        }

        if ($sale['customer_id'] && $sale['remaining_amount'] > 0) {
            $pdo->prepare("
                UPDATE customers
                SET total_debt = GREATEST(0, total_debt - :amt)
                WHERE id = :cid
            ")->execute([':amt' => $sale['remaining_amount'], ':cid' => $sale['customer_id']]);
        }

        $pdo->prepare("UPDATE payments SET sale_id = NULL WHERE sale_id = :id")->execute([':id' => $id]);
        $pdo->prepare("DELETE FROM sales WHERE id = :id")->execute([':id' => $id]);

        $pdo->commit();
        logAction('Sale Cancelled', __('sale_log_cancelled', $id, $sale['customer_id']));
        $deletedCount++;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            try { $pdo->rollBack(); } catch (Exception $re) {}
        }
        $skippedCount++;
    }
}

if ($deletedCount > 0 && $skippedCount == 0) {
    setFlash('success', "{$deletedCount} sales deleted successfully.");
} elseif ($deletedCount > 0 && $skippedCount > 0) {
    setFlash('warning', "{$deletedCount} sales deleted successfully. {$skippedCount} skipped.");
} elseif ($deletedCount == 0 && $skippedCount > 0) {
    setFlash('error', "No sales were deleted. {$skippedCount} skipped due to errors.");
} else {
    setFlash('info', "No sales were processed.");
}

redirect(BASE_URL . '/modules/sales/index.php');
