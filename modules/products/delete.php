<?php
/**
 * Ürün Sil
 * 
 * Satış kaydında kullanılmış ürünleri silmez (FK koruması).
 * Görseli de diskten kaldırır.
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$id = (int) get('id');

if ($id <= 0) {
    setFlash('error', 'Invalid request.');
    redirect(BASE_URL . '/modules/products/index.php');
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Product not found.');
    redirect(BASE_URL . '/modules/products/index.php');
}

// Hareket Kontrolü (Satış veya Stok Hareketi varsa silme)
$moveCheck = $pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE product_id = :id");
$moveCheck->execute([':id' => $id]);
if ($moveCheck->fetchColumn() > 0) {
    setFlash('error', "This product cannot be deleted because it has stock movements. You can deactivate it instead.");
    redirect(BASE_URL . '/modules/products/index.php');
}

$saleCheck = $pdo->prepare("SELECT COUNT(*) FROM sale_items WHERE product_id = :id");
$saleCheck->execute([':id' => $id]);
if ($saleCheck->fetchColumn() > 0) {
    setFlash('error', "This product cannot be deleted because it has been used in sales. You can deactivate it instead.");
    redirect(BASE_URL . '/modules/products/index.php');
}

try {
    // Görseli diskten sil
    if (!empty($product['image'])) {
        $imgPath = dirname(__DIR__, 2) . '/storage/images/' . $product['image'];
        if (file_exists($imgPath) && is_file($imgPath)) {
            unlink($imgPath);
        }
    }

    $pdo->prepare("DELETE FROM products WHERE id = :id")->execute([':id' => $id]);
    logAction('Product deleted', "ID:{$id} | {$product['name']}");
    setFlash('success', "'{$product['name']}' product deleted successfully.");
} catch (PDOException $e) {
    setFlash('error', 'Product deletion error: ' . $e->getMessage());
}

redirect(BASE_URL . '/modules/products/index.php');
