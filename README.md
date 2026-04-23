# BAH Eczane Yönetim Sistemi

**Sürüm:** 1.0.0  
**Platform:** PHP 7.4+ | MySQL 5.7+ | XAMPP  
**Lisans:** MIT

---

## 📁 Proje Yapısı

```
BAH_pharmacy/
│
├── .htaccess                        ← Dizin listeleme kapatma
│
├── config/
│   ├── config.php                   ← Kurulum sonrası otomatik oluşur
│   └── .htaccess                    ← Dış erişim kapatma
│
├── core/
│   ├── bootstrap.php                ← Her sayfanın önyükleyicisi
│   ├── Database.php                 ← PDO Singleton bağlantı sınıfı
│   ├── helpers.php                  ← e(), logAction(), formatMoney(), UUID...
│   ├── layout_header.php            ← Sidebar + Topbar (tüm sayfalarda)
│   └── layout_footer.php            ← Bootstrap JS + HTML kapanış
│
├── install/
│   ├── install.php                  ← Kurulum sihirbazı (3 adımlı)
│   └── .htaccess                    ← Kurulum sonrası kilit için
│
├── modules/
│   ├── products/
│   │   ├── index.php                ← Ürün listesi + filtre + istatistik
│   │   ├── form.php                 ← Ürün ekle / düzenle
│   │   ├── delete.php               ← Ürün sil (FK koruması)
│   │   ├── stock_update.php         ← Hızlı stok güncelleme
│   │   └── categories.php           ← Kategori CRUD
│   │
│   ├── customers/
│   │   ├── index.php                ← Müşteri listesi + borç filtresi
│   │   ├── form.php                 ← Ekle / düzenle (çift kayıt uyarısı)
│   │   ├── detail.php               ← Detay: satışlar + ödemeler + ödeme alma
│   │   ├── delete.php               ← Müşteri sil
│   │   ├── search_api.php           ← AJAX arama (satış ekranı için)
│   │   └── quick_add_api.php        ← AJAX hızlı müşteri ekleme
│   │
│   ├── sales/
│   │   ├── index.php                ← Satış listesi + filtre
│   │   ├── new.php                  ← Yeni satış (JS sepet + AJAX)
│   │   ├── invoice.php              ← Fatura (print-ready HTML)
│   │   ├── delete.php               ← Satış iptali (stok geri yükleme)
│   │   └── product_search_api.php   ← AJAX ürün arama
│   │
│   ├── stock/
│   │   ├── movements.php            ← Stok hareketleri listesi + filtre
│   │   └── convert.php              ← Stok dönüşümü (kutu → adet)
│   │
│   └── reports/
│       ├── index.php                ← Özet raporlar + Chart.js grafik
│       ├── export.php               ← CSV dışa aktarma (5 tür)
│       ├── logs.php                 ← İşlem logları
│       └── stock_report.php         ← Yazdırılabilir stok raporu
│
├── public/
│   ├── index.php                    ← Dashboard (gerçek verili)
│   ├── storage.php                  ← Güvenli görsel sunucu
│   └── assets/
│       ├── css/app.css              ← Tam koyu tema CSS
│       └── js/app.js                ← Sidebar toggle, upload, form JS
│
└── storage/
    ├── .htaccess                    ← Dış erişim kapatma
    ├── invoices/                    ← (Fatura dosyaları — gelecek genişleme)
    ├── images/                      ← Ürün görselleri
    ├── exports/                     ← CSV çıktıları
    └── logs/                        ← Dosya tabanlı loglar (opsiyonel)
```

---

## 🚀 Kurulum

### Gereksinimler
- XAMPP (Apache + MySQL)
- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri

### Adımlar

1. Bu klasörü `C:\xampp\htdocs\BAH_pharmacy\` dizinine kopyalayın

2. XAMPP'ta **Apache** ve **MySQL** servislerini başlatın

3. Tarayıcıda şu adresi açın:
   ```
   http://localhost/BAH_pharmacy/install/install.php
   ```

4. Kurulum formunu doldurun:
   - **DB Host:** `localhost`
   - **DB Name:** `bah_pharmacy` (otomatik oluşturulur)
   - **DB User:** `root`
   - **DB Password:** *(XAMPP için boş bırakın)*

5. "Kurulumu Başlat" butonuna tıklayın

6. Kurulum tamamlandıktan sonra sisteme giriş yapın:
   ```
   http://localhost/BAH_pharmacy/public/index.php
   ```

---

## 🔒 Güvenlik Önlemleri

| Önlem | Uygulama |
|-------|----------|
| SQL Injection | Tüm sorgularada PDO prepared statements |
| XSS | `htmlspecialchars()` wrapper → `e()` fonksiyonu |
| CSRF | Her formda token doğrulama |
| Path Traversal | storage.php'de `..` filtreleme |
| Tekrar Kurulum | `config.php` varsa install.php çalışmaz |
| Dizin Listeleme | `.htaccess` ile `Options -Indexes` |
| Yetkisiz Erişim | `/storage/` ve `/config/` `.htaccess` ile kilitli |

---

## 📦 Modüller

### 1. Stok Yönetimi
- Ürün ekleme / düzenleme / silme
- Barkod & SKU desteği
- Farmasötik form (tablet, şurup vb.) ve birim (kutu, adet vb.)
- Ürün görseli upload (JPG/PNG/WEBP — maks 3MB)
- Kritik stok uyarısı (liste sayfasında renk vurgusu)
- Hızlı stok güncelleme + otomatik hareket kaydı
- Kategori CRUD

### 2. Müşteri Modülü
- UUID ile benzersiz müşteri kimliği
- Aynı isim çift kayıt uyarısı
- Vade günü takibi
- Müşteri detay: tüm satışlar + ödeme geçmişi
- Ödeme alma (nakit/kart/havale) → borç otomatik güncellenir
- Satış ekranından hızlı müşteri ekleme (AJAX)

### 3. Satış Modülü
- Canlı ürün arama (AJAX — ad, barkod, kategori, stok filtresi)
- JavaScript sepet (adet ve fiyat düzenleme, max stok kontrolü)
- Müşteri AJAX araması & hızlı ekleme
- Yüzde veya sabit iskonto
- Kalan borç → müşteri hesabına otomatik ekleme
- Transaction ile atomik kayıt (satış + kalemler + stok düşme + hareket)
- Satış iptali → stok geri yüklenir, borç düzeltilir

### 4. Fatura
- Print-ready HTML (harici kütüphane yok)
- Tarayıcıdan **Ctrl+P → PDF olarak kaydet**
- Müşteri bilgisi, ürün listesi, iskonto, borç özeti

### 5. Stok Dönüşümü
- Kaynak ürünü parçala → hedef ürüne ekle
- Örnek: 1 "Aspirin Kutu" → 20 "Aspirin Adet"
- Transaction ile güvenli çift taraflı kayıt
- Her iki ürün için `stock_movements` kaydı

### 6. Raporlama
- Dönemsel: Bugün / Bu Hafta / Bu Ay / Bu Yıl / Özel
- Chart.js grafik (ciro + satış adedi)
- Kâr & maliyet hesabı (marj yüzdesiyle)
- En çok satan ürünler, kategori dağılımı, en iyi müşteriler
- CSV export (Excel uyumlu UTF-8 BOM)
- Yazdırılabilir stok raporu

### 7. Log Sistemi
- Her ürün ekleme / güncelleme / silme
- Her satış / iptal
- Her ödeme alma
- Her stok dönüşümü
- Arama + tarih filtresi + CSV export

---

## 🎨 Tasarım

- **Tema:** Koyu (Dark) — glass-morphism aksan
- **Font:** Inter (Google Fonts CDN)
- **CSS Framework:** Bootstrap 5.3 (CDN)
- **İkonlar:** Bootstrap Icons 1.11 (CDN)
- **Grafik:** Chart.js 4.4 (CDN — sadece raporlar sayfasında yüklenir)
- **Responsive:** Tablet & mobil uyumlu sidebar collapse

---

## 🛠️ Geliştirici Notları

### Yeni Modül Eklemek
```php
<?php
require_once dirname(__DIR__, 2) . '/core/bootstrap.php';
$pdo = Database::getInstance();
$pageTitle = 'Yeni Modül';
require_once dirname(__DIR__, 2) . '/core/layout_header.php';
// İçerik buraya
require_once dirname(__DIR__, 2) . '/core/layout_footer.php';
```

### Log Kaydetmek
```php
logAction('İşlem adı', 'Detay bilgisi', 'kullanici');
```

### Flash Mesaj
```php
setFlash('success', 'İşlem tamamlandı.');
setFlash('error',   'Hata oluştu.');
redirect(BASE_URL . '/modules/...');
```

### CSRF Token (Formlarda)
```html
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
```
```php
// Doğrulama
if (!hash_equals($_SESSION['csrf_token'] ?? '', post('csrf_token'))) {
    die('CSRF hatası.');
}
```

---

## 📊 Veritabanı Tabloları

| Tablo | Açıklama |
|-------|----------|
| `categories` | Ürün kategorileri |
| `products` | İlaçlar (barkod, SKU, fiyat, stok) |
| `customers` | Müşteriler (UUID, vade, borç) |
| `sales` | Satış başlıkları (iskonto, ödeme, kalan) |
| `sale_items` | Satış kalemleri (ürün, adet, fiyat) |
| `payments` | Ödeme geçmişi |
| `stock_movements` | Stok hareketleri (giriş/çıkış/dönüşüm/düzeltme) |
| `logs` | Sistem işlem logları |

---

## ⚠️ Sık Karşılaşılan Sorunlar

**config.php bulunamadı hatası**
→ Önce `install/install.php` çalıştırın

**Görsel yüklenemiyor**
→ `storage/images/` klasörünün yazma izni olduğundan emin olun  
→ XAMPP'ta PHP `upload_max_filesize` değerini kontrol edin (min 3M)

**CSV Türkçe karakter sorunu**
→ Excel'de "Veri → Metinden/CSV'den" ile açın, UTF-8 seçin  
→ Veya LibreOffice Calc ile doğrudan açın

**Fatura PDF kalitesi**
→ Tarayıcı print ayarlarında "Arka plan grafikleri yazdır" seçeneğini açın  
→ Chrome veya Edge kullanmanız önerilir

---

*Geliştirici: BAH Eczane Projesi — 2026*
