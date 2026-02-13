<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

require_once "lib/worker_generator.php";
require_once "lib/worker_deployer.php"; // deploy için gerekli

$currentUserEmail = is_array($_SESSION['user']) ? ($_SESSION['user']['email'] ?? '') : $_SESSION['user'];

$GLOBAL_SECRET = "cok_guclu_uzun_random_bir_secret_123XYZ";


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
$allBotFilters = ['js_challenge','headless','datacenter','behavior','automation_flags','suspicious_referer','ip_entropy','rate_limit','header_consistency','tls_fingerprint','entropy','navigation_flow'];

$botDescriptions = [
    'fingerprint' => 'Tarayıcı ve cihaz parmak izine göre aynı botu farklı IP’lerde tespit eder.',
    'js_challenge' => 'Gerçek tarayıcı olup olmadığını anlamak için JavaScript çalıştırma testi uygular.',
    'headless' => 'Headless (ekransız) çalışan otomasyon tarayıcılarını tespit eder.',
    'datacenter' => 'AWS, DigitalOcean gibi veri merkezi IP’lerini bot olarak işaretler.',
    'behavior' => 'Kullanıcı davranış modeline göre anormal hareketleri analiz eder.',
    'automation_flags' => 'Selenium, Puppeteer gibi otomasyon araç izlerini kontrol eder.',
    'accept_mismatch' => 'HTTP Accept header uyumsuzluklarını tespit eder.',
    'suspicious_referer' => 'Şüpheli veya sahte referer (kaynak) bilgilerini algılar.',
    'ip_entropy' => 'Benzer IP bloklarından gelen yapay trafik kümelerini analiz eder.',
    'rate_limit' => 'Kısa sürede aşırı istek atan IP’leri sınırlar.',
    'header_consistency' => 'HTTP header bilgilerinin tutarlılığını kontrol eder.',
    'tls_fingerprint' => 'TLS bağlantı parmak izi ile bot kütüphanelerini tespit eder.',
    'entropy' => 'Header ve User-Agent rastgelelik seviyesini analiz eder.',
    'navigation_flow' => 'Referer kontrol eder. URL ile direkt girişi engeller.'
];

$recommendedFilters = [
'automation_flags',
'js_challenge',
'headless',
'behavior',
'suspicious_referer',
'ip_entropy',
'datacenter',
'rate_limit',
'tls_fingerprint',
'header_consistency'];

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
<title><?= htmlspecialchars($campaign['name']) ?> - Kampanyayı Düzenle</title>
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
            <a class="nav-link" href="dashboard.php">Dashboard</a>
			
           <!-- <a class="nav-link" href="new_campaign.php">Yeni Kampanya Oluştur</a> -->
        </nav>

        <div class="user-box mt-auto pt-3 border-top border-secondary">
            <div class="small text-muted">Oturum açan kullanıcı</div>
            <div class="fw-semibold"><?= htmlspecialchars($currentUserEmail) ?></div>
            <a href="logout.php" class="btn btn-outline-danger btn-sm mt-3 w-100">Çıkış Yap</a>
        </div>
    </aside>

    <div class="content flex-grow-1 p-4">
        <h2><?= htmlspecialchars($campaign['name']) ?> - Kampanyayı Düzenle</h2>

        <form method="POST" class="mt-4">
            <h5>Bot Filtreleri</h5>
            <?php foreach ($allBotFilters as $filter): 
                $checked = in_array($filter, $campaign['bot_filters'] ?? []) ? 'checked' : '';
            ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="bot_filters[]" value="<?= $filter ?>" <?= $checked ?>>
                    <label class="form-check-label">
                        <strong><?= ucfirst($filter) ?></strong>

                        <?php if(in_array($filter, $recommendedFilters)): ?>
                            <span class="badge bg-success ms-2">Önerilen</span>
                        <?php endif; ?>

                        <span class="text-muted">
                            (<?= $botDescriptions[$filter] ?? '' ?>)
                        </span>
                    </label>
                </div>
            <?php endforeach; ?>
			
			<h3>Level 2 – Aggressive Koruma</h3>

			<?php
			$level2Filters = [
				'navigation_token' => 'Navigation Token Doğrulama',
				'behavioral_delay' => 'Davranış Gecikme Kontrolü',
				'advanced_js' => 'Gelişmiş JS Challenge',
				'fingerprint_v2' => 'Fingerprint Entropy V2'
			];
			$recommendedLevel2 = ['navigation_token','behavioral_delay'];
			foreach ($level2Filters as $filter => $desc):
				$checked = in_array($filter, $campaign['bot_filters'] ?? []) ? 'checked' : '';
			?>
			<div class="form-check mb-2">
				<input class="form-check-input" type="checkbox" name="bot_filters[]" value="<?= $filter ?>" <?= $checked ?>>
				<label class="form-check-label">
					<strong><?= $desc ?></strong>
					<?php if(in_array($filter, $recommendedLevel2)): ?>
						<span class="badge bg-success ms-2">Önerilen</span>
					<?php endif; ?>
				</label>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});
</script>

</body>
</html>
