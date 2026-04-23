<?php
/**
 * Hızlı Müşteri Ekleme API (AJAX)
 *
 * POST: first_name, last_name, phone, csrf_token
 * → { success: true, id: X, full_name: '...', phone: '...' }
 *    veya { success: false, error: '...' }
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
    echo json_encode(['success' => false, 'error' => 'CSRF error.']);
    exit;
}

$firstName = post('first_name');
$lastName = post('last_name');
$phone = post('phone');

if (empty($firstName) || empty($lastName)) {
    echo json_encode(['success' => false, 'error' => 'First name and last name are required.']);
    exit;
}

// Telefon benzersizlik
if (!empty($phone)) {
    $pdo = Database::getInstance();
    $s = $pdo->prepare("SELECT id FROM customers WHERE phone = :p LIMIT 1");
    $s->execute([':p' => $phone]);
    if ($s->fetch()) {
        echo json_encode(['success' => false, 'error' => 'This phone number is already registered.']);
        exit;
    }
}

$uuid = generateUUID();
$pdo = Database::getInstance();

$targetCurrency = getCurrentCurrency();

$pdo->prepare("
    INSERT INTO customers (unique_id, first_name, last_name, phone, currency, payment_due_days)
    VALUES (:uid, :fn, :ln, :phone, :currency, 30)
")->execute([
            ':uid' => $uuid,
            ':fn' => $firstName,
            ':ln' => $lastName,
            ':phone' => $phone ?: null,
            ':currency' => $targetCurrency,
        ]);

$newId = (int) $pdo->lastInsertId();
logAction('Quick customer added', "ID:{$newId} | {$firstName} {$lastName}");

echo json_encode([
    'success' => true,
    'id' => $newId,
    'full_name' => $firstName . ' ' . $lastName,
    'phone' => $phone,
]);
