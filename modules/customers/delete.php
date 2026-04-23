<?php
/**
 * Müşteri Sil
 *
 * Satış kaydı olan müşteri silinemez (FK koruması + manuel kontrol).
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$id = (int) get('id');

if ($id <= 0) {
    setFlash('error', 'Invalid request.');
    redirect(BASE_URL . '/modules/customers/index.php');
}

$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('error', 'Customer not found.');
    redirect(BASE_URL . '/modules/customers/index.php');
}

// Satış ve Ödeme kontrolü
$saleCount = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE customer_id = :id");
$saleCount->execute([':id' => $id]);
$paymentCount = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE customer_id = :id");
$paymentCount->execute([':id' => $id]);

if ($saleCount->fetchColumn() > 0 || $paymentCount->fetchColumn() > 0) {
    setFlash('error', 'This customer has sales or payment records. You can deactivate instead of deleting.');
    redirect(BASE_URL . '/modules/customers/index.php');
}

try {
    $pdo->prepare("DELETE FROM customers WHERE id = :id")->execute([':id' => $id]);
    logAction('Customer deleted', "ID:{$id} | {$customer['first_name']} {$customer['last_name']}");
    setFlash('success', "{$customer['first_name']} {$customer['last_name']} deleted.");
} catch (PDOException $e) {
    setFlash('error', 'Deletion error: ' . $e->getMessage());
}

redirect(BASE_URL . '/modules/customers/index.php');
