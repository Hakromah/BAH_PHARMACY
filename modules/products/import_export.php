<?php
/**
 * Product Import / Export (Excel/CSV)
 * Smart version: auto-detects delimiter, dynamic column mapping, string parsing for prices and units.
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$action = get('action');

// Helpers for smart parsing
function detectDelimiter($csvFile) {
    $delimiters = [';' => 0, ',' => 0, "\t" => 0, '|' => 0];
    $handle = fopen($csvFile, "r");
    if ($handle) {
        $firstLine = fgets($handle);
        fclose($handle);
        if ($firstLine) {
            foreach ($delimiters as $delimiter => &$count) {
                $count = count(str_getcsv($firstLine, $delimiter));
            }
            return array_search(max($delimiters), $delimiters);
        }
    }
    return ';';
}

function mapHeaders($headersRow) {
    $mapped = [];
    $fields = [
        'name' => ['product name', 'ürün adı', 'urun adi', 'name', mb_strtolower(__('product_name'))],
        'sku' => ['sku', mb_strtolower(__('sku'))],
        'barcode' => ['barcode', 'barkod', mb_strtolower(__('barcode'))],
        'dosage_form' => ['dosage form', 'form', 'dosage_form', mb_strtolower(__('dosage_form'))],
        'category' => ['category', 'kategori', mb_strtolower(__('category'))],
        'purchase_price' => ['purchase price', 'alış fiyatı', 'alis fiyati', mb_strtolower(__('purchase_price'))],
        'sale_price' => ['sale price', 'satış fiyatı', 'satis fiyati', mb_strtolower(__('sale_price'))],
        'stock_qty' => ['stock qty', 'stock quantity', 'stok miktarı', 'stok', mb_strtolower(__('stock_qty')), mb_strtolower(__('stock_quantity')), 'stock']
    ];
    
    foreach ($headersRow as $index => $header) {
        $h = mb_strtolower(trim($header));
        foreach ($fields as $key => $possibleNames) {
            foreach ($possibleNames as $pn) {
                if ($h === $pn || str_contains($h, $pn)) {
                    $mapped[$key] = $index;
                    break 2; // break both loops
                }
            }
        }
    }
    return $mapped;
}

function parsePriceStr($str) {
    if (empty($str)) return 0.0;
    // Remove anything that is not a digit, comma, period, or minus sign
    $str = preg_replace('/[^0-9,\.-]/', '', $str);
    
    $lastComma = strrpos($str, ',');
    $lastDot = strrpos($str, '.');
    
    if ($lastComma !== false && $lastDot !== false) {
        if ($lastComma > $lastDot) {
            // Comma is decimal
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        } else {
            // Dot is decimal
            $str = str_replace(',', '', $str);
        }
    } elseif ($lastComma !== false) {
        // Only comma, likely decimal
        $str = str_replace(',', '.', $str);
    }
    return (float) $str;
}

function parseStockStr($str) {
    $str = trim($str);
    if (preg_match('/^([\d\.,]+)\s*(.*)$/', $str, $matches)) {
        // Clean quantity
        $qtyStr = str_replace([',', '.'], '', $matches[1]);
        $qty = (int)$qtyStr;
        $unit = trim($matches[2]);
        if (empty($unit)) $unit = __('box') ?? 'Kutu';
        return [$qty, $unit];
    }
    return [(int)$str, __('box') ?? 'Kutu'];
}

function resolveCategory($pdo, $catName) {
    $catName = trim($catName);
    if (empty($catName) || $catName === '—' || $catName === '-') return null;
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
    $stmt->execute([$catName]);
    $id = $stmt->fetchColumn();
    if ($id) return $id;
    // Create new category
    $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$catName]);
    return $pdo->lastInsertId();
}

// ── ACTION: Download Template ────────────────────────────────
if ($action === 'download_template') {
    $filename = "pharmacy_products_template.csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        __('product_name'),
        __('sku'),
        __('barcode'),
        __('dosage_form'),
        __('category'),
        __('purchase_price'),
        __('sale_price'),
        __('stock_qty')
    ], ',');

    fputcsv($out, [
        'Parol 500mg',
        'PRL01',
        '8699514010101',
        'Tablet',
        'Pain Medication',
        '10.50 ₺',
        '15.00 ₺',
        '100 Box'
    ], ',');

    fclose($out);
    exit;
}

// ── ACTION: Export All ────────────────────
if ($action === 'export') {
    $filename = "pharmacy_products_" . date('Ymd_Hi') . ".csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');

    fputcsv($out, [
        __('product_name'),
        __('sku'),
        __('barcode'),
        __('dosage_form'),
        __('category'),
        __('purchase_price'),
        __('sale_price'),
        __('stock_qty')
    ], ',');

    $stmt = $pdo->query("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON c.id = p.category_id 
        ORDER BY p.name ASC
    ");

    while ($row = $stmt->fetch()) {
        fputcsv($out, [
            $row['name'],
            $row['sku'],
            $row['barcode'],
            $row['dosage_form'],
            $row['category_name'],
            $row['purchase_price'] . ' ' . $row['currency'],
            $row['sale_price'] . ' ' . $row['currency'],
            $row['stock_quantity'] . ' ' . $row['unit']
        ], ',');
    }
    fclose($out);
    exit;
}

// ── ACTION: Import (POST) ──────────────────────────
$errors = [];
$successCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
        die('CSRF error.');
    }

    $file = $_FILES['csv_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = __('error') . ': File upload error.';
    } else {
        $delimiter = detectDelimiter($file['tmp_name']);
        $handle = fopen($file['tmp_name'], 'r');

        // Skip BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Read headers
        $headers = fgetcsv($handle, 0, $delimiter);
        $map = mapHeaders($headers);

        if (!isset($map['name'])) {
            $errors[] = "Could not find a valid Product Name column in the sheet headers.";
        } else {
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

                while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                    // Get mapped values safely
                    $name = isset($map['name'], $row[$map['name']]) ? trim($row[$map['name']]) : '';
                    if (empty($name)) continue;

                    $barcode = isset($map['barcode'], $row[$map['barcode']]) ? trim($row[$map['barcode']]) : null;
                    $sku = isset($map['sku'], $row[$map['sku']]) ? trim($row[$map['sku']]) : null;
                    $dosage_form = isset($map['dosage_form'], $row[$map['dosage_form']]) ? trim($row[$map['dosage_form']]) : '';
                    
                    $catName = isset($map['category'], $row[$map['category']]) ? trim($row[$map['category']]) : '';
                    $catId = resolveCategory($pdo, $catName);

                    $purchPriceStr = isset($map['purchase_price'], $row[$map['purchase_price']]) ? $row[$map['purchase_price']] : '0';
                    $salePriceStr = isset($map['sale_price'], $row[$map['sale_price']]) ? $row[$map['sale_price']] : '0';
                    $purchPrice = parsePriceStr($purchPriceStr);
                    $salePrice = parsePriceStr($salePriceStr);

                    $stockStr = isset($map['stock_qty'], $row[$map['stock_qty']]) ? $row[$map['stock_qty']] : '0';
                    list($qty, $unit) = parseStockStr($stockStr);

                    $stmt->execute([
                        ':name' => $name,
                        ':barcode' => $barcode ?: null,
                        ':sku' => $sku ?: null,
                        ':dosage_form' => $dosage_form,
                        ':category_id' => $catId,
                        ':currency' => 'TRY', // Default or extracted
                        ':purchase_price' => $purchPrice,
                        ':sale_price' => $salePrice,
                        ':unit' => $unit,
                        ':stock_quantity' => $qty,
                        ':critical_stock' => 5, // Default
                    ]);
                    $successCount++;
                }
                $pdo->commit();
                setFlash('success', "$successCount " . __('products') . " processed successfully.");
                redirect('index.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
        fclose($handle);
    }
}

$pageTitle = __('import_export');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">

        <div class="panel mb-4">
            <div class="panel-header">
                <h5><i class="bi bi-download me-2"></i><?= __('export') ?> & <?= __('download_template') ?? 'Template' ?></h5>
            </div>
            <div class="panel-body">
                <p class="text-muted small"><?= __('export_desc') ?? 'You can back up all products in the system or download a sample template for bulk adding.' ?></p>
                <div class="d-flex gap-3">
                    <a href="?action=export" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel me-1"></i> <?= __('export') ?>
                    </a>
                    <a href="?action=download_template" class="btn btn-outline-info">
                        <i class="bi bi-file-earmark-arrow-down me-1"></i> <?= __('download_template') ?? 'Download Template' ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h5><i class="bi bi-upload me-2"></i><?= __('import_export') ?> (Import)</h5>
            </div>
            <div class="panel-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?>
                                <li>
                                    <?= e($e) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="alert alert-warning small">
                    <i class="bi bi-info-circle me-1"></i> <strong><?= __('important') ?? 'Important' ?>:</strong>
                    The file format must be <strong>CSV (Comma or Semicolon separated)</strong>.
                    You can use your localized headers. Prices with currency symbols and stock quantities with text units are fully supported.
                    If products with the same barcode exist, their prices will be updated and the stock quantity will be added on top.
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <div class="mb-4">
                        <label class="form-label-dark">Product CSV File</label>
                        <input type="file" name="csv_file" class="form-control-dark" accept=".csv" required>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="index.php" class="btn btn-outline-secondary"><?= __('cancel') ?></a>
                        <button type="submit" class="btn-accent">
                            <i class="bi bi-check-lg me-1"></i> <?= __('save') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>