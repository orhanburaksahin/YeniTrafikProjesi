<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// Eğer $_SESSION['user'] array ise email alanını alıyoruz
$currentUserEmail = is_array($_SESSION['user']) ? ($_SESSION['user']['email'] ?? '') : $_SESSION['user'];

// Kampanyaları yükle
$campaigns = json_decode(file_get_contents("config/campaigns.json"), true) ?? [];

// Kullanıcının kampanyalarını al, status pending ve active dahil
$userCampaigns = array_filter($campaigns, function($c) use ($currentUserEmail) {
    if (!isset($c['user'])) return false;

    // Eğer user objesi şeklindeyse emaili al
    $userEmail = is_array($c['user']) ? ($c['user']['email'] ?? '') : $c['user'];

    return $userEmail === $currentUserEmail && in_array($c['status'], ['pending', 'active']);
});

// Trafik loglarını yükle
$logs = json_decode(file_get_contents("config/traffic_logs.json"), true) ?? [];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar p-4 d-flex flex-column">
        <h4 class="mb-4">🚦 Traffic Guard</h4>
        <nav class="nav flex-column">
            <a class="nav-link active" href="dashboard.php">📊 Dashboard</a>
            <a class="nav-link" href="new_campaign.php">➕ Yeni Kampanya</a>
            
            <a href="logout.php" class="btn btn-danger btn-sm mt-3">Çıkış Yap</a>
        </nav>
        <div class="mt-auto pt-4 text-muted small">
            <?= htmlspecialchars($currentUserEmail) ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content flex-grow-1">

        <!-- Dashboard Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card p-4">
                    <h5>Toplam Kampanya</h5>
                    <h2><?= count($userCampaigns) ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4">
                    <h5>Aktif Kampanya</h5>
                    <h2><?= count(array_filter($userCampaigns, fn($c) => $c['status'] === 'active')) ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4">
                    <h5>Engellenen Bot</h5>
                    <h2><?= count(array_filter($logs, fn($l) => $l['status'] !== 'allowed')) ?></h2>
                </div>
            </div>
        </div>

        <!-- Campaign Table -->
        <div class="card p-4 mb-4">
            <h4 class="mb-4">📋 Kampanyalarım</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Ad</th>
                            <th>Hedef URL</th>
                            <th>Worker Link</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(count($userCampaigns) > 0): ?>
                        <?php foreach ($userCampaigns as $c): ?>
                            <tr>
                                <td>
                                    <a href="campaign_detail.php?id=<?= htmlspecialchars($c['id']) ?>">
                                        <?= htmlspecialchars($c['name']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($c['target_url']) ?></td>
                                <td>
                                    <a href="https://<?= htmlspecialchars($c['id']) ?>.eragonn.workers.dev" target="_blank">
                                        <?= htmlspecialchars($c['id']) ?>.eragonn.workers.dev
                                    </a>
                                </td>
                                <td>
                                    <?php if ($c['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Bekleniyor</span>
                                    <?php elseif ($c['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Error</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">Henüz kampanya yok.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Campaign Traffic Chart -->
        <div class="card p-4 mb-4">
            <h4 class="mb-4">📊 Kampanya Trafik Grafiği</h4>
            <canvas id="campaignChart" width="800" height="400"></canvas>
        </div>

    </div>
</div>

<script>
// Logs PHP'den frontend'e aktarılıyor
const logs = <?= json_encode($logs) ?>;

// Grafik verilerini hazırla
const labels = logs.map(l => l.timestamp);
const humanData = logs.map(l => l.status === "allowed" ? 1 : 0);
const botData = logs.map(l => l.status !== "allowed" ? 1 : 0);

// Chart.js ile çiz
const ctx = document.getElementById('campaignChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            { label: 'İnsan', data: humanData, borderColor: 'green', fill: false },
            { label: 'Bot', data: botData, borderColor: 'red', fill: false }
        ]
    },
    options: {
        responsive: true,
        scales: {
            x: { display: true, title: { display: true, text: 'Zaman' } },
            y: { display: true, title: { display: true, text: 'Ziyaret Sayısı' }, beginAtZero: true }
        }
    }
});
</script>

</body>
</html>
