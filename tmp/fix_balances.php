<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $pdo = Database::getInstance();
    $customers = $pdo->query("SELECT id, first_name, last_name, total_debt FROM customers")->fetchAll();

    foreach ($customers as $c) {
        $cid = $c['id'];

        // Satışlardan kalan toplam borcu hesapla
        $stmt = $pdo->prepare("SELECT SUM(final_amount - paid_amount) as calc_debt FROM sales WHERE customer_id = :cid");
        $stmt->execute([':cid' => $cid]);
        $calcDebt = (float) $stmt->fetchColumn();

        // Eğer fark varsa güncelle
        if (abs($c['total_debt'] - $calcDebt) > 0.001) {
            $pdo->prepare("UPDATE customers SET total_debt = :d WHERE id = :cid")
                ->execute([':d' => $calcDebt, ':cid' => $cid]);
            echo "✅ {$c['first_name']} {$c['last_name']} bakiyesi düzeltildi: {$c['total_debt']} -> {$calcDebt}\n";
        } else {
            echo "ℹ️ {$c['first_name']} {$c['last_name']} bakiyesi zaten doğru.\n";
        }
    }
    echo "✨ Tüm bakiye kontrolleri tamamlandı.\n";

} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
