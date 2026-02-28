<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode([]);
    exit;
}

$campaignId = $_GET['id'] ?? '';
if (!$campaignId) {
    echo json_encode([]);
    exit;
}

// Kampanyaları kontrol et
$campaigns = json_decode(file_get_contents("../config/campaigns.json"), true) ?? [];
$campaignExists = false;
foreach ($campaigns as $c) {
    $userEmail = is_array($c['user']) ? ($c['user']['email'] ?? '') : $c['user'];
    if ($c['id'] === $campaignId && $userEmail === $_SESSION['user']) {
        $campaignExists = true;
        break;
    }
}

if (!$campaignExists) {
    echo json_encode([]);
    exit;
}

// Logları filtrele
$logs = json_decode(file_get_contents("../config/traffic_logs.json"), true) ?? [];
$campaignLogs = array_filter($logs, fn($l) => ($l['campaign_id'] ?? '') === $campaignId);

// En yeniler üstte olsun
$campaignLogs = array_reverse($campaignLogs);

// Sadece son 100 log (opsiyonel)
// $campaignLogs = array_slice($campaignLogs, 0, 100);

echo json_encode(array_values($campaignLogs));
?>