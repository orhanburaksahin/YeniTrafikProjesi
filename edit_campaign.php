<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

require_once "lib/worker_generator.php";
require_once "lib/worker_deployer.php"; // deploy için gerekli

$currentUserEmail = is_array($_SESSION['user']) ? ($_SESSION['user']['email'] ?? '') : $_SESSION['user'];

$campaignId = $_GET['id'] ?? '';
if (!$campaignId) die("Kampanya ID belirtilmedi.");

$campaigns = json_decode(file_get_contents("config/campaigns.json"), true) ?? [];
$campaignIndex = null;
$campaign = null;

// Kullanıcının kampanyasını bul
foreach ($campaigns as $i => $c) {
    $userEmail = is_array($c['user']) ? ($c['user']['email'] ?? '') : $c['user'];
    if ($c['id'] === $campaignId && $userEmail === $currentUserEmail) {
        $campaignIndex = $i;
        $campaign = $c;
        break;
    }
}

if (!$campaign) die("Kampanya bulunamadı veya yetkiniz yok.");

// Ayarlar hazır
$allBotFilters = ['fingerprint','js_challenge','headless','datacenter','behavior','automation_flags','accept_mismatch','suspicious_referer','ip_entropy','rate_limit','header_consistency','tls_fingerprint','entropy','navigation_flow'];
$allDevices = ['desktop','mobile','tablet'];

// POST geldiyse güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updated = $campaign;

    // Sadece kullanıcı değiştirebileceği alanlar
    $updated['bot_filters'] = $_POST['bot_filters'] ?? [];
    $updated['devices'] = $_POST['devices'] ?? [];
    $updated['settings'] = $_POST['settings'] ?? [];

    // JSON kaydet
    $campaigns[$campaignIndex] = $updated;
    file_put_contents("config/campaigns.json", json_encode($campaigns, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // Worker script oluştur ve deploy et
    $workerCode = generateWorkerScript(
        $campaign['id'],
        $campaign['target_url'],
        $updated['bot_filters'],
        $updated['devices']
    );

     // lib/worker_deployer.php içindeki fonksiyon
	$deployer = new WorkerDeployer();
	$deployer->deploy($campaign['id'], $workerCode); // deploy metodu class içinde tanımlı

    header("Location: campaign_detail.php?id={$campaignId}");
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($campaign['name']) ?> - Kampanyayı Düzenle</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="d-flex">
    <div class="sidebar p-4 d-flex flex-column">
        <h4 class="mb-4">🚦 Traffic Guard</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php">📊 Dashboard</a>
            <a href="logout.php" class="btn btn-danger btn-sm mt-3">Çıkış Yap</a>
        </nav>
        <div class="mt-auto pt-4 text-muted small">
            <?= htmlspecialchars($currentUserEmail) ?>
        </div>
    </div>

    <div class="content flex-grow-1 p-4">
        <h2><?= htmlspecialchars($campaign['name']) ?> - Kampanyayı Düzenle</h2>

        <form method="POST" class="mt-4">
            <h5>Bot Filtreleri</h5>
            <?php foreach ($allBotFilters as $filter): 
                $checked = in_array($filter, $campaign['bot_filters'] ?? []) ? 'checked' : '';
            ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="bot_filters[]" value="<?= $filter ?>" <?= $checked ?>>
                    <label class="form-check-label"><?= ucfirst($filter) ?></label>
                </div>
            <?php endforeach; ?>

            <h5 class="mt-3">Cihaz Kuralları</h5>
            <?php foreach ($allDevices as $device): 
                $checked = in_array($device, $campaign['devices'] ?? []) ? 'checked' : '';
            ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="devices[]" value="<?= $device ?>" <?= $checked ?>>
                    <label class="form-check-label"><?= ucfirst($device) ?></label>
                </div>
            <?php endforeach; ?>

            <h5 class="mt-3">Ek Ayarlar</h5>
            <?php
            $settings = $campaign['settings'] ?? [];
            foreach (['enable_logging','enable_rate_limit'] as $setting):
                $checked = !empty($settings[$setting]) ? 'checked' : '';
            ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="settings[<?= $setting ?>]" value="1" <?= $checked ?>>
                    <label class="form-check-label"><?= ucfirst(str_replace('_',' ',$setting)) ?></label>
                </div>
            <?php endforeach; ?>

            <div class="mt-4">
                <button type="submit" class="btn btn-success">Kaydet ve Deploy Et</button>
                <a href="campaign_detail.php?id=<?= $campaign['id'] ?>" class="btn btn-secondary">İptal</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
