<?php
session_start();
header('Content-Type: application/json');

// Admin kontrolü
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

$campaignId = $_POST['id'] ?? '';
if (!$campaignId) {
    echo json_encode(['success' => false, 'error' => 'Kampanya ID eksik']);
    exit;
}

$campaignsFile = __DIR__ . "/../config/campaigns.json";
$campaigns = json_decode(file_get_contents($campaignsFile), true) ?? [];

$found = false;
foreach ($campaigns as $key => $c) {
    if ($c['id'] === $campaignId) {
        unset($campaigns[$key]);
        $found = true;
        break;
    }
}

if (!$found) {
    echo json_encode(['success' => false, 'error' => 'Kampanya bulunamadı']);
    exit;
}

// Diziyi yeniden indeksle ve kaydet
$campaigns = array_values($campaigns);
file_put_contents($campaignsFile, json_encode($campaigns, JSON_PRETTY_PRINT));

echo json_encode(['success' => true]);
