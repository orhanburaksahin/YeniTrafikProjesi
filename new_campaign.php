<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$currentUserEmail = is_array($_SESSION['user']) ? ($_SESSION['user']['email'] ?? '') : $_SESSION['user'];
$campaigns = json_decode(file_get_contents("config/campaigns.json"), true) ?? [];
$userCampaigns = array_filter($campaigns, fn($c) => $c['user'] === $currentUserEmail);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Kampanya | TrafficGuard</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
<div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-shield-halved"></i></div>
                <div class="logo-text"><h2>TrafficGuard</h2><p>Admin Panel</p></div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Ana Menü</div>
            <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-pie"></i>Dashboard</a>
            <a href="new_campaign.php" class="nav-item active"><i class="fas fa-plus-circle"></i>Yeni Kampanya</a>
            <a href="campaigns.php" class="nav-item"><i class="fas fa-bullhorn"></i>Kampanyalar</a>
            <div class="nav-label">Raporlar</div>
            <a href="reports.php" class="nav-item"><i class="fas fa-chart-line"></i>İstatistikler</a>
            <a href="logs.php" class="nav-item"><i class="fas fa-history"></i>Log Kayıtları</a>
            <div class="nav-label">Sistem</div>
            <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i>Ayarlar</a>
            <a href="support.php" class="nav-item"><i class="fas fa-question-circle"></i>Destek</a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-info">
                    <div class="user-avatar"><?= strtoupper(substr($currentUserEmail, 0, 1)) ?></div>
                    <div class="user-details"><h4><?= htmlspecialchars(explode('@', $currentUserEmail)[0]) ?></h4><p><?= htmlspecialchars($currentUserEmail) ?></p></div>
                </div>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i></a>
                <div><h2>Yeni Kampanya</h2><p>Yeni bir bot koruma kampanyası oluşturun</p></div>
            </div>
            <div class="campaign-badge"><i class="fas fa-plus-circle"></i>Yeni Oluştur</div>
        </div>

        <!-- Form Kartı -->
        <div class="form-card" style="max-width: 900px;">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-plus-circle"></i></div>
                <div class="card-title"><h3>Kampanya Bilgileri</h3><p>Yeni kampanyanız için temel ayarları yapın</p></div>
            </div>

            <form method="post" action="actions/create_campaign.php">
                <input type="hidden" name="user" value="<?= htmlspecialchars($currentUserEmail) ?>">

                <!-- Temel Bilgiler -->
                <div class="info-grid" style="grid-template-columns: repeat(2,1fr); margin-bottom: 30px;">
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-tag"></i></div>
                        <div class="info-content" style="width: 100%;">
                            <label>Kampanya Adı</label>
                            <input type="text" name="name" class="form-control" placeholder="Örn: Black Friday Koruma" required style="width: 100%; padding: 10px; border: 1px solid #eef2f6; border-radius: 10px;">
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-link"></i></div>
                        <div class="info-content" style="width: 100%;">
                            <label>Hedef URL</label>
                            <input type="url" name="target_url" class="form-control" placeholder="https://hedef-site.com" required style="width: 100%; padding: 10px; border: 1px solid #eef2f6; border-radius: 10px;">
                        </div>
                    </div>
                </div>

                <!-- Ülke ve Dil (Sabit) -->
                <div style="display: grid; grid-template-columns: repeat(2,1fr); gap: 20px; margin-bottom: 30px;">
                    <div>
                        <label style="font-size:0.8rem; color:#5f6f8f; font-weight:600; margin-bottom:8px; display:block;">Ülke</label>
                        <div style="background:#f8fafc; border:1px solid #eef2f6; border-radius:10px; padding:12px; color:#1e293b; font-weight:500;">
                            <i class="fas fa-check-circle" style="color:#10b981; margin-right:8px;"></i>Türkiye (TR)
                        </div>
                    </div>
                    <div>
                        <label style="font-size:0.8rem; color:#5f6f8f; font-weight:600; margin-bottom:8px; display:block;">Dil</label>
                        <div style="background:#f8fafc; border:1px solid #eef2f6; border-radius:10px; padding:12px; color:#1e293b; font-weight:500;">
                            <i class="fas fa-check-circle" style="color:#10b981; margin-right:8px;"></i>Türkçe
                        </div>
                    </div>
                </div>

                <!-- Cihaz Filtresi -->
                <div style="margin-bottom: 30px;">
                    <label style="font-size:0.9rem; font-weight:600; color:#0f1829; margin-bottom:8px; display:block;">
                        <i class="fas fa-mobile-screen me-2" style="color:#2563eb;"></i>Cihaz Filtresi
                    </label>
                    <p style="font-size:0.8rem; color:#5f6f8f; margin-bottom:16px;">Hiçbiri seçilmezse tüm cihazlar kabul edilir.</p>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                        <?php
                        $devices = [
                            "desktop" => ["Masaüstü", "fa-desktop"],
                            "mobile" => ["Mobil", "fa-mobile-screen"],
                            "tablet" => ["Tablet", "fa-tablet-screen"],
                            "laptop" => ["Laptop", "fa-laptop"]
                        ];
                        foreach ($devices as $val => $data): ?>
                        <label style="display: flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid #eef2f6; border-radius: 10px; padding: 12px 16px; cursor: pointer;">
                            <input type="checkbox" name="devices[]" value="<?= $val ?>" style="width: 18px; height: 18px; accent-color: #2563eb;">
                            <i class="fas <?= $data[1] ?>" style="color: #2563eb;"></i>
                            <span style="font-weight: 500;"><?= $data[0] ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Bot Tespit Mekanizmaları -->
                <div style="margin-bottom: 30px;">
                    <label style="font-size:0.9rem; font-weight:600; color:#0f1829; margin-bottom:8px; display:block;">
                        <i class="fas fa-shield-halved me-2" style="color:#2563eb;"></i>Bot Tespit Mekanizmaları
                    </label>
                    <p style="font-size:0.8rem; color:#5f6f8f; margin-bottom:16px;">Seçilmezse sistem varsayılan korumayı uygular. Tümü önerilir.</p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
                        <?php
                        $bots = [
                            "headless" => ["Otomasyon Tarayıcı Engeli", "Headless Chrome, Puppeteer, Selenium gibi otomasyon araçlarını tespit eder.", "fa-robot"],
                            "datacenter" => ["Veri Merkezi IP Engeli", "AWS, DigitalOcean, Google Cloud gibi sunuculardan gelen trafiği engeller.", "fa-cloud"],
                            "ua" => ["User-Agent Doğrulama", "Gerçek tarayıcı gibi görünmeyen sahte user-agent'ları engeller.", "fa-user-secret"],
                            "behavior" => ["Davranış Analizi", "İnsan gibi davranmayan (çok hızlı tıklama) ziyaretçileri engeller.", "fa-chart-line"],
                            "js_challenge" => ["JavaScript Challenge", "Tarayıcıya JavaScript testi çözdürerek gerçek kullanıcı olduğunu doğrular.", "fa-code"],
                            "rate_limit" => ["Rate Limiting", "Aynı IP'den gelen aşırı istekleri sınırlar.", "fa-gauge-high"],
                            "fingerprint" => ["Parmak İzi Analizi", "Tarayıcı parmak izi ile aynı botu farklı IP'lerde bile tespit eder.", "fa-fingerprint"]
                        ];
                        foreach ($bots as $val => $data): ?>
                        <div style="background: #f8fafc; border: 1px solid #eef2f6; border-radius: 12px; padding: 16px;">
                            <label style="display: flex; gap: 12px; cursor: pointer;">
                                <input type="checkbox" name="bot_filters[]" value="<?= $val ?>" checked style="width: 18px; height: 18px; margin-top: 3px; accent-color: #2563eb;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                        <i class="fas <?= $data[2] ?>" style="color: #2563eb;"></i>
                                        <strong style="font-size:0.95rem;"><?= $data[0] ?></strong>
                                    </div>
                                    <p style="font-size:0.8rem; color:#5f6f8f; margin:0;"><?= $data[1] ?></p>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Form Butonları -->
                <div style="display: flex; justify-content: flex-end; gap: 16px; margin-top: 30px;">
                    <a href="dashboard.php" class="btn-secondary" style="padding: 12px 32px; text-decoration: none;">İptal</a>
                    <button type="submit" class="btn-primary" style="padding: 12px 32px; border: none;">
                        <i class="fas fa-cloud-upload-alt me-2"></i>Kampanya Oluştur
                    </button>
                </div>
            </form>
        </div>

        <!-- Mevcut Kampanyalar -->
        <div class="form-card" style="max-width: 900px; margin-top: 30px;">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-list"></i></div>
                <div class="card-title"><h3>Mevcut Kampanyalarım</h3><p>Daha önce oluşturduğunuz kampanyalar</p></div>
            </div>

            <?php if(count($userCampaigns) > 0): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid #eef2f6;">
                                <th style="text-align: left; padding: 16px 12px; font-size:0.8rem; color:#5f6f8f;">Kampanya</th>
                                <th style="text-align: left; padding: 16px 12px; font-size:0.8rem; color:#5f6f8f;">Hedef URL</th>
                                <th style="text-align: left; padding: 16px 12px; font-size:0.8rem; color:#5f6f8f;">Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userCampaigns as $c): ?>
                            <tr>
                                <td style="padding: 16px 12px; font-weight: 600;"><?= htmlspecialchars($c['name']) ?></td>
                                <td style="padding: 16px 12px; color: #2563eb;"><?= htmlspecialchars($c['target_url']) ?></td>
                                <td style="padding: 16px 12px;">
                                    <?php if ($c['status'] === 'pending'): ?>
                                        <span class="status-badge" style="background:#fff4e5; color:#b45b0a;">Bekleniyor</span>
                                    <?php elseif ($c['status'] === 'active'): ?>
                                        <span class="status-badge status-active">Aktif</span>
                                    <?php else: ?>
                                        <span class="status-badge status-blocked">Hata</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bullhorn empty-icon"></i>
                    <h4>Henüz kampanyanız yok</h4>
                    <p>Yukarıdaki formu doldurarak ilk kampanyanızı oluşturun.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>