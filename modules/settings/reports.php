<?php
require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

$pdo = Database::getInstance();
$success = null;

// Rapor Tipleri
$reportTypes = [
    'ledger' => __('statement'),
    'receipt' => __('receipt'),
    'invoice' => __('invoice'),
    'debit_credit' => __('debit_credit_note')
];

$activeType = get('type', 'ledger');

// Varsayılan Şablon Yapısı
$defaultTemplate = [
    'sections' => [
        ['id' => 'logo_header', 'label' => 'Logo & Header', 'visible' => true, 'order' => 0],
        ['id' => 'customer_meta', 'label' => 'Customer & Date Info', 'visible' => true, 'order' => 1],
        ['id' => 'summary_bar', 'label' => 'Summary Balances', 'visible' => true, 'order' => 2],
        ['id' => 'main_table', 'label' => 'Transaction Table', 'visible' => true, 'order' => 3],
        ['id' => 'notes_footer', 'label' => 'Footer & Notes', 'visible' => true, 'order' => 4]
    ],
    'settings' => [
        'logo_size' => 60,
        'show_borders' => true,
        'font_size' => 14
    ]
];

// Ayarları Çek
$settingKey = "report_template_" . $activeType;
$templateJson = $pdo->prepare("SELECT value FROM settings WHERE `key` = :k");
$templateJson->execute([':k' => $settingKey]);
$template = $templateJson->fetchColumn();
$data = $template ? json_decode($template, true) : $defaultTemplate;

// Kaydetme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], post('csrf_token')))
        die(__('error'));

    $postedData = json_decode(post('template_data'), true);
    if ($postedData) {
        $json = json_encode($postedData, JSON_UNESCAPED_UNICODE);
        $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (:k, :v) ON DUPLICATE KEY UPDATE value = :v2");
        $stmt->execute([':k' => $settingKey, ':v' => $json, ':v2' => $json]);
        $success = __('design_saved');
        $data = $postedData;
        logAction('Report Design Updated', $reportTypes[$activeType] . ' template redesigned.');
    }
}

$pageTitle = __('advanced_report_designer');
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
?>

<style>
    .builder-container {
        display: flex;
        gap: 24px;
        min-height: 600px;
    }

    .builder-controls {
        width: 320px;
        flex-shrink: 0;
    }

    .builder-preview {
        flex-grow: 1;
        background: #eaeff2;
        border-radius: var(--radius);
        padding: 30px;
        display: flex;
        justify-content: center;
        overflow-y: auto;
    }

    .paper {
        background: white;
        width: 100%;
        max-width: 800px;
        min-height: 1000px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border-radius: 4px;
        padding: 40px;
    }

    /* Sortable List Styles */
    .section-list {
        border-radius: var(--radius);
        overflow: hidden;
        border: 1px solid var(--card-border);
    }

    .section-item {
        background: var(--card-bg);
        padding: 12px 16px;
        border-bottom: 1px solid var(--card-border);
        cursor: grab;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: 0.2s;
    }

    .section-item:hover {
        background: var(--body-bg);
    }

    .section-item.dragging {
        opacity: 0.5;
        background: var(--accent-soft);
    }

    .preview-section {
        padding: 15px;
        border: 1px dashed transparent;
        margin-bottom: 10px;
        position: relative;
        min-height: 40px;
    }

    .preview-section:hover {
        border-color: var(--accent);
    }

    .preview-section.hidden {
        opacity: 0.2;
        grayscale: 100%;
        border-color: #ddd;
    }

    .tab-link {
        display: block;
        padding: 12px 16px;
        border-radius: 8px;
        color: var(--text-muted);
        text-decoration: none;
        margin-bottom: 4px;
        border: 1px solid transparent;
    }

    .tab-link:hover {
        background: var(--body-bg);
        color: var(--text-primary);
    }

    .tab-link.active {
        background: var(--accent-soft);
        border-color: var(--accent);
        color: var(--accent);
        font-weight: 600;
    }

    #logo-preview {
        object-fit: contain;
    }
</style>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="mb-0"><i class="bi bi-palette2 me-2"></i><?= e($pageTitle) ?></h4>
    <div class="text-muted small"><?= __('preview_instant') ?></div>
</div>

<div class="builder-container">
    <div class="builder-controls">
        <!-- Rapor Seçimi -->
        <div class="panel mb-4">
            <div class="panel-header py-2 px-3">
                <small class="text-uppercase fw-bold opacity-50"><?= __('report_type') ?></small>
            </div>
            <div class="panel-body p-2">
                <?php foreach ($reportTypes as $key => $label): ?>
                    <a href="?type=<?= $key ?>" class="tab-link <?= $activeType === $key ? 'active' : '' ?>">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header py-2 px-3">
                <small class="text-uppercase fw-bold opacity-50"><?= __('order_visibility') ?></small>
            </div>
            <div class="panel-body p-0">
                <div id="section-order" class="section-list">
                    <!-- JS ile dolacak -->
                </div>
            </div>
        </div>

        <div class="panel mt-4">
            <div class="panel-header py-2 px-3">
                <small class="text-uppercase fw-bold opacity-50"><?= __('appearance') ?></small>
            </div>
            <div class="panel-body">
                <div class="mb-3">
                    <label class="form-label-dark small"><?= __('logo_size') ?></label>
                    <input type="range" class="form-range" id="logo-slider" min="30" max="150" step="5"
                        value="<?= (int) $data['settings']['logo_size'] ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label-dark small"><?= __('font_size_pt') ?></label>
                    <input type="number" class="form-control form-control-dark form-control-sm" id="font-size"
                        value="<?= (int) $data['settings']['font_size'] ?>">
                </div>
                <hr class="opacity-10">
                <form id="save-form" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="template_data" id="template-data-input">
                    <button type="submit" class="btn-accent w-100 shadow-sm"><i
                            class="bi bi-save me-2"></i><?= __('save_changes') ?></button>
                    <div class="text-center mt-2">
                        <small class="text-muted"><?= __('preview_instant') ?></small>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- PREVIEW AREA -->
    <div class="builder-preview">
        <div class="paper" id="main-paper">
            <!-- Dinamik Bölümler JS ile Gelecek -->
        </div>
    </div>
</div>

<script>
    const reportData = <?= json_encode($data) ?>;
    const activeType = '<?= $activeType ?>';
    const activeLabel = '<?= $reportTypes[$activeType] ?>';
    const logoUrl = '<?= BASE_URL ?>/storage/images/logo.png';

    const sectionLabels = {
        'logo_header': '<?= __('logo_header_block') ?>',
        'customer_meta': '<?= __('customer_date_info') ?>',
        'summary_bar': '<?= __('summary_balance_frame') ?>',
        'main_table': '<?= __('detailed_transaction_list') ?>',
        'notes_footer': '<?= __('footer_notes_thanks') ?>'
    };

    function renderBuilder() {
        const listContainer = document.getElementById('section-order');
        const paper = document.getElementById('main-paper');

        listContainer.innerHTML = '';
        paper.innerHTML = '';

        // Sıralanmış listeyi işle
        reportData.sections.sort((a, b) => a.order - b.order).forEach((sec, idx) => {
            // Kontrol Listesi
            const item = document.createElement('div');
            item.className = `section-item ${sec.visible ? '' : 'opacity-50'}`;
            item.draggable = true;
            item.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-grip-vertical opacity-50"></i>
                    <span>${sec.label}</span>
                </div>
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" ${sec.visible ? 'checked' : ''} onchange="toggleSection('${sec.id}')">
                </div>
            `;

            // Drag Drop Eventleri
            item.ondragstart = (e) => { e.dataTransfer.setData('idx', idx); item.classList.add('dragging'); };
            item.ondragover = (e) => e.preventDefault();
            item.ondragend = (e) => item.classList.remove('dragging');
            item.ondrop = (e) => {
                const fromIdx = e.dataTransfer.getData('idx');
                moveSection(parseInt(fromIdx), idx);
            };

            listContainer.appendChild(item);

            // Preview Alanı
            if (sec.visible) {
                const psec = document.createElement('div');
                psec.className = 'preview-section';
                psec.innerHTML = getSectionHTML(sec.id);
                paper.appendChild(psec);
            }
        });

        // Veriyi gizli inputa yaz
        document.getElementById('template-data-input').value = JSON.stringify(reportData);
        paper.style.fontSize = reportData.settings.font_size + 'px';
    }

    function getSectionHTML(id) {
        switch (id) {
            case 'logo_header':
                return `
                    <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4" style="color:#333;">
                        <img src="${logoUrl}" style="width:${reportData.settings.logo_size}px" id="logo-preview">
                        <div class="text-end">
                            <h2 style="margin:0; font-weight:800;">${activeLabel}</h2>
                            <div style="color:#666; font-size:12px">BAH Pharmacy Reporting Service</div>
                        </div>
                    </div>
                `;
            case 'customer_meta':
                return `
                    <div class="row mb-4" style="color:#333;">
                        <div class="col-6">
                            <h6 class="fw-bold mb-1"><?= __('customer_info_title') ?></h6>
                            <div>Ahmet Veli (Cari: #12345)</div>
                            <div class="text-muted small">0532 XXX XX XX</div>
                        </div>
                        <div class="col-6 text-end">
                            <h6 class="fw-bold mb-1"><?= __('report_detail_title') ?></h6>
                            <div>No: ${Math.floor(Math.random() * 10000)}</div>
                            <div>Date: ${new Date().toLocaleDateString('tr-TR')}</div>
                        </div>
                    </div>
                `;
            case 'summary_bar':
                return `
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:20px; display:flex; justify-content:space-around; margin-bottom:20px; color:#333;">
                        <div class="text-center">
                            <small class="text-muted d-block"><?= __('previous_balance') ?></small>
                            <span class="fw-bold">1,250.00 TL</span>
                        </div>
                         <div class="text-center border-start ps-4">
                            <small class="text-muted d-block"><?= __('total_transaction') ?></small>
                            <span class="fw-bold">450.00 TL</span>
                        </div>
                         <div class="text-center border-start ps-4">
                            <small class="text-muted d-block"><?= __('current_status') ?></small>
                            <span class="fw-bold text-danger">1,700.00 TL</span>
                        </div>
                    </div>
                `;
            case 'main_table':
                return `
                    <table class="table table-sm" style="color:#333;">
                        <thead class="table-light">
                            <tr><th><?= __('date') ?></th><th><?= __('description') ?></th><th class="text-end"><?= __('total') ?></th></tr>
                        </thead>
                        <tbody>
                            <tr><td>22.04.2026</td><td>Sale Transaction #102</td><td class="text-end">150.00 TL</td></tr>
                            <tr><td>21.04.2026</td><td>Collection (Cash)</td><td class="text-end">-200.00 TL</td></tr>
                             <tr><td>20.04.2026</td><td>Drug Sale</td><td class="text-end">500.00 TL</td></tr>
                        </tbody>
                    </table>
                `;
            case 'notes_footer':
                return `
                    <div class="mt-5 pt-4 border-top text-center text-muted" style="font-size:11px;">
                        <p><?= __('auto_generated_report') ?></p>
                        <p><strong><?= __('thank_you_footer') ?></strong></p>
                    </div>
                `;
            default: return '';
        }
    }

    function toggleSection(id) {
        const sec = reportData.sections.find(s => s.id === id);
        if (sec) sec.visible = !sec.visible;
        renderBuilder();
    }

    function moveSection(fromIdx, toIdx) {
        const sections = reportData.sections;
        const item = sections.splice(fromIdx, 1)[0];
        sections.splice(toIdx, 0, item);

        // Orderları güncelle
        sections.forEach((s, i) => s.order = i);
        renderBuilder();
    }

    document.getElementById('logo-slider').addEventListener('input', (e) => {
        reportData.settings.logo_size = e.target.value;
        renderBuilder();
    });

    document.getElementById('font-size').addEventListener('input', (e) => {
        reportData.settings.font_size = e.target.value;
        renderBuilder();
    });

    // Başlat
    renderBuilder();
</script>

<?php require_once dirname(__DIR__, 2) . '/core/layout_footer.php'; ?>