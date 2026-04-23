<?php
/**
 * Müşteri Hızlı Arama API (AJAX)
 *
 * Satış ekranından müşteri aramak için kullanılır.
 * GET ?q=arama_terimi → JSON döner
 *
 * Yanıt:
 * [
 *   { "id": 1, "full_name": "Ahmet Yılmaz", "phone": "...", "total_debt": 0.00 },
 *   ...
 * ]
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim(get('q'));

if (strlen($q) < 2) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

$pdo = Database::getInstance();

$whereSql = "1=1";
$params = [];

$gSearch = buildGoogleSearchQuery(['first_name', 'last_name', 'phone', "CONCAT(first_name,' ',last_name)"], $q, 'q');
if (!empty($gSearch['sql'])) {
    $whereSql = $gSearch['sql'];
    $params = $gSearch['params'];
}

$stmt = $pdo->prepare("
    SELECT id,
           CONCAT(first_name,' ',last_name) AS full_name,
           phone,
           total_debt,
           currency,
           payment_due_days
    FROM   customers
    WHERE  is_active = 1 AND {$whereSql}
    ORDER  BY first_name
    LIMIT  10
");
$stmt->execute($params);

$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$targetCurrency = getCurrentCurrency();

foreach ($customers as &$c) {
    if ($c['total_debt'] > 0) {
        $c['total_debt'] = round(convertCurrency((float) $c['total_debt'], $c['currency'] ?? 'USD', $targetCurrency), 2);
    }
}

echo json_encode(['success' => true, 'data' => $customers], JSON_UNESCAPED_UNICODE);
