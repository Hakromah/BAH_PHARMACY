<?php
require_once __DIR__ . '/core/bootstrap.php';
$pdo = Database::getInstance();
$updates = [
    'txt_hizli_tahsilat' => 'Fast Payment',
    'txt_musteri_secin' => 'Select Customer',
    'txt__musteri_ara_sec' => 'Search Customer Select',
    'txt_guncel_cari_durum' => 'Current Account Status',
    'txt_tahsilat_avans_tutari' => 'Collection Advance Amount',
    'txt_orn_50000' => 'Ex: 50000',
    'txt_odeme_yontemi' => 'Payment Method',
    'txt_nakit' => 'Cash',
    'txt_kredibanka_karti' => 'Credit Card',
    'txt_havaleeft' => 'Bank Transfer',
    'txt_diger' => 'Other',
    'txt_aciklama_not' => 'Note / Explanation',
    'txt_orn_ekim_ayi_avansi_eski_borc' => 'Ex: October advance, old debt',
    'txt_tahsilati_tamamla' => 'Complete The Payment'
];
foreach ($updates as $key => $val) {
    // Delete first to ensure we insert fresh if update fails for some reason
    $pdo->prepare("DELETE FROM translations WHERE lang_code = 'en' AND string_key = :k")->execute([':k' => $key]);
    $pdo->prepare("INSERT INTO translations (lang_code, string_key, string_value) VALUES ('en', :k, :v)")->execute([':k' => $key, ':v' => $val]);
}
echo "Database updated for English translations.";
