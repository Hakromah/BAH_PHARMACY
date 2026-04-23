<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BAH Eczane — Kurulum Sihirbazı</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a1628 0%, #0f2027 50%, #1a2a3b 100%);
            color: #eee;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Wizard Container */
        .wizard {
            width: 100%;
            max-width: 720px;
            background: rgba(13, 27, 42, 0.85);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6);
            overflow: hidden;
        }

        /* Header */
        .wiz-header {
            background: linear-gradient(135deg, #0d1b2a, #1b3a4b);
            padding: 32px 36px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .wiz-header h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
            letter-spacing: -.5px;
        }

        .wiz-header h1 span {
            font-size: 28px;
            margin-right: 6px;
        }

        .wiz-header p {
            color: rgba(255, 255, 255, 0.45);
            font-size: 13px;
        }

        /* Step indicator */
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 0;
            padding: 24px 36px 0;
            position: relative;
        }

        .step-dot {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }

        .step-dot .dot {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
            border: 2px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.3);
            transition: all .3s;
            position: relative;
            z-index: 2;
        }

        .step-dot.active .dot {
            background: #0ea5e9;
            border-color: #0ea5e9;
            color: #fff;
            box-shadow: 0 0 20px rgba(14, 165, 233, 0.4);
        }

        .step-dot.done .dot {
            background: #22c55e;
            border-color: #22c55e;
            color: #fff;
        }

        .step-dot .label {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.3);
            margin-top: 8px;
            text-align: center;
            width: 80px;
        }

        .step-dot.active .label,
        .step-dot.done .label {
            color: rgba(255, 255, 255, 0.7);
        }

        .step-dot::after {
            content: '';
            position: absolute;
            top: 18px;
            left: calc(50% + 18px);
            right: calc(-50% + 18px);
            height: 2px;
            background: rgba(255, 255, 255, 0.06);
            z-index: 1;
        }

        .step-dot:last-child::after {
            display: none;
        }

        .step-dot.done::after {
            background: #22c55e;
        }

        .step-dot.active::after {
            background: linear-gradient(90deg, #0ea5e9, rgba(255, 255, 255, 0.06));
        }

        /* Body */
        .wiz-body {
            padding: 32px 36px;
        }

        /* Footer */
        .wiz-footer {
            padding: 20px 36px 28px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Buttons */
        .btn-next {
            background: linear-gradient(135deg, #0ea5e9, #38bdf8);
            color: #fff;
            border: none;
            padding: 12px 32px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-next:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(14, 165, 233, 0.35);
        }

        .btn-next:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.06);
            color: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-success {
            background: linear-gradient(135deg, #22c55e, #4ade80);
            color: #111;
            font-weight: 700;
            padding: 14px 36px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.35);
        }

        /* Form */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.55);
            margin-bottom: 6px;
            font-weight: 500;
        }

        .form-group label .req {
            color: #ef9a9a;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #eee;
            font-size: 14px;
            font-family: inherit;
            transition: border-color .2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.12);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* Check table */
        .check-table {
            width: 100%;
            border-collapse: collapse;
        }

        .check-table th {
            text-align: left;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.35);
            padding: 8px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .check-table td {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            font-size: 13px;
        }

        .check-table tr:last-child td {
            border-bottom: none;
        }

        .badge-ok {
            background: rgba(34, 197, 94, 0.12);
            color: #22c55e;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-fail {
            background: rgba(239, 68, 68, 0.12);
            color: #ef4444;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-warn {
            background: rgba(245, 158, 11, 0.12);
            color: #f59e0b;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Alert */
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 13px;
            line-height: 1.6;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.25);
            color: #a5d6a7;
        }

        .alert-info {
            background: rgba(14, 165, 233, 0.1);
            border: 1px solid rgba(14, 165, 233, 0.25);
            color: #93c5fd;
        }

        /* Install log */
        .install-log {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 18px;
            max-height: 300px;
            overflow-y: auto;
        }

        .install-log .log-item {
            padding: 8px 0;
            font-size: 13px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            color: rgba(255, 255, 255, 0.7);
        }

        .install-log .log-item:last-child {
            border-bottom: none;
        }

        /* Welcome features */
        .feature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-top: 20px;
        }

        .feature-item {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .feature-icon {
            font-size: 22px;
            flex-shrink: 0;
        }

        .feature-item strong {
            font-size: 13px;
            display: block;
            margin-bottom: 2px;
        }

        .feature-item span {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.4);
            line-height: 1.5;
        }

        /* Success screen */
        .success-box {
            text-align: center;
            padding: 20px 0;
        }

        .success-icon {
            font-size: 64px;
            animation: bounceIn .6s;
        }

        .success-box h2 {
            font-size: 24px;
            margin: 12px 0 8px;
            color: #22c55e;
        }

        .success-box p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
        }

        .success-links {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 28px;
            flex-wrap: wrap;
        }

        @keyframes bounceIn {
            0% {
                transform: scale(0.3);
                opacity: 0;
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* DB Test result */
        .db-result {
            margin-top: 16px;
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 13px;
        }

        .db-ok {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.25);
            color: #a5d6a7;
        }

        .db-fail {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #fca5a5;
        }

        /* License */
        .license-box {
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 18px;
            max-height: 180px;
            overflow-y: auto;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.45);
            line-height: 1.8;
            margin: 16px 0;
        }

        @media(max-width:600px) {
            .wizard {
                border-radius: 16px;
                margin: 10px;
            }

            .wiz-header,
            .wiz-body,
            .wiz-footer {
                padding: 20px 22px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }

            .step-dot .label {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="wizard">

        <!-- Header -->
        <div class="wiz-header">
            <h1><span>💊</span> BAH Eczane Yönetim Sistemi</h1>
            <p>Kurulum Sihirbazı — v1.0.0</p>
        </div>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <?php
            $stepLabels = ['Hoş Geldin', 'Gereksinimler', 'Veritabanı', 'Kurulum', 'Tamamlandı'];
            for ($i = 1; $i <= 5; $i++):
                $cls = $i < $step ? 'done' : ($i === $step ? 'active' : '');
                ?>
                <div class="step-dot <?= $cls ?>">
                    <div class="dot">
                        <?= $i < $step ? '✓' : $i ?>
                    </div>
                    <div class="label">
                        <?= $stepLabels[$i - 1] ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <!-- ─── ADIM 1: HOŞ GELDİN ─────────────────────── -->
        <?php if ($step === 1): ?>
            <div class="wiz-body">
                <h3 style="font-size:18px;margin-bottom:12px;">Hoş Geldiniz! 🎉</h3>
                <p style="color:rgba(255,255,255,0.55);font-size:14px;line-height:1.7;">
                    Bu sihirbaz, BAH Eczane Yönetim Sistemi'ni bilgisayarınıza kuracaktır.
                    Kurulum birkaç dakika sürer ve aşağıdaki bileşenleri otomatik oluşturur:
                </p>
                <div class="feature-grid">
                    <div class="feature-item">
                        <div class="feature-icon">🗄️</div>
                        <div><strong>Veritabanı</strong><span>MySQL veritabanı ve 8 tablo</span></div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">📁</div>
                        <div><strong>Klasörler</strong><span>Storage, images, invoices, logs</span></div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">⚙️</div>
                        <div><strong>Yapılandırma</strong><span>config.php — bağlantı bilgileri</span></div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">🔒</div>
                        <div><strong>Güvenlik</strong><span>.htaccess dosyaları</span></div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">💊</div>
                        <div><strong>Stok Yönetimi</strong><span>Ürün, kategori, stok hareketleri</span></div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">👥</div>
                        <div><strong>Müşteri & Satış</strong><span>Borç takibi, fatura, ödeme</span></div>
                    </div>
                </div>
                <div class="license-box">
                    <strong>Lisans Sözleşmesi (MIT)</strong><br><br>
                    Bu yazılım MIT Lisansı altında sunulmuştur. Herhangi bir amaçla, kısıtlama olmadan kullanabilir,
                    kopyalayabilir, değiştirebilir, birleştirebilir, yayımlayabilir, dağıtabilir, alt lisanslayabilir
                    ve/veya satabilirsiniz.<br><br>
                    YAZILIM "OLDUĞU GİBİ" SUNULMAKTADIR. AÇIK VEYA ZIMNİ HİÇBİR GARANTİ VERİLMEMEKTEDİR.<br><br>
                    © 2026 BAH Eczane Projesi — Tüm hakları saklıdır.
                </div>
            </div>
            <div class="wiz-footer">
                <div></div>
                <a href="?step=2" class="btn-next">Kabul Et ve Devam ➜</a>
            </div>
        <?php endif; ?>

        <!-- ─── ADIM 2: GEREKSİNİMLER ──────────────────── -->
        <?php if ($step === 2): ?>
            <div class="wiz-body">
                <h3 style="font-size:18px;margin-bottom:6px;">Sistem Gereksinimleri</h3>
                <p style="color:rgba(255,255,255,0.45);font-size:13px;margin-bottom:18px;">
                    Sisteminiz kontrol ediliyor…
                </p>

                <table class="check-table">
                    <thead>
                        <tr>
                            <th>Bileşen</th>
                            <th>Gerekli</th>
                            <th>Mevcut</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requirements as $r): ?>
                            <tr>
                                <td>
                                    <?= $r['name'] ?>
                                </td>
                                <td style="color:rgba(255,255,255,0.4);">
                                    <?= $r['required'] ?>
                                </td>
                                <td>
                                    <?= $r['current'] ?>
                                </td>
                                <td>
                                    <?php if ($r['ok']): ?>
                                        <span class="badge-ok">✓ Geçti</span>
                                    <?php elseif ($r['critical']): ?>
                                        <span class="badge-fail">✗ Kritik</span>
                                    <?php else: ?>
                                        <span class="badge-warn">⚠ Uyarı</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($hasCriticalFail): ?>
                    <div class="alert alert-danger" style="margin-top:16px;">
                        ⛔ <strong>Kritik gereksinimler karşılanmıyor!</strong><br>
                        Kırmızı olarak işaretli sorunları çözüp sayfayı yenileyin.
                    </div>
                <?php else: ?>
                    <div class="alert alert-success" style="margin-top:16px;">
                        ✅ <strong>Tüm gereksinimler karşılanıyor.</strong> Devam edebilirsiniz.
                    </div>
                <?php endif; ?>
            </div>
            <div class="wiz-footer">
                <a href="?step=1" class="btn-back">← Geri</a>
                <?php if (!$hasCriticalFail): ?>
                    <a href="?step=3" class="btn-next">Devam ➜</a>
                <?php else: ?>
                    <button class="btn-next" disabled>Devam ➜</button>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ─── ADIM 3: VERİTABANI ─────────────────────── -->
        <?php if ($step === 3): ?>
            <div class="wiz-body">
                <h3 style="font-size:18px;margin-bottom:6px;">Veritabanı Bağlantısı</h3>
                <p style="color:rgba(255,255,255,0.45);font-size:13px;margin-bottom:18px;">
                    MySQL bağlantı bilgilerini girin. Veritabanı yoksa otomatik oluşturulacaktır.
                </p>

                <?php if (!empty($result['errors'])): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($result['errors'] as $err): ?>
                            <div>⛔
                                <?= htmlspecialchars($err) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($result['success']): ?>
                    <div class="db-result db-ok">
                        ✅ <strong>Bağlantı başarılı!</strong> MySQL
                        <?= htmlspecialchars($result['db_version']) ?><br>
                        <span style="font-size:12px;">"İleri" butonuna tıklayarak kurulumu başlatın.</span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="install.php" id="dbForm">
                    <input type="hidden" name="step" value="3">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Sunucu Adresi <span class="req">*</span></label>
                            <input type="text" name="db_host" class="form-control"
                                value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>">
                        </div>
                        <div class="form-group">
                            <label>Port</label>
                            <input type="text" name="db_port" class="form-control"
                                value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Veritabanı Adı <span class="req">*</span></label>
                        <input type="text" name="db_name" class="form-control"
                            value="<?= htmlspecialchars($_POST['db_name'] ?? 'bah_pharmacy') ?>"
                            placeholder="Yeni veya mevcut veritabanı adı">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Kullanıcı Adı <span class="req">*</span></label>
                            <input type="text" name="db_user" class="form-control"
                                value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>">
                        </div>
                        <div class="form-group">
                            <label>Şifre</label>
                            <input type="password" name="db_pass" class="form-control"
                                value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>"
                                placeholder="XAMPP için boş bırakın">
                        </div>
                    </div>
                    <div style="text-align:right;margin-top:4px;">
                        <button type="submit" class="btn-next"
                            style="background:rgba(14,165,233,0.2);border:1px solid rgba(14,165,233,0.3);">
                            🔍 Bağlantıyı Test Et
                        </button>
                    </div>
                </form>
            </div>
            <div class="wiz-footer">
                <a href="?step=2" class="btn-back">← Geri</a>
                <?php if ($result['success']): ?>
                    <form method="POST" action="install.php" style="margin:0;">
                        <input type="hidden" name="step" value="4">
                        <button type="submit" class="btn-next" id="installBtn">Kurulumu Başlat ➜</button>
                    </form>
                <?php else: ?>
                    <button class="btn-next" disabled>Önce Bağlantıyı Test Edin</button>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ─── ADIM 4: KURULUM ────────────────────────── -->
        <?php if ($step === 4): ?>
            <div class="wiz-body">
                <h3 style="font-size:18px;margin-bottom:6px;">Kurulum İşlemi</h3>

                <?php if (!empty($result['errors'])): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($result['errors'] as $err): ?>
                            <div>⛔
                                <?= htmlspecialchars($err) ?>
                            </div>
                        <?php endforeach; ?>
                        <br><a href="?step=3" style="color:#fca5a5;">← 3. adıma dön</a>
                    </div>
                <?php elseif ($result['success']): ?>
                    <div class="alert alert-success" style="margin-bottom:18px;">
                        ✅ <strong>Kurulum başarıyla tamamlandı!</strong>
                    </div>
                    <div class="install-log">
                        <?php foreach ($result['steps'] ?? [] as $logItem): ?>
                            <div class="log-item">
                                <?= $logItem ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="wiz-footer">
                <?php if ($result['success']): ?>
                    <div></div>
                    <a href="?step=5" class="btn-next">Sonuç ➜</a>
                <?php else: ?>
                    <a href="?step=3" class="btn-back">← Geri</a>
                    <button class="btn-next" disabled>Hata Var</button>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ─── ADIM 5: TAMAMLANDI ─────────────────────── -->
        <?php if ($step === 5): ?>
            <div class="wiz-body">
                <div class="success-box">
                    <div class="success-icon">🎉</div>
                    <h2>Kurulum Tamamlandı!</h2>
                    <p>BAH Eczane Yönetim Sistemi başarıyla kuruldu ve kullanıma hazır.</p>

                    <div class="alert alert-info" style="text-align:left;margin-top:24px;">
                        <strong>⚠️ Güvenlik Notu:</strong><br>
                        Güvenlik için <code
                            style="background:rgba(0,0,0,.2);padding:2px 6px;border-radius:4px;">install/</code>
                        klasörünü silin veya <code>install/.htaccess</code> dosyasını etkinleştirin.
                    </div>

                    <div class="success-links">
                        <a href="../public/index.php" class="btn-success">🚀 Sisteme Giriş Yap</a>
                        <a href="../modules/products/form.php" class="btn-back" style="color:#eee;text-decoration:none;">📦
                            İlk Ürünü Ekle</a>
                        <a href="../modules/customers/form.php" class="btn-back" style="color:#eee;text-decoration:none;">👤
                            İlk Müşteri Ekle</a>
                    </div>
                </div>
            </div>
            <div class="wiz-footer" style="justify-content:center;">
                <p style="font-size:12px;color:rgba(255,255,255,0.3);">
                    BAH Eczane v1.0.0 —
                    <?= date('Y') ?>
                </p>
            </div>
        <?php endif; ?>

    </div>

    <script>
        // Install butonuna çift tıklama engeli
        const btn = document.getElementById('installBtn');
        if (btn) {
            btn.closest('form').addEventListener('submit', function () {
                btn.disabled = true;
                btn.innerHTML = '⏳ Kuruluyor...';
            });
        }
    </script>
</body>

</html>