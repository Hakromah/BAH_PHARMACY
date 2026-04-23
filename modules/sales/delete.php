<?php
/**
 * Sale Cancel / Delete
 *
 * Restores stock, adjusts customer debt.
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$id = (int) get('id');

if ($id <= 0) {
    setFlash('error', __('invalid_sale'));
    redirect(BASE_URL . '/modules/sales/index.php');
}

$stmt = $pdo->prepare("SELECT * FROM sales WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$sale = $stmt->fetch();

if (!$sale) {
    setFlash('error', __('sale_not_found'));
    redirect(BASE_URL . '/modules/sales/index.php');
}

// Satış kalemlerini al
$items = $pdo->prepare("SELECT * FROM sale_items WHERE sale_id = :sid");
$items->execute([':sid' => $id]);
$items = $items->fetchAll();

$pdo->beginTransaction();
try {
    // Stokları geri yükle
    foreach ($items as $item) {
        $pdo->prepare("
            UPDATE products SET stock_quantity = stock_quantity + :qty WHERE id = :pid
        ")->execute([':qty' => $item['quantity'], ':pid' => $item['product_id']]);

        // Stok hareketi
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

    // Müşteri borcunu düzelt
    if ($sale['customer_id'] && $sale['remaining_amount'] > 0) {
        $pdo->prepare("
            UPDATE customers
            SET total_debt = GREATEST(0, total_debt - :amt)
            WHERE id = :cid
        ")->execute([':amt' => $sale['remaining_amount'], ':cid' => $sale['customer_id']]);
    }

    // Sale + sale_items sil (CASCADE ile sale_items otomatik silinir)
    $pdo->prepare("DELETE FROM sales WHERE id = :id")->execute([':id' => $id]);

    $pdo->commit();
    logAction('Sale Cancelled', __('sale_log_cancelled', $id, $sale['customer_id']));
    setFlash('success', __('sale_cancelled_success', $id));
} catch (Exception $e) {
    $pdo->rollBack();
    setFlash('error', sprintf(__('cancel_error'), $e->getMessage()));
}

redirect(BASE_URL . '/modules/sales/index.php');
