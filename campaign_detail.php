<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$currentUserEmail = is_array($_SESSION['user']) ? ($_SESSION['user']['email'] ?? '') : $_SESSION['user'];

$campaignId = $_GET['id'] ?? '';
if (!$campaignId) {
    die("Kampanya ID belirtilmedi.");
}

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

// Logları al
$logs = json_decode(file_get_contents("config/traffic_logs.json"), true) ?? [];
$campaignLogs = array_values(array_filter($logs, fn($l) => $l['campaign_id'] === $campaignId));

// En yeni loglar en üstte
$campaignLogs = array_reverse($campaignLogs);

$humanCount = count(array_filter($campaignLogs, fn($l) => $l['status'] === 'allowed'));
$botCount = count(array_filter($campaignLogs, fn($l) => $l['status'] !== 'allowed'));

function shortUserAgent($ua) {
    $browser = 'Bilinmiyor';
    $os = 'Bilinmiyor';

    if (stripos($ua, 'Chrome') !== false && stripos($ua, 'Edg') === false) $browser = 'Chrome';
    elseif (stripos($ua, 'Edg') !== false) $browser = 'Edge';
    elseif (stripos($ua, 'Firefox') !== false) $browser = 'Firefox';
    elseif (stripos($ua, 'Safari') !== false) $browser = 'Safari';

    if (stripos($ua, 'Windows') !== false) $os = 'Windows';
    elseif (stripos($ua, 'Macintosh') !== false) $os = 'MacOS';
    elseif (stripos($ua, 'Android') !== false) $os = 'Android';
    elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) $os = 'iOS';
    elseif (stripos($ua, 'Linux') !== false) $os = 'Linux';

    return $browser . ' · ' . $os;
}

// Logları temizleme işlemi
if (isset($_POST['clear_logs'])) {
    $logs = array_filter($logs, fn($l) => $l['campaign_id'] !== $campaignId);
    file_put_contents("config/traffic_logs.json", json_encode(array_values($logs), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: campaign_detail.php?id=$campaignId");
    exit;
}
function countryCodeToName($code) {

    if (!$code) return "Geçersiz Ülke";

    $code = strtoupper($code);

    $countries = [

        // 🇪🇺 Avrupa
        "TR" => "Türkiye",
        "DE" => "Almanya",
        "FR" => "Fransa",
        "GB" => "Birleşik Krallık",
        "IT" => "İtalya",
        "ES" => "İspanya",
        "NL" => "Hollanda",
        "BE" => "Belçika",
        "AT" => "Avusturya",
        "CH" => "İsviçre",
        "SE" => "İsveç",
        "NO" => "Norveç",
        "DK" => "Danimarka",
        "FI" => "Finlandiya",
        "PL" => "Polonya",
        "RO" => "Romanya",
        "GR" => "Yunanistan",
        "PT" => "Portekiz",
        "HU" => "Macaristan",
        "CZ" => "Çekya",
        "UA" => "Ukrayna",
        "BG" => "Bulgaristan",
        "RS" => "Sırbistan",

        // 🇺🇸 Amerika
        "US" => "Amerika Birleşik Devletleri",
        "CA" => "Kanada",
        "MX" => "Meksika",
        "BR" => "Brezilya",
        "AR" => "Arjantin",
        "CL" => "Şili",
        "CO" => "Kolombiya",
        "PE" => "Peru",

        // 🌏 Asya
        "CN" => "Çin",
        "JP" => "Japonya",
        "KR" => "Güney Kore",
        "IN" => "Hindistan",
        "ID" => "Endonezya",
        "MY" => "Malezya",
        "SG" => "Singapur",
        "TH" => "Tayland",
        "VN" => "Vietnam",
        "PH" => "Filipinler",
        "PK" => "Pakistan",

        // 🌍 Orta Doğu
        "AE" => "Birleşik Arap Emirlikleri",
        "SA" => "Suudi Arabistan",
        "QA" => "Katar",
        "KW" => "Kuveyt",
        "IR" => "İran",
        "IQ" => "Irak",
        "IL" => "İsrail",
        "JO" => "Ürdün",
        "LB" => "Lübnan",

        // 🌍 Afrika
        "ZA" => "Güney Afrika",
        "EG" => "Mısır",
        "NG" => "Nijerya",
        "MA" => "Fas",
        "DZ" => "Cezayir",

        // 🌏 Okyanusya
        "AU" => "Avustralya",
        "NZ" => "Yeni Zelanda"
    ];

    return $countries[$code] ?? "Bilinmeyen Ülke ($code)";
}


function countryCodeToFlag($code) {
    if (!$code || strlen($code) !== 2) return "";

    $code = strtoupper($code);
    return mb_chr(127397 + ord($code[0]), 'UTF-8') .
           mb_chr(127397 + ord($code[1]), 'UTF-8');
}




?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($campaign['name']) ?> - Kampanya Detayı</title>
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
			<a href="edit_campaign.php?id=<?= $campaign['id'] ?>" class="btn btn-success mb-3">⚙️ Kampanyayı Düzenle</a>
           <!-- <a class="nav-link" href="new_campaign.php">Yeni Kampanya Oluştur</a> -->
        </nav>

        <div class="user-box mt-auto pt-3 border-top border-secondary">
            <div class="small text-muted">Oturum açan kullanıcı</div>
            <div class="fw-semibold"><?= htmlspecialchars($currentUserEmail) ?></div>
            <a href="logout.php" class="btn btn-outline-danger btn-sm mt-3 w-100">Çıkış Yap</a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="content flex-grow-1">
        <div class="row g-4">
            <div class="col-lg-8">

                <!-- İstatistik Kartları -->
                <div class="stats-grid mb-4">
                    <div class="stat-card green">
                        <div class="stat-icon">👤</div>
                        <div>
                            <div class="stat-title">İnsan Ziyaretçi</div>
                            <div class="stat-value"><?= $humanCount ?></div>
                        </div>
                    </div>
                    <div class="stat-card red">
                        <div class="stat-icon">🤖</div>
                        <div>
                            <div class="stat-title">Engellenen Bot</div>
                            <div class="stat-value"><?= $botCount ?></div>
                        </div>
                    </div>
                </div>

                <!-- Logları Temizle Butonu -->
                <form method="post" class="mb-3">
                    <button type="submit" name="clear_logs" class="btn btn-danger btn-sm">🗑️ Logları Temizle</button>
                </form>
				
				

                <!-- Ziyaretçi Kayıtları -->
                <div class="campaign-section">
                    <h2>Ziyaretçi Kayıtları</h2>
                    <p>Bu kampanyaya gelen tüm ziyaretçilerin kayıtları (en yeni üstte)</p>

                    <?php if(count($campaignLogs) > 0): ?>
                        <?php foreach ($campaignLogs as $log): ?>
                            <div class="campaign-card mb-2">
                                <div class="campaign-header">
                                    <div class="campaign-title">
                                        <?= $log['status'] === 'allowed' ? '👤 İnsan Ziyaretçi' : '🤖 Bot Tespiti' ?>
                                        <?php if ($log['status'] === 'allowed'): ?>
                                            <span class="badge active">İzin Verildi</span>
                                        <?php else: ?>
                                            <span class="badge error">Engellendi</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="campaign-meta mt-2">
                                    <div><strong>Zaman:</strong> <?= htmlspecialchars($log['timestamp']) ?></div>
                                    <div><strong>IP:</strong> <?= htmlspecialchars($log['ip']) ?></div>
                                    <div><strong>Cihaz:</strong> <?= shortUserAgent($log['user_agent'] ?? '') ?></div>
                                    <?php
$countryCode = strtoupper($log['country'] ?? '');

if (!$countryCode) {
    $countryDisplay = "Bilinmeyen Ülke";
} else {
    $countryName = countryCodeToName($countryCode);
    $flagUrl = "https://flagcdn.com/24x18/" . strtolower($countryCode) . ".png";

    $countryDisplay = '<img src="' . $flagUrl . '" 
        style="width:24px;height:18px;vertical-align:middle;margin-right:6px;border-radius:2px;"> 
        ' . htmlspecialchars($countryName);
}
?>

<div><?= $countryDisplay ?></div>

                                    <?php if (!empty($log['is_bot'])): ?>
                                        <?php $reason = $log['detected_by'] ?? $log['filter_hit'] ?? 'Bilinmiyor'; ?>
                                        <div><strong>Tespit Nedeni:</strong> <?= htmlspecialchars($reason) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-muted">Henüz ziyaretçi kaydı yok.</div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Sağ Alan: Kampanya Bilgileri -->
            <div class="col-lg-4">
                <div class="card p-4 mb-4">
                    <h5 class="mb-3">Kampanya Bilgileri</h5>
                    <p><strong>Ad:</strong> <?= htmlspecialchars($campaign['name']) ?></p>
                    <p><strong>Hedef URL:</strong> <?= htmlspecialchars($campaign['target_url']) ?></p>
                    <div class="mb-2"><strong>Durum:</strong>
                        <?php if ($campaign['status'] === 'active'): ?>
                            <span class="badge bg-success">Aktif</span>
                        <?php elseif ($campaign['status'] === 'pending'): ?>
                            <span class="badge bg-warning text-dark">Beklemede</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Hata</span>
                        <?php endif; ?>
                    </div>
                    <p><strong>Worker Link:</strong></p>
                    <?php if (!empty($campaign['worker_domain'])): ?>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" id="workerLink" class="form-control form-control-sm" readonly
                                   value="https://<?= htmlspecialchars($campaign['id']) ?>.eragonn.workers.dev">
                            <button class="btn btn-sm btn-outline-primary" onclick="copyWorkerLink()">Kopyala</button>
                        </div>
                    <?php else: ?> —
                    <?php endif; ?>
                    <hr>
                    <p><strong>Aktif Bot Tespitleri:</strong></p>
                    <ul class="mb-3">
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
            </div>

        </div>
        <!-- <a href="dashboard.php" class="btn btn-primary mt-4">⬅️ Dashboard'a Dön</a> -->
    </div>
</div>

<script>
function copyWorkerLink() {
    const text = document.getElementById('workerLink').value;
    navigator.clipboard.writeText(text).then(() => alert("Link kopyalandı!"))
                                 .catch(() => alert("Link kopyalanamadı."));
}
</script>

</body>
</html>
