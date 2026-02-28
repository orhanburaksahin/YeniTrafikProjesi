<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$currentUserEmail = is_array($_SESSION['user']) ? ($_SESSION['user']['email'] ?? '') : $_SESSION['user'];

$campaigns = json_decode(file_get_contents("config/campaigns.json"), true) ?? [];

$userCampaigns = array_filter($campaigns, function($c) use ($currentUserEmail) {
    if (!isset($c['user'])) return false;
    $userEmail = is_array($c['user']) ? ($c['user']['email'] ?? '') : $c['user'];
    return $userEmail === $currentUserEmail && in_array($c['status'], ['pending','error','active']);
});

$logs = json_decode(file_get_contents("config/traffic_logs.json"), true) ?? [];

// Her kampanya için istatistikleri hesapla
foreach ($userCampaigns as &$c) {
    $campaignLogs = array_filter($logs, fn($l) => ($l['campaign_id'] ?? '') === $c['id']);
    $c['stats'] = [
        'clicks' => count($campaignLogs),
        'bots' => count(array_filter($campaignLogs, fn($l) => ($l['status'] ?? '') !== 'allowed')),
        'today' => count(array_filter($campaignLogs, fn($l) => substr($l['timestamp'] ?? '', 0, 10) === date('Y-m-d')))
    ];
}

$totalWorkers = count($userCampaigns);
$activeWorkers = count(array_filter($userCampaigns, fn($c) => $c['status'] === 'active'));
$totalClicks = count($logs);
$blockedBots = count(array_filter($logs, fn($l) => ($l['status'] ?? '') !== 'allowed'));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Traffic Guard | Dashboard</title>
    
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

        .action-buttons {
            display: flex;
            gap: 12px;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
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
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
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

        /* İstatistik Kartları */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            border: 1px solid #eef2f6;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: #eef2ff;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #2563eb;
        }

        .stat-content {
            flex: 1;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0f1829;
            margin-bottom: 4px;
        }

        .stat-label {
            color: #5f6f8f;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Kampanya Başlığı */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0f1829;
        }

        .campaign-count {
            background: #eef2ff;
            color: #2563eb;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Kampanya Kartları - TAM GENİŞLİK */
        .campaigns-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .campaign-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #eef2f6;
            transition: all 0.2s;
            width: 100%;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        }

        .campaign-card:hover {
            border-color: #2563eb;
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.1);
        }

        .campaign-row {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        /* Kampanya Bilgileri - Solda */
        .campaign-info {
            flex: 2;
        }

        .campaign-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .campaign-header h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f1829;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .status-active {
            background: #e3f9ed;
            color: #0f6e3f;
        }

        .status-pending {
            background: #fff4e5;
            color: #b45b0a;
        }

        .status-error {
            background: #ffe8e8;
            color: #c24141;
        }

        .campaign-url {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f8fafc;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid #eef2f6;
        }

        .campaign-url code {
            font-size: 0.85rem;
            color: #2563eb;
            font-weight: 500;
        }

        .copy-btn {
            background: #fff;
            border: 1px solid #eef2f6;
            color: #5f6f8f;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .copy-btn:hover {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }

        /* İstatistikler - Ortada */
        .campaign-stats {
            flex: 2;
            display: flex;
            gap: 20px;
        }

        .stat-item {
            flex: 1;
            text-align: center;
        }

        .stat-item-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0f1829;
            margin-bottom: 4px;
        }

        .stat-item-label {
            font-size: 0.7rem;
            color: #5f6f8f;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* Progress Bar - Sağda */
        .campaign-progress {
            flex: 1;
            min-width: 150px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .progress-header span {
            font-size: 0.8rem;
            font-weight: 600;
            color: #5f6f8f;
        }

        .progress-bar {
            height: 8px;
            background: #eef2f6;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #2563eb, #4f46e5);
            border-radius: 20px;
            width: 0%;
        }

        .action-icons {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .action-icon {
            width: 36px;
            height: 36px;
            background: #f8fafc;
            border: 1px solid #eef2f6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #5f6f8f;
            text-decoration: none;
            transition: all 0.2s;
        }

        .action-icon:hover {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }

        /* Boş Durum */
        .empty-state {
            background: #fff;
            border-radius: 16px;
            padding: 60px 30px;
            text-align: center;
            border: 1px solid #eef2f6;
        }

        .empty-icon {
            font-size: 3.5rem;
            color: #a5b4fc;
            margin-bottom: 20px;
        }

        .empty-state h4 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0f1829;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #5f6f8f;
            margin-bottom: 24px;
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
        @media (max-width: 1400px) {
            .campaign-row {
                flex-wrap: wrap;
            }
            .campaign-info, .campaign-stats, .campaign-progress {
                flex: auto;
                width: 100%;
            }
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
                <a href="dashboard.php" class="nav-item active">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
                <a href="new_campaign.php" class="nav-item">
                    <i class="fas fa-plus-circle"></i>
                    <span>Yeni Kampanya</span>
                </a>
                <a href="campaigns.php" class="nav-item">
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
                    <h2>Dashboard</h2>
                    <p>Kampanyalarınızı yönetin ve performansı takip edin</p>
                </div>
                <div class="action-buttons">
                    <a href="reports.php" class="btn-secondary">
                        <i class="fas fa-download"></i>
                        Rapor Al
                    </a>
                    <a href="new_campaign.php" class="btn-primary">
                        <i class="fas fa-plus"></i>
                        Yeni Kampanya
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $totalWorkers ?></div>
                        <div class="stat-label">Toplam Kampanya</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shield"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $activeWorkers ?></div>
                        <div class="stat-label">Aktif Kampanya</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= number_format($totalClicks) ?></div>
                        <div class="stat-label">Toplam Ziyaret</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= number_format($blockedBots) ?></div>
                        <div class="stat-label">Engellenen Bot</div>
                    </div>
                </div>
            </div>

            <!-- Campaigns Section -->
            <div class="section-header">
                <h3>Kampanyalar</h3>
                <span class="campaign-count"><?= count($userCampaigns) ?> kayıt</span>
            </div>

            <?php if(count($userCampaigns) > 0): ?>
                <div class="campaigns-list">
                    <?php foreach($userCampaigns as $c): 
                        $successRate = $c['stats']['clicks'] > 0 ? round(($c['stats']['clicks'] - $c['stats']['bots']) / $c['stats']['clicks'] * 100) : 100;
                    ?>
                        <div class="campaign-card">
                            <div class="campaign-row">
                                <!-- Sol: Kampanya Bilgileri -->
                                <div class="campaign-info">
                                    <div class="campaign-header">
                                        <h4><?= htmlspecialchars($c['name']) ?></h4>
                                        <?php if($c['status'] === 'active'): ?>
                                            <span class="status-badge status-active">Aktif</span>
                                        <?php elseif($c['status'] === 'pending'): ?>
                                            <span class="status-badge status-pending">Onay Bekliyor</span>
                                        <?php else: ?>
                                            <span class="status-badge status-error">Hata</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="campaign-url">
                                        <code>https://<?= htmlspecialchars($c['id']) ?>.eragonn.workers.dev</code>
                                        <button class="copy-btn" onclick="copyToClipboard('https://<?= htmlspecialchars($c['id']) ?>.eragonn.workers.dev')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Orta: İstatistikler -->
                                <div class="campaign-stats">
                                    <div class="stat-item">
                                        <div class="stat-item-value"><?= number_format($c['stats']['clicks']) ?></div>
                                        <div class="stat-item-label">Toplam</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-item-value"><?= number_format($c['stats']['bots']) ?></div>
                                        <div class="stat-item-label">Bot</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-item-value"><?= number_format($c['stats']['today']) ?></div>
                                        <div class="stat-item-label">Bugün</div>
                                    </div>
                                </div>

                                <!-- Sağ: Progress ve Actions -->
                                <div class="campaign-progress">
                                    <div class="progress-header">
                                        <span>Başarı Oranı</span>
                                        <span>%<?= $successRate ?></span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= $successRate ?>%;"></div>
                                    </div>
                                    <div class="action-icons">
                                        <a href="campaign_detail.php?id=<?= htmlspecialchars($c['id']) ?>" class="action-icon" title="Detay">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                        <a href="edit_campaign.php?id=<?= htmlspecialchars($c['id']) ?>" class="action-icon" title="Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="action-icon" title="İstatistik" onclick="alert('İstatistikler hazırlanıyor...')">
                                            <i class="fas fa-chart-bar"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h4>Henüz kampanyanız yok</h4>
                    <p>İlk kampanyanızı oluşturarak bot korumasını başlatın.</p>
                    <a href="new_campaign.php" class="btn-primary" style="display: inline-flex;">
                        <i class="fas fa-plus"></i>
                        Kampanya Oluştur
                    </a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                const toast = document.createElement('div');
                toast.className = 'toast';
                toast.innerHTML = '<i class="fas fa-check-circle"></i><span>Kampanya linki kopyalandı!</span>';
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => toast.remove(), 300);
                }, 2000);
            });
        }
    </script>
</body>
</html>