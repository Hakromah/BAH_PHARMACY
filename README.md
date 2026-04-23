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

===============================================
GİTHUP KURULUM
=============================================
BAH Pharmacy — New Computer Setup Guide
Prerequisites
XAMPP installed (any recent version)
Git installed
Internet access (for cloning)
Step 1: Clone the Project
Open a terminal / command prompt and run:

bash
cd C:\xampp\htdocs
git clone https://github.com/Hakromah/BAH_PHARMACY.git BAH_PHARMACY
Note: The folder name BAH_PHARMACY must match exactly — it affects the URL path.

Step 2: Start XAMPP Services
Open XAMPP Control Panel
Start Apache
Start MySQL
Step 3: Run the Installer Wizard
Open your browser and go to:

http://localhost/BAH_PHARMACY/install/install.php
The installer will guide you through 5 steps:

Step	What it does
1	Welcome screen
2	PHP & folder permission checks
3	Database connection setup
4	Creates database tables + admin user
5	Done!
Step 3 — Database Settings to Enter:
Field	Value
Host	localhost
Port	3306
Database Name	bah_pharmacy
Username	root
Password	(leave blank — XAMPP default)
The installer will automatically:

Create the database
Create all required tables
Create a default admin user (admin / 1234)
Generate the 
config/config.php
 file
Step 4: Open the Application
After installation completes, go to:

http://localhost/BAH_PHARMACY/public/login.php
Login with:

Username: admin
Password: 1234
⚠️ Change the password immediately after first login via Settings → My Profile.

Step 5: (Optional) Import Real Data from Main Computer
If you want to use the real pharmacy data (customers, products, sales) from the main computer instead of starting fresh:

On the Main Computer (192.168.1.65):
Open http://localhost/BAH_pharmacy/public/index.php
Go to Settings → Backup & Restore
Click "Create New Backup"
Download the generated 
.sql
 backup file
On the New Computer:
Open phpMyAdmin: http://localhost/phpmyadmin
Click on the bah_pharmacy database (left sidebar)
Click the "Import" tab at the top
Choose the 
.sql
 file you downloaded
Click "Go"
Troubleshooting
"Page not found" / 404 error
Make sure Apache is running in XAMPP
Check the URL — it must be exactly http://localhost/BAH_PHARMACY/...
Windows is case-insensitive but the URL must match the folder name
"Connection refused" on database
Make sure MySQL is running in XAMPP Control Panel
Default XAMPP MySQL has user root with no password
Login page shows black screen / labels not visible
This is now fixed — the login page no longer needs internet/CDN
If it still happens, try clearing browser cache (Ctrl+Shift+Del)
Installer says "Already installed"
The 
config/config.php
 file already exists
Delete it and re-run the installer: delete 
C:\xampp\htdocs\BAH_PHARMACY\config\config.php
After git pull — config gets overwritten
config/config.php
 is in 
.gitignore
 — it will never be overwritten by git
Each computer has its own 
config/config.php
 generated by the installer
Accessing from Other Devices on the Same Network
From any device on the same WiFi/LAN:

http://192.168.1.65/BAH_PHARMACY/public/login.php
Replace 192.168.1.65 with the actual IP of the computer running XAMPP.

To find your IP: open Command Prompt → type ipconfig → look for IPv4 Address

Keeping Both Computers in Sync (Git Workflow)
Push changes from Main Computer:
bash
cd C:\xampp\htdocs\BAH_pharmacy
git add .
git commit -m "Your change description"
git push
Pull changes on the Other Computer:
bash
cd C:\xampp\htdocs\BAH_PHARMACY
git pull
⚠️ Important: 
config/config.php
 is excluded from git (
.gitignore
). Each computer keeps its own config file — never share it via git.

Project Structure (Quick Reference)
BAH_PHARMACY/
├── config/
│   └── config.php          ← Auto-generated by installer (NOT in git)
├── core/                   ← Core framework files
├── install/
│   └── install.php         ← Run this on new computers
├── modules/                ← Feature modules (customers, sales, etc.)
├── public/
│   └── login.php           ← Entry point
├── storage/                ← Backups, images, logs (NOT in git)
└── .gitignore              ← Protects sensitive files
