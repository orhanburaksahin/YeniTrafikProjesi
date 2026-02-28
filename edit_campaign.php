<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

require_once "lib/worker_generator.php";
require_once "lib/worker_deployer.php";

$currentUserEmail = is_array($_SESSION['user']) ? ($_SESSION['user']['email'] ?? '') : $_SESSION['user'];

$GLOBAL_SECRET = "cok_guclu_uzun_random_bir_secret_123XYZ";

$campaignId = $_GET['id'] ?? '';
if (!$campaignId) die("Kampanya ID belirtilmedi.");

$campaigns = json_decode(file_get_contents("config/campaigns.json"), true) ?? [];
$campaignIndex = null;
$campaign = null;

foreach ($campaigns as $i => $c) {
    $userEmail = is_array($c['user']) ? ($c['user']['email'] ?? '') : $c['user'];
    if ($c['id'] === $campaignId && $userEmail === $currentUserEmail) {
        $campaignIndex = $i;
        $campaign = $c;
        break;
    }
}

if (!$campaign) die("Kampanya bulunamadı veya yetkiniz yok.");

// TÜM FİLTRELER VE AÇIKLAMALARI
$botFiltersWithDescriptions = [
    // === TEMEL BOT TESPİT FİLTRELERİ ===
    'js_challenge' => [
        'name' => 'JavaScript Challenge',
        'description' => 'Ziyaretçiye basit bir JavaScript testi çözerek bot olmadığını kanıtlamasını ister. Gerçek tarayıcılar JavaScript çalıştırabilir, botlar çalıştıramaz.',
        'recommended' => true,
        'level' => 'Temel',
        'icon' => 'code'
    ],
    'headless' => [
        'name' => 'Headless Browser Tespiti',
        'description' => 'Headless Chrome, PhantomJS, Puppeteer, Selenium gibi ekransız çalışan otomasyon araçlarını tespit eder.',
        'recommended' => true,
        'level' => 'Temel',
        'icon' => 'chrome'
    ],
    'datacenter' => [
        'name' => 'Veri Merkezi IP Tespiti',
        'description' => 'AWS, DigitalOcean, Google Cloud, Azure gibi veri merkezlerinden gelen IP adreslerini tespit eder.',
        'recommended' => true,
        'level' => 'Temel',
        'icon' => 'cloud'
    ],
    'behavior' => [
        'name' => 'Davranış Analizi',
        'description' => 'Kullanıcının fare hareketleri, tıklama patternleri, sayfada kalma süresi gibi davranışlarını analiz eder.',
        'recommended' => true,
        'level' => 'Orta',
        'icon' => 'chart-line'
    ],
    'automation_flags' => [
        'name' => 'Otomasyon Araç İzleri',
        'description' => 'Selenium, Puppeteer, Playwright gibi otomasyon araçlarının bıraktığı izleri kontrol eder.',
        'recommended' => true,
        'level' => 'Orta',
        'icon' => 'robot'
    ],
    'suspicious_referer' => [
        'name' => 'Şüpheli Yönlendirme Tespiti',
        'description' => 'Ziyaretçinin hangi siteden geldiğini kontrol eder. Bilinmeyen sitelerden gelen trafiği işaretler.',
        'recommended' => true,
        'level' => 'Orta',
        'icon' => 'link'
    ],
    'ip_entropy' => [
        'name' => 'IP Entropy Analizi',
        'description' => 'Aynı IP bloğundan gelen anormal trafik patternlerini analiz eder.',
        'recommended' => true,
        'level' => 'İleri',
        'icon' => 'network-wired'
    ],
    'rate_limit' => [
        'name' => 'Rate Limiting',
        'description' => 'Kısa sürede aşırı istek atan IP adreslerini sınırlar.',
        'recommended' => true,
        'level' => 'Temel',
        'icon' => 'gauge-high'
    ],
    'header_consistency' => [
        'name' => 'Header Tutarlılığı',
        'description' => 'HTTP header bilgilerinin tutarlılığını kontrol eder.',
        'recommended' => true,
        'level' => 'Orta',
        'icon' => 'list-check'
    ],
    'tls_fingerprint' => [
        'name' => 'TLS Fingerprint',
        'description' => 'TLS bağlantı parmak izi ile bot kütüphanelerini tespit eder.',
        'recommended' => true,
        'level' => 'İleri',
        'icon' => 'shield'
    ],
    'entropy' => [
        'name' => 'Entropy Analizi',
        'description' => 'Header ve User-Agent bilgilerinin rastgelelik seviyesini analiz eder.',
        'recommended' => false,
        'level' => 'İleri',
        'icon' => 'shuffle'
    ],
    'navigation_flow' => [
        'name' => 'Navigasyon Akışı Kontrolü',
        'description' => 'Ziyaretçinin siteye nasıl geldiğini kontrol eder.',
        'recommended' => false,
        'level' => 'Orta',
        'icon' => 'arrow-right-arrow-left'
    ],
    
    // === HEADER CONSISTENCY ALT FİLTRELERİ ===
    'tls_ua_check' => [
        'name' => 'TLS/User-Agent Uyumu',
        'description' => 'Cloudflare TLS fingerprint ile User-Agent bilgisinin uyumunu kontrol eder.',
        'recommended' => true,
        'level' => 'İleri',
        'icon' => 'fingerprint'
    ],
    'lang_country_check' => [
        'name' => 'Dil/Ülke Tutarlılığı',
        'description' => 'Tarayıcı dil tercihi ile IP adresinin ülkesi arasındaki tutarlılığı kontrol eder.',
        'recommended' => true,
        'level' => 'Orta',
        'icon' => 'language'
    ],
    'encoding_check' => [
        'name' => 'Accept-Encoding Kontrolü',
        'description' => 'Tarayıcının kabul ettiği sıkıştırma yöntemlerini kontrol eder.',
        'recommended' => true,
        'level' => 'Temel',
        'icon' => 'file-zipper'
    ],
    'referrer_check' => [
        'name' => 'Referrer Doğrulama',
        'description' => 'Yönlendirme yapan sayfanın güvenilirliğini kontrol eder.',
        'recommended' => true,
        'level' => 'Orta',
        'icon' => 'arrow-up-from-bracket'
    ],
    'header_integrity' => [
        'name' => 'Header Bütünlüğü',
        'description' => 'Tüm HTTP header bilgilerinin bütünlüğünü kontrol eder.',
        'recommended' => true,
        'level' => 'Orta',
        'icon' => 'rectangle-list'
    ],
    
    // === LEVEL 2 AGGRESSIVE FİLTRELER ===
    'navigation_token' => [
        'name' => 'Navigation Token Doğrulama',
        'description' => 'Her sayfa yüklemesinde benzersiz bir token oluşturur ve kontrol eder.',
        'recommended' => true,
        'level' => 'Aggressive',
        'icon' => 'key'
    ],
    'behavioral_delay' => [
        'name' => 'Davranış Gecikme Kontrolü',
        'description' => 'İki istek arasındaki süreyi kontrol eder.',
        'recommended' => true,
        'level' => 'Aggressive',
        'icon' => 'clock'
    ],
    'advanced_js' => [
        'name' => 'Gelişmiş JavaScript Challenge',
        'description' => 'Karmaşık JavaScript testleri ile botları tespit eder.',
        'recommended' => false,
        'level' => 'Aggressive',
        'icon' => 'cubes'
    ],
    'fingerprint_v2' => [
        'name' => 'Gelişmiş Parmak İzi Analizi',
        'description' => 'Tarayıcı, ekran, font gibi özelliklerden oluşan parmak izi ile botları tespit eder.',
        'recommended' => false,
        'level' => 'Aggressive',
        'icon' => 'print'
    ]
];

// Filtre isimlerini array olarak al
$allBotFilters = array_keys($botFiltersWithDescriptions);

// Önerilen filtreler
$recommendedFilters = [];
foreach ($botFiltersWithDescriptions as $key => $filter) {
    if ($filter['recommended']) {
        $recommendedFilters[] = $key;
    }
}

// Level 2 filtreler
$level2Filters = [];
foreach ($botFiltersWithDescriptions as $key => $filter) {
    if ($filter['level'] === 'Aggressive') {
        $level2Filters[$key] = $filter['name'];
    }
}

$allDevices = ['desktop', 'mobile', 'tablet'];

// POST işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updated = $campaign;
    $updated['bot_filters'] = $_POST['bot_filters'] ?? [];
    $updated['devices'] = $_POST['devices'] ?? [];
    $updated['settings'] = $_POST['settings'] ?? [];

    $campaigns[$campaignIndex] = $updated;
    file_put_contents("config/campaigns.json", json_encode($campaigns, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $workerCode = generateWorkerScript(
        $campaign['id'],
        $campaign['target_url'],
        $updated['bot_filters'],
        $updated['devices'],
        $GLOBAL_SECRET
    );

    $deployer = new WorkerDeployer();
    $deployer->deploy($campaign['id'], $workerCode);

    header("Location: campaign_detail.php?id={$campaignId}");
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($campaign['name']) ?> | Kampanya Düzenle</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            color: #1e293b;
        }

        /* Ana Layout */
        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 280px;
            background: #0f1829;
            color: #fff;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 30px 24px;
            border-bottom: 1px solid #1e2a3a;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: #2563eb;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #fff;
        }

        .logo-text h2 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 4px;
            color: #fff;
        }

        .logo-text p {
            font-size: 0.7rem;
            color: #8b9bb5;
            letter-spacing: 0.3px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 24px;
        }

        .nav-label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #5a6f8c;
            margin-bottom: 16px;
            margin-top: 24px;
        }

        .nav-label:first-of-type {
            margin-top: 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #a0b3d9;
            border-radius: 10px;
            margin-bottom: 4px;
            transition: all 0.2s;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .nav-item i {
            width: 24px;
            font-size: 1.1rem;
            margin-right: 12px;
            color: #5a6f8c;
        }

        .nav-item:hover {
            background: #1a2639;
            color: #fff;
        }

        .nav-item:hover i {
            color: #2563eb;
        }

        .nav-item.active {
            background: #2563eb;
            color: #fff;
        }

        .nav-item.active i {
            color: #fff;
        }

        .sidebar-footer {
            padding: 24px;
            border-top: 1px solid #1e2a3a;
        }

        .user-card {
            background: #1a2639;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            background: #2563eb;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
            color: #fff;
        }

        .user-details h4 {
            font-size: 0.9rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 4px;
        }

        .user-details p {
            font-size: 0.7rem;
            color: #8b9bb5;
        }

        .logout-btn {
            display: block;
            background: transparent;
            border: 1px solid #2a3a52;
            color: #ffb4b4;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: #ef4444;
            border-color: #ef4444;
            color: #fff;
        }

        /* ===== MAIN CONTENT ===== */
        .main {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }

        /* Üst Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: #fff;
            padding: 20px 30px;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .back-link {
            width: 40px;
            height: 40px;
            background: #f1f5f9;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1e293b;
            text-decoration: none;
            transition: all 0.2s;
        }

        .back-link:hover {
            background: #2563eb;
            color: #fff;
        }

        .page-title h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f1829;
            margin-bottom: 4px;
        }

        .page-title p {
            color: #5f6f8f;
            font-size: 0.9rem;
        }

        .campaign-badge {
            background: #eef2ff;
            color: #2563eb;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Kampanya Bilgi Kartı */
        .info-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
            border: 1px solid #eef2f6;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-icon {
            width: 48px;
            height: 48px;
            background: #f1f5f9;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #2563eb;
        }

        .info-content label {
            font-size: 0.75rem;
            color: #5f6f8f;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
            display: block;
        }

        .info-content div {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f1829;
        }

        /* Form Kartları */
        .form-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #eef2f6;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eef2f6;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            background: #eef2ff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: #2563eb;
        }

        .card-title h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f1829;
            margin-bottom: 4px;
        }

        .card-title p {
            font-size: 0.8rem;
            color: #5f6f8f;
        }

        /* Filtre Grid */
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
        }

        .filter-item {
            background: #f8fafc;
            border: 1px solid #eef2f6;
            border-radius: 12px;
            padding: 16px;
            transition: all 0.2s;
        }

        .filter-item:hover {
            border-color: #2563eb;
            background: #fff;
        }

        .filter-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .filter-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-top: 2px;
            accent-color: #2563eb;
            cursor: pointer;
        }

        .filter-content {
            flex: 1;
        }

        .filter-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .filter-header label {
            font-weight: 600;
            font-size: 0.95rem;
            color: #0f1829;
            cursor: pointer;
        }

        .filter-badge {
            padding: 2px 8px;
            border-radius: 30px;
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-temel { background: #e0f2fe; color: #0369a1; }
        .badge-orta { background: #fff4e5; color: #b45b0a; }
        .badge-ileri { background: #ffe8e8; color: #c24141; }
        .badge-aggressive { background: #f0e7ff; color: #6f42c1; }

        .recommended-tag {
            background: #10b981;
            color: #fff;
            padding: 2px 8px;
            border-radius: 30px;
            font-size: 0.6rem;
            font-weight: 700;
        }

        .filter-desc {
            font-size: 0.8rem;
            color: #5f6f8f;
            line-height: 1.5;
        }

        /* Level 2 Kartı */
        .level2-card {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 16px;
            padding: 20px;
            margin-top: 16px;
        }

        .level2-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .level2-icon {
            width: 40px;
            height: 40px;
            background: #fed7aa;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #b45b0a;
        }

        .level2-header h4 {
            font-size: 1rem;
            font-weight: 700;
            color: #b45b0a;
        }

        /* Cihaz Grid */
        .devices-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .device-item {
            background: #f8fafc;
            border: 1px solid #eef2f6;
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .device-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #2563eb;
        }

        .device-item label {
            font-weight: 600;
            color: #0f1829;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .device-item label i {
            color: #2563eb;
            font-size: 1.1rem;
        }

        /* Ayarlar Grid */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .setting-item {
            background: #f8fafc;
            border: 1px solid #eef2f6;
            border-radius: 12px;
            padding: 16px;
        }

        .setting-switch {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .setting-switch input[type="checkbox"] {
            width: 44px;
            height: 24px;
            accent-color: #2563eb;
            cursor: pointer;
        }

        .setting-switch label {
            font-weight: 600;
            color: #0f1829;
        }

        .setting-desc {
            font-size: 0.8rem;
            color: #5f6f8f;
            margin-left: 56px;
        }

        /* Form Butonları */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 16px;
            margin-top: 30px;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.3);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #1e293b;
            border: 1px solid #e2e8f0;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #0f1829;
            color: #fff;
            padding: 14px 28px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 20px 40px -10px rgba(15, 24, 41, 0.5);
            z-index: 9999;
            animation: slideIn 0.3s ease;
        }

        .toast i {
            color: #10b981;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .devices-grid, .settings-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <div class="logo-text">
                        <h2>TrafficGuard</h2>
                        <p>Admin Panel</p>
                    </div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-label">Ana Menü</div>
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
                <a href="new_campaign.php" class="nav-item">
                    <i class="fas fa-plus-circle"></i>
                    <span>Yeni Kampanya</span>
                </a>
                <a href="campaigns.php" class="nav-item active">
                    <i class="fas fa-bullhorn"></i>
                    <span>Kampanyalar</span>
                </a>

                <div class="nav-label">Raporlar</div>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>İstatistikler</span>
                </a>
                <a href="logs.php" class="nav-item">
                    <i class="fas fa-history"></i>
                    <span>Log Kayıtları</span>
                </a>

                <div class="nav-label">Sistem</div>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Ayarlar</span>
                </a>
                <a href="support.php" class="nav-item">
                    <i class="fas fa-question-circle"></i>
                    <span>Destek</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-card">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?= strtoupper(substr($currentUserEmail, 0, 1)) ?>
                        </div>
                        <div class="user-details">
                            <h4><?= htmlspecialchars(explode('@', $currentUserEmail)[0]) ?></h4>
                            <p><?= htmlspecialchars($currentUserEmail) ?></p>
                        </div>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt me-2"></i> Çıkış Yap
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="page-title">
                    <a href="campaign_detail.php?id=<?= $campaign['id'] ?>" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h2>Kampanya Düzenle</h2>
                        <p><?= htmlspecialchars($campaign['name']) ?></p>
                    </div>
                </div>
                <div class="campaign-badge">
                    <i class="fas fa-shield"></i>
                    <span>ID: <?= htmlspecialchars($campaignId) ?></span>
                </div>
            </div>

            <!-- Kampanya Bilgi Kartı -->
            <div class="info-card">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-link"></i>
                        </div>
                        <div class="info-content">
                            <label>Hedef URL</label>
                            <div><?= htmlspecialchars($campaign['target_url'] ?? '') ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="info-content">
                            <label>Oluşturulma</label>
                            <div>
                                <?php 
                                if (isset($campaign['created_at'])) {
                                    if (is_numeric($campaign['created_at'])) {
                                        echo date('d.m.Y H:i', $campaign['created_at']);
                                    } else {
                                        echo htmlspecialchars($campaign['created_at']);
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="info-content">
                            <label>Worker Domain</label>
                            <div><?= htmlspecialchars($campaign['id'] ?? '') ?>.eragonn.workers.dev</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form method="POST">
                <!-- Bot Filtreleri -->
                <div class="form-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-shield-halved"></i>
                        </div>
                        <div class="card-title">
                            <h3>Bot Tespit Filtreleri</h3>
                            <p>Hangi bot tespit yöntemlerinin aktif olacağını seçin</p>
                        </div>
                    </div>

                    <?php 
                    $levelOrder = ['Temel', 'Orta', 'İleri'];
                    foreach ($levelOrder as $level): 
                        $levelFilters = array_filter($botFiltersWithDescriptions, function($f) use ($level) {
                            return $f['level'] === $level;
                        });
                        if (empty($levelFilters)) continue;
                    ?>
                        <div style="margin-bottom: 24px;">
                            <h4 style="margin-bottom: 16px; font-size: 0.9rem; color: #5f6f8f;"><?= $level ?> Seviye</h4>
                            <div class="filters-grid">
                                <?php foreach ($levelFilters as $key => $filter): 
                                    $checked = in_array($key, $campaign['bot_filters'] ?? []) ? 'checked' : '';
                                ?>
                                    <div class="filter-item">
                                        <div class="filter-checkbox">
                                            <input type="checkbox" name="bot_filters[]" value="<?= $key ?>" id="filter_<?= $key ?>" <?= $checked ?>>
                                            <div class="filter-content">
                                                <div class="filter-header">
                                                    <label for="filter_<?= $key ?>"><?= $filter['name'] ?></label>
                                                    <span class="filter-badge badge-<?= strtolower($filter['level']) ?>"><?= $filter['level'] ?></span>
                                                    <?php if(in_array($key, $recommendedFilters)): ?>
                                                        <span class="recommended-tag">Önerilen</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="filter-desc">
                                                    <?= htmlspecialchars($filter['description']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Level 2 Filtreler -->
                    <div class="level2-card">
                        <div class="level2-header">
                            <div class="level2-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h4>Level 2 – Aggressive Koruma</h4>
                        </div>
                        <div class="filters-grid">
                            <?php foreach ($level2Filters as $key => $name): 
                                $filter = $botFiltersWithDescriptions[$key] ?? null;
                                if (!$filter) continue;
                                $checked = in_array($key, $campaign['bot_filters'] ?? []) ? 'checked' : '';
                            ?>
                                <div class="filter-item">
                                    <div class="filter-checkbox">
                                        <input type="checkbox" name="bot_filters[]" value="<?= $key ?>" id="filter_<?= $key ?>" <?= $checked ?>>
                                        <div class="filter-content">
                                            <div class="filter-header">
                                                <label for="filter_<?= $key ?>"><?= $filter['name'] ?></label>
                                                <span class="filter-badge badge-aggressive">Aggressive</span>
                                                <?php if(in_array($key, ['navigation_token', 'behavioral_delay'])): ?>
                                                    <span class="recommended-tag">Önerilen</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="filter-desc">
                                                <?= htmlspecialchars($filter['description']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Cihaz Kuralları -->
                <div class="form-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-mobile-screen"></i>
                        </div>
                        <div class="card-title">
                            <h3>Cihaz Kuralları</h3>
                            <p>Hangi cihaz türlerinden gelen ziyaretçilerin erişebileceğini belirleyin</p>
                        </div>
                    </div>

                    <div class="devices-grid">
                        <?php 
                        $deviceIcons = ['desktop' => 'fa-desktop', 'mobile' => 'fa-mobile-screen', 'tablet' => 'fa-tablet-screen'];
                        $deviceLabels = ['desktop' => 'Masaüstü', 'mobile' => 'Mobil', 'tablet' => 'Tablet'];
                        foreach ($allDevices as $device): 
                            $checked = in_array($device, $campaign['devices'] ?? []) ? 'checked' : '';
                        ?>
                            <div class="device-item">
                                <input type="checkbox" name="devices[]" value="<?= $device ?>" id="device_<?= $device ?>" <?= $checked ?>>
                                <label for="device_<?= $device ?>">
                                    <i class="fas <?= $deviceIcons[$device] ?>"></i>
                                    <?= $deviceLabels[$device] ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Ek Ayarlar -->
                <div class="form-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-gear"></i>
                        </div>
                        <div class="card-title">
                            <h3>Ek Ayarlar</h3>
                            <p>Kampanya çalışma ayarlarını yapılandırın</p>
                        </div>
                    </div>

                    <?php
                    $settings = $campaign['settings'] ?? [];
                    $settingItems = [
                        'enable_logging' => [
                            'label' => 'Loglama Aktif',
                            'desc' => 'Tüm ziyaretçi hareketlerini kaydeder. İstatistikleri görmek için açık olmalıdır.',
                            'icon' => 'fa-database'
                        ],
                        'enable_rate_limit' => [
                            'label' => 'Rate Limit Aktif',
                            'desc' => 'Aynı IP\'den gelen aşırı istekleri sınırlar. Bot saldırılarını engeller.',
                            'icon' => 'fa-gauge-high'
                        ]
                    ];
                    ?>

                    <div class="settings-grid">
                        <?php foreach ($settingItems as $setting => $item):
                            $checked = !empty($settings[$setting]) ? 'checked' : '';
                        ?>
                            <div class="setting-item">
                                <div class="setting-switch">
                                    <input type="checkbox" name="settings[<?= $setting ?>]" value="1" id="setting_<?= $setting ?>" <?= $checked ?>>
                                    <label for="setting_<?= $setting ?>">
                                        <i class="fas <?= $item['icon'] ?> me-2"></i>
                                        <?= $item['label'] ?>
                                    </label>
                                </div>
                                <div class="setting-desc">
                                    <?= $item['desc'] ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Form Butonları -->
                <div class="form-actions">
                    <a href="campaign_detail.php?id=<?= $campaign['id'] ?>" class="btn-secondary">
                        <i class="fas fa-times"></i>
                        İptal
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-cloud-upload-alt"></i>
                        Kaydet ve Deploy Et
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.innerHTML = `<i class="fas fa-check-circle"></i><span>${message}</span>`;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 2000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deploy Ediliyor...';
                    }
                });
            }
        });
    </script>
</body>
</html>