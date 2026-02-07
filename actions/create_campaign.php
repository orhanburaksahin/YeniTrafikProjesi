<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

require_once "../lib/worker_generator.php";
require_once "../lib/worker_deployer.php";

$name = trim($_POST['name']);
$targetUrl = trim($_POST['target_url']);
$devices = $_POST['devices'] ?? [];
$botFilters = $_POST['bot_filters'] ?? [];

$campaignsFile = "../config/campaigns.json";
$campaigns = json_decode(file_get_contents($campaignsFile), true) ?? [];

$campaignId = uniqid("cmp_");

// 1️⃣ Worker scripti üret
$scriptContent = generateWorkerScript($campaignId, $targetUrl, $botFilters, $devices);

// 2️⃣ Worker dosyasını kaydet
$workerDir = __DIR__ . "/../workers";
if (!is_dir($workerDir)) mkdir($workerDir, 0777, true);

$workerFile = $workerDir . "/{$campaignId}.js";
file_put_contents($workerFile, $scriptContent);

// 3️⃣ Cloudflare'a deploy et
try {
    $deployer = new WorkerDeployer();
    $workerDomain = $deployer->deploy($campaignId, $workerFile);
    $status = "pending";
    $errorMessage = null;
} catch (Exception $e) {
    $workerDomain = null;
    $status = "error";
    $errorMessage = "Cloudflare API hatası: " . $e->getMessage();
}

// 4️⃣ Kampanya kaydet
$newCampaign = [
    "id" => $campaignId,
    "user" => $_SESSION['user'],
    "name" => $name,
    "target_url" => $targetUrl,
    "worker_domain" => $workerDomain,
    "worker_file" => $workerFile,
    "devices" => $devices,
    "bot_filters" => $botFilters,
    "country" => "TR",
    "language" => "tr",
    "status" => $status,
    "error" => $errorMessage,
    "created_at" => date("Y-m-d H:i:s")
];

$campaigns[] = $newCampaign;
file_put_contents($campaignsFile, json_encode($campaigns, JSON_PRETTY_PRINT));

header("Location: ../dashboard.php");
exit;
