<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Giriş yapılmamış']);
    exit;
}

// Kampanya ID POST ile alınıyor
$campaignId = $_POST['id'] ?? '';
if (!$campaignId) {
    echo json_encode(['success' => false, 'error' => 'Kampanya ID yok']);
    exit;
}

require_once __DIR__ . "/../lib/worker_deployer.php";

$deployer = new WorkerDeployer();

// Preview URL aktif et
$result = $deployer->enablePreview($campaignId);

// Eğer başarılı ise campaigns.json dosyasını güncelle
if ($result['success']) {
    $campaignsFile = __DIR__ . "/../config/campaigns.json";

    if (!file_exists($campaignsFile)) {
        echo json_encode(['success' => false, 'error' => 'Kampanya dosyası bulunamadı']);
        exit;
    }

    $campaigns = json_decode(file_get_contents($campaignsFile), true);

    foreach ($campaigns as &$c) {
        if ($c['id'] === $campaignId) {
            $c['preview_enabled'] = true; // Preview aktif
            $c['status'] = 'active';      // Opsiyonel: Kullanıcıya active göster
            break;
        }
    }

    file_put_contents($campaignsFile, json_encode($campaigns, JSON_PRETTY_PRINT));
}

echo json_encode($result);
