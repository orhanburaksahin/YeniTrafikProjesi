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

$totalWorkers = count($userCampaigns);
$activeWorkers = count(array_filter($userCampaigns, fn($c) => $c['status'] === 'active'));
$totalClicks = count($logs);
$blockedBots = count(array_filter($logs, fn($l) => $l['status'] !== 'allowed'));
?>
<!DOCTYPE html>
<html lang="tr" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Traffic Guard – Yönetim Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="d-flex">

    <!-- Sol Menü -->
    <aside class="sidebar p-4 d-flex flex-column">
        <div class="brand mb-4">
            <div class="fw-bold fs-5 text-info">Traffic Guard</div>
            <div class="text-muted small">Güvenlik Yönetim Paneli</div>
        </div>

        <nav class="nav flex-column mb-auto">
            <a class="nav-link active" href="dashboard.php">Genel Bakış</a>
            <a class="nav-link" href="new_campaign.php">Yeni Kampanya Oluştur</a>
        </nav>

        <div class="user-box mt-auto pt-3 border-top border-secondary">
            <div class="small text-muted">Oturum açan kullanıcı</div>
            <div class="fw-semibold"><?= htmlspecialchars($currentUserEmail) ?></div>
            <a href="logout.php" class="btn btn-outline-danger btn-sm mt-3 w-100">Çıkış Yap</a>
        </div>
    </aside>

    <!-- Ana İçerik -->
    <main class="content flex-grow-1">

        <!-- Üst Başlık -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Yönetim Paneli</h2>
                <p class="text-muted mb-0">Tüm kampanyalarınızı ve güvenlik trafiğinizi buradan yönetin.</p>
            </div>
            <a href="new_campaign.php" class="btn btn-primary">+ Yeni Kampanya</a>
        </div>

        <!-- İstatistik Kartları -->
        <div class="stats-grid mb-4">
            <div class="stat-card blue">
                <div class="stat-icon">🧩</div>
                <div>
                    <div class="stat-title">Toplam Worker</div>
                    <div class="stat-value"><?= $totalWorkers ?></div>
                </div>
            </div>

            <div class="stat-card green">
                <div class="stat-icon">⚡</div>
                <div>
                    <div class="stat-title">Aktif Worker</div>
                    <div class="stat-value"><?= $activeWorkers ?></div>
                </div>
            </div>

            <div class="stat-card purple">
                <div class="stat-icon">📊</div>
                <div>
                    <div class="stat-title">Toplam Ziyaret</div>
                    <div class="stat-value"><?= $totalClicks ?></div>
                </div>
            </div>

            <div class="stat-card red">
                <div class="stat-icon">🛡️</div>
                <div>
                    <div class="stat-title">Engellenen Bot</div>
                    <div class="stat-value"><?= $blockedBots ?></div>
                </div>
            </div>
        </div>

        <!-- Kampanya Yönetimi -->
        <section class="campaign-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-1">Kampanya Yönetimi</h4>
                    <p class="text-muted mb-0">Oluşturduğunuz tüm kampanyaları buradan takip edebilirsiniz.</p>
                </div>
            </div>

            <?php if(count($userCampaigns) > 0): ?>
                <?php foreach ($userCampaigns as $c): ?>
                    <div class="campaign-card">
                        <div class="campaign-header">
                            <div class="campaign-title">
                                <?= htmlspecialchars($c['name']) ?>

                                <?php if ($c['status'] === 'active'): ?>
                                    <span class="badge active">Aktif</span>
                                <?php elseif ($c['status'] === 'pending'): ?>
                                    <span class="badge pending">Onay Bekliyor</span>
                                <?php else: ?>
                                    <span class="badge error">Dağıtım Hatası</span>
                                <?php endif; ?>
                            </div>

                            <div class="campaign-actions">
                                <a href="campaign_detail.php?id=<?= htmlspecialchars($c['id']) ?>" class="btn btn-sm btn-outline-light">Yönet</a>
                            </div>
                        </div>

                        <div class="campaign-meta mt-2">
                            <div><strong>Hedef URL:</strong> <?= htmlspecialchars($c['target_url']) ?></div>
                            <div><strong>Worker Adresi:</strong></div>
                        </div>

                        <div class="campaign-link mt-2">
                            <code>https://<?= htmlspecialchars($c['id']) ?>.eragonn.workers.dev</code>
                            <button onclick="copyToClipboard('https://<?= htmlspecialchars($c['id']) ?>.eragonn.workers.dev')">📋</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-muted">Henüz kampanya oluşturmadınız.</div>
            <?php endif; ?>
        </section>

    </main>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert("Link panoya kopyalandı.");
    });
}
</script>

</body>
</html>
