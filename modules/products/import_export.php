<?php
/**
 * Ürün İçe / Dışa Aktar (Excel/CSV)
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$action = get('action');

// ── AKSİYON: Şablon İndir ────────────────────────────────
if ($action === 'download_template') {
    $filename = "bah_urun_sablonu.csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'Product Name',
        'Barcode',
        'SKU',
        'Form',
        'Category ID',
        'Currency',
        'Purchase Price',
        'Sale Price',
        'Unit',
        'Stock Quantity',
        'Critical Stock'
    ], ';');

    // Örnek bir satır
    fputcsv($out, [
        'Parol 500mg',
        '8699514010101',
        'PRL01',
        'Tablet',
        '1',
        'USD',
        '1.50',
        '2.50',
        'Box',
        '100',
        '10'
    ], ';');

    fclose($out);
    exit;
}

// ── AKSİYON: Dışa Aktar (Tüm Ürünler) ────────────────────
if ($action === 'export') {
    $filename = "bah_tum_urunler_" . date('Ymd_Hi') . ".csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');

    fputcsv($out, [
        'ID',
        'Product Name',
        'Barcode',
        'SKU',
        'Form',
        'Category',
        'Category ID',
        'Currency',
        'Purchase Price',
        'Sale Price',
        'Unit',
        'Stock Quantity',
        'Critical Stock',
        'Active/Inactive Status'
    ], ';');

    $stmt = $pdo->query("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON c.id = p.category_id 
        ORDER BY p.name ASC
    ");

    while ($row = $stmt->fetch()) {
        fputcsv($out, [
            $row['id'],
            $row['name'],
            $row['barcode'],
            $row['sku'],
            $row['dosage_form'],
            $row['category_name'],
            $row['category_id'],
            $row['currency'],
            number_format($row['purchase_price'], 2, ',', ''),
            number_format($row['sale_price'], 2, ',', ''),
            $row['unit'],
            $row['stock_quantity'],
            $row['critical_stock'],
            $row['is_active'] ? 'Active' : 'Inactive'
        ], ';');
    }
    fclose($out);
    exit;
}

// ── AKSİYON: İçe Aktar (POST) ──────────────────────────
$errors = [];
$successCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
        die('CSRF error.');
    }

    $file = $_FILES['csv_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error.';
    } else {
        $handle = fopen($file['tmp_name'], 'r');

        // BOM kontrolü ve geçme
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Başlık satırını oku
        $headers = fgetcsv($handle, 0, ';');

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO products (
                    name, barcode, sku, dosage_form, category_id, 
                    currency, purchase_price, sale_price, 
                    unit, stock_quantity, critical_stock, is_active
                ) VALUES (
                    :name, :barcode, :sku, :dosage_form, :category_id, 
                    :currency, :purchase_price, :sale_price, 
                    :unit, :stock_quantity, :critical_stock, 1
                ) ON DUPLICATE KEY UPDATE 
                    sale_price = VALUES(sale_price),
                    purchase_price = VALUES(purchase_price),
                    stock_quantity = stock_quantity + VALUES(stock_quantity)
            ");

            // Mevcut kategorileri çek (kontrol için)
            $existingCats = $pdo->query("SELECT id FROM categories")->fetchAll(PDO::FETCH_COLUMN);

            while (($row = fgetcsv($handle, 0, ';')) !== FALSE) {
                if (count($row) < 2 || empty($row[0]))
                    continue;

                $catId = !empty($row[4]) ? (int) $row[4] : null;
                // Eğer ID kategoriler tablosunda yoksa NULL yap (FK hatasını önlemek için)
                if ($catId !== null && !in_array($catId, $existingCats)) {
                    $catId = null;
                }

                $stmt->execute([
                    ':name' => $row[0],
                    ':barcode' => !empty($row[1]) ? $row[1] : null,
                    ':sku' => !empty($row[2]) ? $row[2] : null,
                    ':dosage_form' => $row[3] ?? '',
                    ':category_id' => $catId,
                    ':currency' => !empty($row[5]) ? $row[5] : 'USD',
                    ':purchase_price' => (float) str_replace(',', '.', $row[6] ?? 0),
                    ':sale_price' => (float) str_replace(',', '.', $row[7] ?? 0),
                    ':unit' => $row[8] ?? 'Kutu',
                    ':stock_quantity' => (int) ($row[9] ?? 0),
                    ':critical_stock' => (int) ($row[10] ?? 5),
                ]);
                $successCount++;
            }
            $pdo->commit();
            setFlash('success', "$successCount products processed successfully.");
            redirect('index.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
        fclose($handle);
    }
}

$pageTitle = "Product Import / Export";
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">

        <div class="panel mb-4">
            <div class="panel-header">
                <h5><i class="bi bi-download me-2"></i>Export & Template</h5>
            </div>
            <div class="panel-body">
                <p class="text-muted small">You can back up all products in the system or download a sample template for
                    bulk adding.</p>
                <div class="d-flex gap-3">
                    <a href="?action=export" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel me-1"></i> Export All Products as Excel (CSV)
                    </a>
                    <a href="?action=download_template" class="btn btn-outline-info">
                        <i class="bi bi-file-earmark-arrow-down me-1"></i> Download Template
                    </a>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-upload me-2"></i>Bulk Product Add (Import)</h5>
            </div>
            <div class="panel-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?>
                                <li>
                                    <?= $e ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="alert alert-warning small">
                    <i class="bi bi-info-circle me-1"></i> <strong>Important:</strong>
                    The file format must be <strong>CSV (Semicolon ";" separated)</strong>.
                    If products with the same barcode exist, their prices will be updated and the stock quantity will be
                    added on top.
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <div class="mb-4">
                        <label class="form-label-dark">Product CSV File</label>
                        <input type="file" name="csv_file" class="form-control-dark" accept=".csv" required>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn-accent">
                            <i class="bi bi-check-lg me-1"></i> Upload and Process File
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>