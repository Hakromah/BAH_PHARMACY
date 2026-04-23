<?php
/**
 * Para Birimi & Döviz Kuru Sistemi
 *
 * - DB'den para birimleri okunur, yoksa varsayılanlar kullanılır
 * - Günlük kur girişi (USD bazlı)
 * - Kur girilmediyse geriye dönük en yakın kur kullanılır
 * - Kur ileriye dönük çalışır, geriye dönük işlemez
 */

// ── Cookie: Aktif para birimi ─────────────────────────
function getCurrentCurrency(): string
{
    return $_COOKIE['bah_currency'] ?? 'USD';
}

function setCurrency(string $cur): void
{
    setcookie('bah_currency', $cur, time() + 86400 * 365, '/');
    $_COOKIE['bah_currency'] = $cur;
}

// ── Varsayılan para birimleri (DB yokken fallback) ────
function getDefaultCurrencies(): array
{
    return [
        'USD' => ['symbol' => '$', 'name' => 'US Dollar', 'code' => 'USD', 'pos' => 'before', 'dec' => '.', 'thou' => ','],
        'EUR' => ['symbol' => '€', 'name' => 'Euro', 'code' => 'EUR', 'pos' => 'before', 'dec' => ',', 'thou' => '.'],
        'TRY' => ['symbol' => '₺', 'name' => 'Türk Lirası', 'code' => 'TRY', 'pos' => 'after', 'dec' => ',', 'thou' => '.'],
        'GBP' => ['symbol' => '£', 'name' => 'British Pound', 'code' => 'GBP', 'pos' => 'before', 'dec' => '.', 'thou' => ','],
        'XOF' => ['symbol' => 'CFA', 'name' => 'Franc CFA (BCEAO)', 'code' => 'XOF', 'pos' => 'after', 'dec' => ',', 'thou' => '.'],
    ];
}

// ── DB'den para birimleri (tablo varsa) ───────────────
function getCurrencyList(): array
{
    try {
        $pdo = Database::getInstance();
        // Tablo var mı?
        $check = $pdo->query("SHOW TABLES LIKE 'currencies'");
        if ($check->rowCount() === 0) {
            return getDefaultCurrencies();
        }
        $rows = $pdo->query("SELECT * FROM currencies WHERE is_active = 1 ORDER BY code")->fetchAll();
        if (empty($rows)) {
            return getDefaultCurrencies();
        }
        $list = [];
        foreach ($rows as $r) {
            $list[$r['code']] = [
                'symbol' => $r['symbol'],
                'name' => $r['name'],
                'code' => $r['code'],
                'pos' => $r['position'],
                'dec' => $r['decimal_sep'],
                'thou' => $r['thousand_sep'],
            ];
        }
        return $list;
    } catch (Exception $e) {
        return getDefaultCurrencies();
    }
}

function getCurrencyInfo(?string $code = null): array
{
    $code = $code ?? getCurrentCurrency();
    $list = getCurrencyList();
    return $list[$code] ?? $list['USD'] ?? getDefaultCurrencies()['USD'];
}

function getCurrencySymbol(?string $code = null): string
{
    return getCurrencyInfo($code)['symbol'];
}

/**
 * Para formatla — seçilen birime göre
 */
function formatMoney(float $amount, ?string $code = null): string
{
    $c = getCurrencyInfo($code);
    $formatted = number_format(abs($amount), 2, $c['dec'], $c['thou']);
    $sign = $amount < 0 ? '-' : '';
    if ($c['pos'] === 'before') {
        return $sign . $c['symbol'] . $formatted;
    }
    return $sign . $formatted . ' ' . $c['symbol'];
}

// ═══════════════════════════════════════════════════════
//  DÖVİZ KURU FONKSİYONLARI
// ═══════════════════════════════════════════════════════

/**
 * Belirli bir tarih için döviz kurunu getir (USD karşılığı)
 * Kural: O tarihe en yakın geçmiş tarihli koru kullanır
 * @param string $currencyCode Para birimi kodu
 * @param string|null $date Tarih (Y-m-d), null = bugün
 * @return float|null Kur (1 USD = ? birim), null = kur yok
 */
function getExchangeRate(string $currencyCode, ?string $date = null): ?float
{
    if ($currencyCode === 'USD')
        return 1.0;

    $date = $date ?? date('Y-m-d');

    try {
        $pdo = Database::getInstance();
        // Tablo var mı?
        $check = $pdo->query("SHOW TABLES LIKE 'exchange_rates'");
        if ($check->rowCount() === 0)
            return null;

        // O tarihe eşit veya daha eski en yakın kuru bul
        $stmt = $pdo->prepare("
            SELECT rate_to_usd FROM exchange_rates
            WHERE currency_code = :code AND effective_date <= :d
            ORDER BY effective_date DESC
            LIMIT 1
        ");
        $stmt->execute([':code' => $currencyCode, ':d' => $date]);
        $rate = $stmt->fetchColumn();
        return $rate !== false ? (float) $rate : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Tutarı USD'ye çevir
 */
function convertToUSD(float $amount, string $fromCode, ?string $date = null): ?float
{
    if ($fromCode === 'USD')
        return $amount;
    $rate = getExchangeRate($fromCode, $date);
    if ($rate === null || $rate <= 0)
        return null;
    return $amount / $rate;
}

/**
 * USD'den hedef para birimine çevir
 */
function convertFromUSD(float $amountUSD, string $toCode, ?string $date = null): ?float
{
    if ($toCode === 'USD')
        return $amountUSD;
    $rate = getExchangeRate($toCode, $date);
    if ($rate === null)
        return null;
    return $amountUSD * $rate;
}

/**
 * İki para birimi arasında çeviri (USD üzerinden)
 */
function convertCurrency(float $amount, string $from, string $to, ?string $date = null): ?float
{
    if ($from === $to)
        return $amount;
    $usd = convertToUSD($amount, $from, $date);
    if ($usd === null)
        return null;
    return convertFromUSD($usd, $to, $date);
}

/**
 * Bir para birimi için tüm kur geçmişini getir
 */
function getRateHistory(string $currencyCode, int $limit = 30): array
{
    try {
        $pdo = Database::getInstance();
        $check = $pdo->query("SHOW TABLES LIKE 'exchange_rates'");
        if ($check->rowCount() === 0)
            return [];

        $stmt = $pdo->prepare("
            SELECT * FROM exchange_rates
            WHERE currency_code = :code
            ORDER BY effective_date DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':code', $currencyCode, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}
