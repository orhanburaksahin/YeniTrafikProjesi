<?php
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header("Location: index.php");
    exit;
}

// Kampanyaları yükle
$campaignsFile = __DIR__ . "/config/campaigns.json";
$campaigns = json_decode(file_get_contents($campaignsFile), true) ?? [];

// Trafik loglarını yükle
$logsFile = __DIR__ . "/config/traffic_logs.json";
$logs = json_decode(file_get_contents($logsFile), true) ?? [];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .btn-sm { padding: .25rem .5rem; font-size: .875rem; }
        .approve-campaign, .delete-campaign { min-width: 90px; }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar p-4 d-flex flex-column">
        <h4 class="mb-4">🚦 Admin Panel</h4>
        <nav class="nav flex-column">
            <a class="nav-link active" href="admin_dashboard.php">📊 Dashboard</a>
            <a class="nav-link" href="#">➕ Kullanıcı Yönetimi</a>
            
            <a href="logout.php" class="btn btn-danger btn-sm mt-3">Çıkış Yap</a>
        </nav>
        <div class="mt-auto pt-4 text-muted small">
            <?= htmlspecialchars($_SESSION['user']['email'] ?? 'Admin') ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content flex-grow-1 p-4">

        <!-- Dashboard Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card p-4">
                    <h5>Toplam Kampanya</h5>
                    <h2><?= count($campaigns) ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4">
                    <h5>Bekleyen Kampanya</h5>
                    <h2><?= count(array_filter($campaigns, fn($c) => ($c['status'] ?? '') === 'pending')) ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4">
                    <h5>Aktif Kampanya</h5>
                    <h2><?= count(array_filter($campaigns, fn($c) => ($c['status'] ?? '') === 'active')) ?></h2>
                </div>
            </div>
        </div>

        <!-- Campaign Table -->
        <div class="card p-4 mb-4">
            <h4 class="mb-4">📋 Tüm Kampanyalar</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Kullanıcı</th>
                            <th>Ad</th>
                            <th>Hedef URL</th>
                            <th>Worker Link</th>
                            <th>Durum</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $c): ?>
                            <?php
                                $user = is_array($c['user'] ?? null) ? '' : ($c['user'] ?? '');
                                $name = is_array($c['name'] ?? null) ? '' : ($c['name'] ?? '');
                                $url = is_array($c['target_url'] ?? null) ? '' : ($c['target_url'] ?? '');
                                $id = is_array($c['id'] ?? null) ? '' : ($c['id'] ?? '');
                                $status = $c['status'] ?? '';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($c['user']['email'] ?? 'Bilinmiyor') ?></td>
                                <td><a href="campaign_detail.php?id=<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($name) ?></a></td>
                                <td><?= htmlspecialchars($url) ?></td>
                                <td><a href="https://<?= htmlspecialchars($id) ?>.eragonn.workers.dev" target="_blank"><?= htmlspecialchars($id) ?>.eragonn.workers.dev</a></td>
                                <td>
                                    <?php if ($status === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Bekleniyor</span>
                                    <?php elseif ($status === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Error</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($status === 'pending'): ?>
                                        <button class="btn btn-outline-primary btn-sm approve-campaign" data-id="<?= htmlspecialchars($id) ?>">✅ Onayla</button>
                                    <?php else: ?>
                                        <span class="text-muted"></span>
                                    <?php endif; ?>
                                    <button class="btn btn-outline-danger btn-sm ms-1 delete-campaign" data-id="<?= htmlspecialchars($id) ?>">🗑️ Sil</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
async function handleButtonAction(selector, url, loadingText, successCallback) {
    document.querySelectorAll(selector).forEach(btn => {
        btn.addEventListener('click', async () => {
            const campaignId = btn.dataset.id;
            if (!campaignId) return;

            if (selector === '.delete-campaign' && !confirm('Bu kampanyayı silmek istediğine emin misin?')) return;

            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = loadingText;

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'id=' + encodeURIComponent(campaignId)
                });
                const data = await res.json();

                if (data.success) successCallback();
                else {
                    alert('Hata: ' + (data.error || 'Bilinmeyen hata'));
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            } catch (err) {
                alert('Bir hata oluştu: ' + err.message);
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
    });
}

// Onayla ve Sil butonları
handleButtonAction('.approve-campaign', 'actions/approve_campaign.php', 'Onaylanıyor...', () => location.reload());
handleButtonAction('.delete-campaign', 'actions/delete_campaign.php', 'Siliniyor...', () => location.reload());
</script>

</body>
</html>
