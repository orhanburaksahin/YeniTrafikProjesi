<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// Eğer $_SESSION['user'] array ise email alanını alıyoruz
$currentUserEmail = is_array($_SESSION['user']) ? ($_SESSION['user']['email'] ?? '') : $_SESSION['user'];

// Kampanya ID al
$campaignId = $_GET['id'] ?? '';
if (!$campaignId) {
    die("Kampanya ID belirtilmedi.");
}

// Kampanyaları yükle
$campaigns = json_decode(file_get_contents("config/campaigns.json"), true) ?? [];
$campaign = null;

foreach ($campaigns as $c) {
    $userEmail = is_array($c['user']) ? ($c['user']['email'] ?? '') : $c['user'];
    if ($c['id'] === $campaignId && $userEmail === $currentUserEmail) {
        $campaign = $c;
        break;
    }
}
if (!$campaign) {
    die("Kampanya bulunamadı veya yetkiniz yok.");
}

// Trafik logları
$logs = json_decode(file_get_contents("config/traffic_logs.json"), true) ?? [];
$campaignLogs = array_filter($logs, fn($l) => $l['campaign_id'] === $campaignId);

// İnsan ve Bot sayısı başlangıç
$humanCount = count(array_filter($campaignLogs, fn($l) => $l['status'] === 'allowed'));
$botCount = count(array_filter($campaignLogs, fn($l) => $l['status'] !== 'allowed'));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($campaign['name']) ?> - Detay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar p-4 d-flex flex-column">
        <h4 class="mb-4">🚦 Traffic Guard</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php">📊 Dashboard</a>
            <a class="nav-link" href="new_campaign.php">➕ Yeni Kampanya</a>
            <a class="nav-link" href="#">📈 Trafik Analizi</a>
            <a class="nav-link" href="#">🛡️ Bot Koruma</a>
            <a class="nav-link" href="logout.php">🚪 Çıkış</a>
        </nav>
        <div class="mt-auto pt-4 text-muted small">
            <?= htmlspecialchars($currentUserEmail) ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content flex-grow-1">
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card p-4">
                    <h5>İnsan Ziyaretçi</h5>
                    <h2 id="humanCount"><?= $humanCount ?></h2>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-4">
                    <h5>Bot Ziyaretçi</h5>
                    <h2 id="botCount"><?= $botCount ?></h2>
                </div>
            </div>
        </div>

        <!-- Campaign Info -->
        <div class="card p-4 mb-4">
            <h4 class="mb-4">Kampanya Bilgileri</h4>
            <p><strong>Ad:</strong> <?= htmlspecialchars($campaign['name']) ?></p>
            <p><strong>Hedef URL:</strong> <?= htmlspecialchars($campaign['target_url']) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($campaign['status']) ?></p>
            <p><strong>Worker Link:</strong>
                <?php if (!empty($campaign['worker_domain'])): ?>
                    <a href="https://<?= htmlspecialchars($campaign['id']) ?>.eragonn.workers.dev" target="_blank">
                        https://<?= htmlspecialchars($campaign['id']) ?>.eragonn.workers.dev
                    </a>
                <?php else: ?>
                    —
                <?php endif; ?>
            </p>

            <p><strong>Aktif Bot Tespit Mekanizmaları:</strong></p>
            <ul>
                <?php foreach($campaign['bot_filters'] ?? [] as $bot): ?>
                    <li><?= htmlspecialchars($bot) ?></li>
                <?php endforeach; ?>
            </ul>

            <p><strong>Aktif Cihaz Kuralları:</strong></p>
            <ul>
                <?php foreach($campaign['devices'] ?? [] as $device): ?>
                    <li><?= htmlspecialchars(ucfirst($device)) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Visitor Logs Table -->
        <div class="card p-4 mb-4">
            <h4>Ziyaretçi Detayları</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-dark-custom">
                    <thead>
                        <tr>
                            <th>Zaman</th>
                            <th>IP</th>
                            <th>User-Agent</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody id="logTableBody">
                        <!-- JS ile doldurulacak -->
                    </tbody>
                </table>
            </div>
        </div>

        <a href="dashboard.php" class="btn btn-primary mt-3">⬅️ Geri</a>
    </div>
</div>

<script>
// Kampanya ID
const campaignId = "<?= htmlspecialchars($campaign['id']) ?>";

// Tablonun tbody elementini al
const tableBody = document.getElementById('logTableBody');

// Ziyaretçi loglarını çekme fonksiyonu
async function fetchLogs() {
    try {
        const response = await fetch(`api/get_campaign_logs.php?id=${campaignId}`);
        const logs = await response.json();

        // İnsan ve Bot sayısını güncelle
        const humanCount = logs.filter(l => l.status === 'allowed').length;
        const botCount = logs.filter(l => l.status !== 'allowed').length;
        document.getElementById('humanCount').innerText = humanCount;
        document.getElementById('botCount').innerText = botCount;

        // Tablonun içini temizle
        tableBody.innerHTML = '';

        // Her bir logu ekle
        logs.forEach(log => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${log.timestamp}</td>
                <td>${log.ip}</td>
                <td>${log.user_agent}</td>
                <td>${log.status === 'allowed' ? 'İnsan' : 'Bot'}</td>
            `;
            tableBody.appendChild(tr);
        });

    } catch (err) {
        console.error('Log çekilemedi:', err);
    }
}

// Başlangıçta logları çek
fetchLogs();

// Her 2 saniyede bir güncelle
setInterval(fetchLogs, 2000);
</script>

</body>
</html>
