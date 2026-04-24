<?php
/**
 * Stock & Critical Stock Report (Printable)
 */

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();

$type = get('type', 'all'); // all | critical | out

$where = ['p.is_active = 1'];
$params = [];

if ($type === 'critical') {
    $where[] = 'p.stock_quantity > 0 AND p.stock_quantity <= p.critical_stock';
} elseif ($type === 'out') {
    $where[] = 'p.stock_quantity <= 0';
}

$whereStr = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT p.*, c.name as cat_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE {$whereStr} 
    ORDER BY p.name ASC
");
$stmt->execute($params);
$products = $stmt->fetchAll();

$totalValue = 0;
foreach ($products as $p) {
    if ($p['stock_quantity'] > 0) {
        $totalValue += $p['stock_quantity'] * $p['purchase_price'];
    }
}

$printDate = date('d.m.Y H:i');
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLang() ?>">

<head>
    <meta charset="UTF-8">
    <title><?= __('stock_report') ?> | BAH Pharmacy</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fff;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .toolbar {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .btn-print {
            background: #3b82f6;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-excel {
            background: #10b981;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 5px;
        }

        .btn-back {
            display: inline-block;
            padding: 6px 12px;
            border: 1px solid #ccc;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            margin-left: 5px;
        }

        .btn-back.active {
            background: #3b82f6;
            color: #fff;
            border-color: #3b82f6;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .report-header h1 {
            margin: 0;
            font-size: 24px;
            color: #1e293b;
        }

        .meta {
            text-align: right;
            font-size: 14px;
            color: #64748b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 13px;
        }

        th,
        td {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            text-align: left;
        }

        th {
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            text-transform: uppercase;
        }

        td.low {
            color: #f59e0b;
            font-weight: 600;
        }

        td.out {
            color: #ef4444;
            font-weight: 600;
        }

        td.ok {
            color: #10b981;
        }

        .summary {
            display: flex;
            gap: 40px;
            border-top: 2px solid #e2e8f0;
            padding-top: 15px;
            margin-top: 20px;
        }

        .summary>div {
            font-size: 18px;
        }

        .filter-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            background: #e2e8f0;
            font-size: 12px;
            font-weight: normal;
            margin-left: 10px;
            vertical-align: middle;
        }

        @media print {
            .toolbar { display: none !important; }
            body { padding: 0; }
            @page { size: A4 portrait; margin: 15mm; }
        }

        .report-logo {
            width: 90px !important;
            height: 90px !important;
            object-fit: contain;
            display: block;
        }
    </style>
</head>

<body>

    <div class="toolbar d-print-none"
        style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <div>
            <button class="btn-print" onclick="window.print()"><?= __('print') ?> / PDF</button>
            <button class="btn-excel" onclick="exportExcel()">Excel İndir</button>
            <a href="index.php" class="btn-back"><?= __('reports') ?></a>
            <a href="stock_report.php?type=all"
                class="btn-back <?= $type === 'all' ? 'active' : '' ?>"><?= __('all') ?></a>
            <a href="stock_report.php?type=critical"
                class="btn-back <?= $type === 'critical' ? 'active' : '' ?>"><?= __('critical') ?></a>
            <a href="stock_report.php?type=out"
                class="btn-back <?= $type === 'out' ? 'active' : '' ?>"><?= __('out_of_stock') ?></a>
        </div>
        <div style="display:flex; gap:10px;">
            <input type="text" id="searchInput" placeholder="İlaç, Barkod, Form veya Kategori Ara..."
                style="padding:6px 12px; border:1px solid #ccc; border-radius:4px; width:280px;">
            <select id="catFilter" style="padding:6px 12px; border:1px solid #ccc; border-radius:4px;">
                <option value="">Tüm Kategoriler</option>
                <?php
                $cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
                foreach ($cats as $c) {
                    echo '<option value="' . $c['id'] . '">' . $c['name'] . '</option>';
                }
                ?>
            </select>
        </div>
    </div>

    <div class="report-header">
        <div class="print-header">
            <h1 style="display:flex; align-items:center; gap:15px;">
                <?php if (file_exists(dirname(__DIR__, 2) . '/storage/images/logo.png')): ?>
                    <img src="<?= BASE_URL ?>/storage/images/logo.png?v=<?= time() ?>" alt="Logo" class="report-logo">
                <?php else: ?>
                    <div class="report-logo" style="border-radius:6px; background:#e7d86d; color:#000; display:flex; align-items:center; justify-content:center; font-size:16px;">
                        BAH</div>
                <?php endif; ?>
                <div>
                    <div>BAH Pharmacy <?= __('stock_report') ?></div>
                </div>
            </h1>
            <?php
            $labels = ['all' => __('all'), 'critical' => __('critical_stock'), 'out' => __('out_of_stock')];
            echo '<span class="filter-badge">' . $labels[$type] . '</span>';
            ?>
        </div>
        <div class="meta">
            <?= __('date') ?>: <strong>
                <?= $printDate ?>
            </strong><br>
            <?= __('total') ?>: <strong>
                <?= count($products) ?> <?= __('products') ?>
            </strong>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th><?= __('product_name') ?></th>
                <th><?= __('barcode') ?></th>
                <th><?= __('dosage_form') ?></th>
                <th><?= __('category') ?></th>
                <th><?= __('purchase') ?></th>
                <th><?= __('sale') ?></th>
                <th><?= __('stock') ?></th>
                <th><?= __('unit') ?></th>
                <th><?= __('critical') ?></th>
                <th><?= __('stock_value') ?></th>
                <th><?= __('status') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $i => $p):
                $isOut = $p['stock_quantity'] <= 0;
                $isLow = !$isOut && $p['stock_quantity'] <= $p['critical_stock'];
                $cls = $isOut ? 'out' : ($isLow ? 'low' : 'ok');
                $label = $isOut ? __('out_of_stock') : ($isLow ? __('critical') : __('sufficient'));
                $stockVal = $p['stock_quantity'] * $p['purchase_price'];
                ?>
                <tr class="stock-row" data-cat="<?= $p['category_id'] ?>"
                    data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>"
                    data-barcode="<?= htmlspecialchars(strtolower($p['barcode'] ?? '')) ?>"
                    data-form="<?= htmlspecialchars(strtolower($p['dosage_form'] ?? '')) ?>"
                    data-catname="<?= htmlspecialchars(strtolower($p['cat_name'] ?? '')) ?>"
                    data-stockval="<?= $stockVal > 0 ? $stockVal : 0 ?>">
                    <td>
                        <?= $i + 1 ?>
                    </td>
                    <td><strong>
                            <?= htmlspecialchars($p['name']) ?>
                        </strong></td>
                    <td>
                        <?= htmlspecialchars($p['barcode'] ?? '—') ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($p['dosage_form'] ?? '—') ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($p['cat_name'] ?? '—') ?>
                    </td>
                    <td>
                        <?= formatMoney($p['purchase_price']) ?>
                    </td>
                    <td>
                        <?= formatMoney($p['sale_price']) ?>
                    </td>
                    <td class="<?= $cls ?>"><strong>
                            <?= (int) $p['stock_quantity'] ?>
                        </strong></td>
                    <td>
                        <?= htmlspecialchars($p['unit']) ?>
                    </td>
                    <td>
                        <?= (int) $p['critical_stock'] ?>
                    </td>
                    <td>
                        <?= $stockVal > 0 ? formatMoney($stockVal) : '—' ?>
                    </td>
                    <td class="<?= $cls ?>">
                        <?= $label ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="summary">
        <div>
            <div style="font-size:11px;color:#666;"><?= __('total_products') ?></div>
            <strong id="totalCount">
                <?= count($products) ?>
            </strong>
        </div>
        <div>
            <div style="font-size:11px;color:#666;"><?= __('total_stock_value') ?></div>
            <strong id="totalStockValue">
                <?= formatMoney($totalValue) ?>
            </strong>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sInput = document.getElementById('searchInput');
            const cFilter = document.getElementById('catFilter');
            const rows = document.querySelectorAll('.stock-row');
            const totalCount = document.getElementById('totalCount');
            const totalStockValue = document.getElementById('totalStockValue');
            const currencySymbol = '<?= getCurrencySymbol() ?>';

            function applyFilter() {
                const query = sInput.value.toLowerCase();
                const cat = cFilter.value;
                let visibleCount = 0;
                let visibleStockVal = 0;

                rows.forEach(r => {
                    const name = r.getAttribute('data-name');
                    const barcode = r.getAttribute('data-barcode');
                    const form = r.getAttribute('data-form');
                    const catName = r.getAttribute('data-catname');
                    const rCat = r.getAttribute('data-cat');
                    const val = parseFloat(r.getAttribute('data-stockval'));

                    const matchesText = name.includes(query) || barcode.includes(query) || form.includes(query) || catName.includes(query);
                    const matchesCat = (cat === '' || rCat === cat);

                    if (matchesText && matchesCat) {
                        r.style.display = '';
                        visibleCount++;
                        visibleStockVal += val;
                    } else {
                        r.style.display = 'none';
                    }
                });

                if (totalCount) totalCount.innerText = visibleCount;
                if (totalStockValue) {
                    totalStockValue.innerText = currencySymbol + visibleStockVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            }

            sInput.addEventListener('input', applyFilter);
            cFilter.addEventListener('change', applyFilter);
        });

        // Excel / XLS Export (Sütun yapıları ve HTML tablo olarak saklar)
        function exportExcel() {
            let tableHtml = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            tableHtml += '<head><meta charset="UTF-8"></head><body><table border="1">';
            
            // Tablo Header
            tableHtml += '<tr>';
            document.querySelectorAll('table thead th').forEach(th => {
                tableHtml += '<th style="background-color:#f1f5f9; font-weight:bold;">' + th.innerText + '</th>';
            });
            tableHtml += '</tr>';
            
            // Tablo Satırları (Sadece Gözükenler - Filtre uyumlu)
            let rows = document.querySelectorAll('.stock-row');
            rows.forEach(r => {
                if(r.style.display !== 'none') {
                    tableHtml += '<tr>';
                    r.querySelectorAll('td').forEach(td => {
                        tableHtml += '<td>' + td.innerText.trim() + '</td>';
                    });
                    tableHtml += '</tr>';
                }
            });

            tableHtml += '</table></body></html>';

            // XLS dosyası olarak İndirme
            let excelFile = new Blob([tableHtml], {type: 'application/vnd.ms-excel;charset=utf-8'});
            let link = document.createElement("a");
            link.download = 'Stok_Raporu_' + new Date().toISOString().slice(0,10) + '.xls';
            link.href = window.URL.createObjectURL(excelFile);
            link.style.display = "none";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>

</html>