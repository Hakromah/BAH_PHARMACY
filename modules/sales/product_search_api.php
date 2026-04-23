<?php
/**
 * Product Quick Search API (for Sales Screen)
 *
 * GET ?q=search&cat=category_id&stock=available|out
 * → Returns JSON
 */

ob_start();
require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim(get('q'));
$cat = get('cat');
$stock = get('stock'); // available | out | ''

$where = ['p.is_active = 1'];
$params = [];

if ($q !== '') {
    $gSearch = buildGoogleSearchQuery(['p.name', 'p.barcode', 'p.sku'], $q, 'q');
    if (!empty($gSearch['sql'])) {
        $where[] = $gSearch['sql'];
        $params = array_merge($params, $gSearch['params']);
    }
}
if ($cat !== '') {
    $where[] = 'p.category_id = :cat';
    $params[':cat'] = (int) $cat;
}
if ($stock === 'available') {
    $where[] = 'p.stock_quantity > 0';
} elseif ($stock === 'out') {
    $where[] = 'p.stock_quantity <= 0';
}

$whereStr = implode(' AND ', $where);

$pdo = Database::getInstance();
$stmt = $pdo->prepare("
    SELECT p.id, p.name, p.barcode, p.dosage_form, p.sale_price, p.currency,
           p.stock_quantity, p.critical_stock, p.unit,
           c.name AS category_name
    FROM   products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE  {$whereStr}
    ORDER  BY p.name
    LIMIT  30
");
$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$targetCurrency = getCurrentCurrency();

// Fiyatları seçili kurdan hesapla
foreach ($products as &$p) {
    $originalPrice = (float) ($p['sale_price'] ?? 0);
    $fromCur = $p['currency'] ?? 'USD';
    $converted = convertCurrency($originalPrice, $fromCur, $targetCurrency);
    $p['sale_price'] = round($converted ?? $originalPrice, 2);
}

ob_clean(); // Önceden oluşmuş olabilecek warning vb çıktıları temizle
echo json_encode(['success' => true, 'data' => $products], JSON_UNESCAPED_UNICODE);
exit;
