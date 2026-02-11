<?php
session_start();
header('Content-Type: application/json');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// JSON çıktı fonksiyonu
function jsonResponse($data) {
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Admin kontrolü
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    jsonResponse(['success' => false, 'error' => 'Yetkisiz erişim']);
}

// Kampanya ID al
$campaignId = $_POST['id'] ?? '';
if (!$campaignId) {
    jsonResponse(['success' => false, 'error' => 'Kampanya ID eksik']);
}

// Kampanya JSON dosyası
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
    jsonResponse(['success' => false, 'error' => 'Kampanya bulunamadı']);
}

// Diziyi yeniden indeksle ve kaydet
$campaigns = array_values($campaigns);
file_put_contents($campaignsFile, json_encode($campaigns, JSON_PRETTY_PRINT));

// Cloudflare Worker silme işlemi
try {
    require_once __DIR__ . '/../lib/worker_deployer.php';

    $workerDeployer = new WorkerDeployer();
    $workerDeployer->deleteWorker($campaignId); // kampanya ID aynı zamanda Worker adı olarak kullanılıyor

    jsonResponse(['success' => true, 'message' => 'Kampanya ve Worker silindi']);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Worker silinemedi: ' . $e->getMessage()]);
} catch (Error $err) {
    jsonResponse(['success' => false, 'error' => 'PHP Hatası: ' . $err->getMessage()]);
}
