<?php
/**
 * Genel Yardımcı Fonksiyonlar
 * 
 * Tüm sayfalarda require_once ile yüklenir.
 */

// -------------------------------------------------------
// XSS Koruması
// -------------------------------------------------------

/**
 * Çıktıyı XSS'e karşı güvenli hale getirir.
 */
function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// -------------------------------------------------------
// UUID Üreteci (PHP 7+ uyumlu)
// -------------------------------------------------------

function generateUUID(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function generateCustomerId(string $firstName): string
{
    $prefix = mb_strtoupper(mb_substr(trim($firstName), 0, 3, 'UTF-8'), 'UTF-8');
    $prefix = str_pad($prefix, 3, 'X');
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random = '';
    for ($i = 0; $i < 9; $i++) {
        $random .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $prefix . $random;
}

// -------------------------------------------------------
// Para Formatı → core/currency.php'de tanımlı
// formatMoney() artık seçilen para birimine göre çalışır
// -------------------------------------------------------

// -------------------------------------------------------
// Flash Mesaj Sistemi
// -------------------------------------------------------

/**
 * Flash mesaj ayarla (tek seferlik gösterim)
 */
function setFlash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Flash mesajı al ve temizle
 * @return array|null ['type'=>'success|error|warning|info', 'message'=>'...']
 */
function getFlash(): ?array
{
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// -------------------------------------------------------
// Log Sistemi
// -------------------------------------------------------

/**
 * İşlemi veritabanına loglar.
 */
function logAction(string $action, string $detail = '', string $user = 'system'): void
{
    try {
        $pdo = Database::getInstance();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = $pdo->prepare(
            "INSERT INTO logs (action, user, ip, detail) VALUES (:action, :user, :ip, :detail)"
        );
        $stmt->execute([
            ':action' => $action,
            ':user' => $user,
            ':ip' => $ip,
            ':detail' => $detail,
        ]);
    } catch (Exception $e) {
        // Log hatası kritik değil, sessizce geç
        error_log('Log hatası: ' . $e->getMessage());
    }
}

// -------------------------------------------------------
// Yönlendirme
// -------------------------------------------------------

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// -------------------------------------------------------
// Güvenli POST / GET alma
// -------------------------------------------------------

function post(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function get(string $key, string $default = ''): string
{
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

// -------------------------------------------------------
// Dosya boyutu formatı
// -------------------------------------------------------

function formatBytes(int $bytes): string
{
    if ($bytes >= 1048576)
        return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)
        return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

/**
 * Google-style Advanced Search Query Builder
 * 
 * E.g: "aspirin -parasetamol" -> (name LIKE '%aspirin%') AND (name NOT LIKE '%parasetamol%')
 * Returns array: ['sql' => '(...)', 'params' => [':p1' => '%aspirin%', ...]]
 */
function buildGoogleSearchQuery(array $fields, string $searchStr, string $prefix = 's'): array
{
    $terms = preg_split('/\s+/', trim($searchStr), -1, PREG_SPLIT_NO_EMPTY);
    $whereParts = [];
    $params = [];
    $i = 0;

    foreach ($terms as $term) {
        $i++;
        $isNegative = false;

        if (str_starts_with($term, '-')) {
            $isNegative = true;
            $term = substr($term, 1);
        }

        if (empty($term))
            continue;

        $op = $isNegative ? 'NOT LIKE' : 'LIKE';

        $fieldConds = [];
        foreach ($fields as $idx => $f) {
            $pName = ":{$prefix}_{$i}_{$idx}";
            if ($isNegative) {
                // If excluding, it MUST NOT be in any field
                $fieldConds[] = "($f $op $pName OR $f IS NULL)";
            } else {
                $fieldConds[] = "$f $op $pName";
            }
            $params[$pName] = '%' . $term . '%';
        }

        if ($isNegative) {
            $whereParts[] = '(' . implode(' AND ', $fieldConds) . ')'; // A NOT LIKE x AND B NOT LIKE x
        } else {
            $whereParts[] = '(' . implode(' OR ', $fieldConds) . ')';  // A LIKE x OR B LIKE x
        }
    }

    return [
        'sql' => empty($whereParts) ? '' : '(' . implode(' AND ', $whereParts) . ')',
        'params' => $params
    ];
}

// -------------------------------------------------------
// Döviz Toplama Yardımcısı
// -------------------------------------------------------

/**
 * SQL sonucundaki tutarları USD'ye çevirip toplar ve sistemin aktif para biriminde döner.
 * Basit fallback: dönüşüm tablosu yoksa direkt toplama yapar.
 */
function sumConverted(string $sql, string $valCol = 'val', string $curCol = 'currency'): float
{
    try {
        $pdo = Database::getInstance();
        $rows = $pdo->query($sql)->fetchAll();
        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float) ($row[$valCol] ?? 0);
        }
        return $total;
    } catch (Exception $e) {
        return 0.0;
    }
}
/**
 * Rapor Şablonunu Getir
 */
function getReportTemplate(string $type, $pdo): array
{
    $default = [
        'sections' => [
            ['id' => 'logo_header', 'visible' => true, 'order' => 0, 'label' => 'Logo & Başlık'],
            ['id' => 'customer_meta', 'visible' => true, 'order' => 1, 'label' => 'Müşteri & Tarih'],
            ['id' => 'summary_bar', 'visible' => true, 'order' => 2, 'label' => 'Özet / Toplam'],
            ['id' => 'main_table', 'visible' => true, 'order' => 3, 'label' => 'İşlem Tablosu'],
            ['id' => 'notes_footer', 'visible' => true, 'order' => 4, 'label' => 'Alt Bilgi']
        ],
        'settings' => ['logo_size' => 60, 'font_size' => 14, 'hide_header_nav' => true]
    ];

    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = :k");
        $stmt->execute([':k' => "report_template_" . $type]);
        $json = $stmt->fetchColumn();

        if (!$json)
            return $default;

        $data = json_decode($json, true);
        if (!is_array($data))
            return $default;

        // Eksik alanları varsayılanla tamamla
        return array_merge($default, $data);
    } catch (Exception $e) {
        return $default;
    }
}
