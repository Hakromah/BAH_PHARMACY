<?php
/**
 * Batch Delete Products
 * 
 * Silinebilecekleri siler, hareket görenleri atlar (FK koruması).
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/modules/products/index.php');
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
    die(__('error'));
}

$pdo = Database::getInstance();
$productIds = $_POST['product_ids'] ?? [];

if (empty($productIds) || !is_array($productIds)) {
    setFlash('error', __('no_data_selected') ?? 'No items selected for deletion.');
    redirect(BASE_URL . '/modules/products/index.php');
}

$deletedCount = 0;
$skippedCount = 0;

$moveCheck = $pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE product_id = :id");
$saleCheck = $pdo->prepare("SELECT COUNT(*) FROM sale_items WHERE product_id = :id");
$delStmt = $pdo->prepare("DELETE FROM products WHERE id = :id");

foreach ($productIds as $id) {
    $id = (int) $id;
    if ($id <= 0) continue;

    // Ürünü bul
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();

    if (!$product) continue;

    // Hareket Kontrolü
    $moveCheck->execute([':id' => $id]);
    if ($moveCheck->fetchColumn() > 0) {
        $skippedCount++;
        continue;
    }

    $saleCheck->execute([':id' => $id]);
    if ($saleCheck->fetchColumn() > 0) {
        $skippedCount++;
        continue;
    }

    try {
        // Görseli diskten sil
        if (!empty($product['image'])) {
            $imgPath = dirname(__DIR__, 2) . '/storage/images/' . $product['image'];
            if (file_exists($imgPath) && is_file($imgPath)) {
                unlink($imgPath);
            }
        }

        $delStmt->execute([':id' => $id]);
        $deletedCount++;
        logAction('Product deleted (Batch)', "ID:{$id} | {$product['name']}");
    } catch (PDOException $e) {
        $skippedCount++;
    }
}

if ($deletedCount > 0 && $skippedCount == 0) {
    setFlash('success', "{$deletedCount} products deleted successfully.");
} elseif ($deletedCount > 0 && $skippedCount > 0) {
    setFlash('warning', "{$deletedCount} products deleted successfully. {$skippedCount} products were skipped because they have existing sales or stock movements.");
} elseif ($deletedCount == 0 && $skippedCount > 0) {
    setFlash('error', "No products were deleted. {$skippedCount} products were skipped because they are in use.");
} else {
    setFlash('info', "No products were processed.");
}

redirect(BASE_URL . '/modules/products/index.php');
