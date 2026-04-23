<?php
/**
 * CSV Dışa Aktarma
 *
 * ?type=sales|products|customers|stock|logs
 * ?df=YYYY-MM-DD  &dt=YYYY-MM-DD
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$type = get('type', 'sales');
$df = get('df', date('Y-m-01'));
$dt = get('dt', date('Y-m-d'));

$allowed = ['sales', 'products', 'customers', 'stock', 'logs'];
if (!in_array($type, $allowed)) {
    die(__('error'));
}

$filename = "bah_{$type}_" . date('Ymd_Hi') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// BOM (Excel UTF-8)
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// ── SATIŞ RAPORU ───────────────────────────────────────
if ($type === 'sales') {
    fputcsv($out, [
        'Sale No',
        'Date',
        'Customer',
        'Phone',
        'Subtotal',
        'Discount Type',
        'Discount Value',
        'Net Amount',
        'Paid Amount',
        'Remaining Debt',
        'Note'
    ], ';');

    $stmt = $pdo->prepare("
        SELECT s.id, s.created_at,
               CONCAT(c.first_name, ' ', c.last_name) as customer_name,
               c.phone, s.total_amount, s.discount_type, s.discount_value,
               s.final_amount, s.paid_amount, s.remaining_amount, s.note
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE DATE(s.created_at) >= :df AND DATE(s.created_at) <= :dt
        ORDER BY s.created_at ASC
    ");
    $stmt->execute([':df' => $df, ':dt' => $dt]);
    foreach ($stmt->fetchAll() as $row) {
        fputcsv($out, [
            $row['id'],
            date('d.m.Y H:i', strtotime($row['created_at'])),
            $row['customer_name'] ?? 'Retail',
            $row['phone'] ?? '',
            number_format((float) $row['total_amount'], 2, ',', '.'),
            $row['discount_type'],
            $row['discount_value'],
            number_format((float) $row['final_amount'], 2, ',', '.'),
            number_format((float) $row['paid_amount'], 2, ',', '.'),
            number_format((float) $row['remaining_amount'], 2, ',', '.'),
            $row['note'] ?? ''
        ], ';');
    }
}

// ── ÜRÜN RAPORU ────────────────────────────────────────
elseif ($type === 'products') {
    fputcsv($out, [
        'ID',
        'Product Name',
        'Barcode',
        'SKU',
        'Form',
        'Category',
        'Purchase Price',
        'Sale Price',
        'Quantity',
        'Unit',
        'Critical Stock',
        'Status',
        'Total Sold',
        'Total Sold Revenue'
    ], ';');

    $stmt = $pdo->prepare("
        SELECT p.*, c.name as cat_name,
               (SELECT SUM(si.quantity) FROM sale_items si JOIN sales s ON si.sale_id = s.id
                WHERE si.product_id = p.id AND DATE(s.created_at) >= :df AND DATE(s.created_at) <= :dt) as sold_qty,
               (SELECT SUM(si.total_price) FROM sale_items si JOIN sales s ON si.sale_id = s.id
                WHERE si.product_id = p.id AND DATE(s.created_at) >= :df AND DATE(s.created_at) <= :dt) as sold_revenue
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.name ASC
    ");
    $stmt->execute([':df' => $df, ':dt' => $dt]);
    foreach ($stmt->fetchAll() as $row) {
        $status = $row['stock_quantity'] <= 0
            ? 'Out of Stock'
            : ($row['stock_quantity'] <= $row['critical_stock'] ? 'Critical' : 'Sufficient');

        fputcsv($out, [
            $row['id'],
            $row['name'],
            $row['barcode'] ?? '',
            $row['sku'] ?? '',
            $row['dosage_form'] ?? '',
            $row['cat_name'] ?? '',
            number_format($row['purchase_price'], 2, ',', '.'),
            number_format($row['sale_price'], 2, ',', '.'),
            $row['stock_quantity'],
            $row['unit'],
            $row['critical_stock'],
            $status,
            (int) $row['sold_qty'],
            number_format((float) $row['sold_revenue'], 2, ',', '.'),
        ], ';');
    }
}

// ── STOK HAREKETLERİ ───────────────────────────────────
elseif ($type === 'stock') {
    fputcsv($out, ['ID', 'Date', 'Product', 'Type', 'Quantity', 'Reference', 'Note'], ';');

    $stmt = $pdo->prepare("
        SELECT sm.*, p.name AS pname
        FROM stock_movements sm
        JOIN products p ON p.id = sm.product_id
        WHERE DATE(sm.created_at) BETWEEN :df AND :dt
        ORDER BY sm.created_at ASC
    ");
    $stmt->execute([':df' => $df, ':dt' => $dt]);

    $typeMap = ['in' => 'In', 'out' => 'Out', 'adjust' => 'Adjust', 'convert' => 'Convert'];
    foreach ($stmt->fetchAll() as $row) {
        fputcsv($out, [
            $row['id'],
            date('d.m.Y H:i', strtotime($row['created_at'])),
            $row['pname'],
            $typeMap[$row['type']] ?? $row['type'],
            $row['quantity'],
            $row['reference'] ?? '',
            $row['note'] ?? '',
        ], ';');
    }
}

// ── MÜŞTERİ RAPORU ────────────────────────────────────
elseif ($type === 'customers') {
    fputcsv($out, [
        'ID',
        'Name Surname',
        'Phone',
        'Due (Days)',
        'Total Sales',
        'Total Spent',
        'Total Debt',
        'Registration Date'
    ], ';');

    $stmt = $pdo->query("
        SELECT c.*,
               COUNT(s.id) AS sale_count,
               SUM(s.final_amount) AS total_spent
        FROM customers c
        LEFT JOIN sales s ON s.customer_id = c.id
        GROUP BY c.id
        ORDER BY c.first_name ASC
    ");

    foreach ($stmt->fetchAll() as $row) {
        fputcsv($out, [
            $row['unique_id'],
            $row['first_name'] . ' ' . $row['last_name'],
            $row['phone'] ?? '',
            $row['payment_due_days'],
            (int) $row['sale_count'],
            number_format((float) $row['total_spent'], 2, ',', '.'),
            number_format((float) $row['total_debt'], 2, ',', '.'),
            date('d.m.Y', strtotime($row['created_at'])),
        ], ';');
    }
}

// ── LOG RAPORU ───────────────────────────────────────
elseif ($type === 'logs') {
    fputcsv($out, ['ID', 'Date', 'Action', 'User', 'IP', 'Detail'], ';');

    $stmt = $pdo->prepare("
        SELECT * FROM logs
        WHERE DATE(timestamp) BETWEEN :df AND :dt
        ORDER BY timestamp ASC
    ");
    $stmt->execute([':df' => $df, ':dt' => $dt]);

    foreach ($stmt->fetchAll() as $row) {
        fputcsv($out, [
            $row['id'],
            date('d.m.Y H:i:s', strtotime($row['timestamp'])),
            $row['action'],
            $row['user'] ?? 'system',
            $row['ip'] ?? '',
            $row['detail'] ?? '',
        ], ';');
    }
}

fclose($out);
exit;
