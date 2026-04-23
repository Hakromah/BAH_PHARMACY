<?php
require_once 'config/config.php';
require_once 'core/Database.php';
$pdo = Database::getInstance();
$count = $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
echo "Product count: " . $count . "\n";
if ($count > 0) {
    $products = $pdo->query('SELECT id, name, is_active FROM products LIMIT 10')->fetchAll();
    print_r($products);
}
