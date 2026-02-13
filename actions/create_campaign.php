<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

require_once "../lib/worker_generator.php";
require_once "../lib/worker_deployer.php";

// ✅ GLOBAL SECRET (log_visit.php ile birebir aynı olacak)
$GLOBAL_SECRET = "cok_guclu_uzun_random_bir_secret_123XYZ";

// Kullanıcı email'ini güvenli al
$currentUser = is_array($_SESSION['user']) ? ($_SESSION['user']['email'] ?? '') : $_SESSION['user'];

$name = trim($_POST['name']);
$targetUrl = trim($_POST['target_url']);
$devices = $_POST['devices'] ?? [];
$botFilters = $_POST['bot_filters'] ?? [];

// Eğer cihaz seçilmediyse → tüm cihazlar
if (empty($devices)) {
    $devices = ["desktop", "mobile", "tablet", "laptop"];
}

// 🔥 Eğer bot filtresi seçilmediyse → FULL KORUMA SETİ
if (empty($botFilters)) {
    $botFilters = [
        "headless",
        "datacenter",
        "ua",
        "behavior",
        "js_challenge",
        "rate_limit",
        "fingerprint",
        "automation_flags",
        "accept_mismatch",
        "suspicious_referer",
        "ip_entropy",
        "header_consistency",
        "tls_fingerprint",
        "entropy",
        "navigation_flow"
    ];
}

$campaignsFile = "../config/campaigns.json";
$campaigns = json_decode(file_get_contents($campaignsFile), true) ?? [];

$campaignId = uniqid("cmp_");

// 1️⃣ Worker scripti üret (SECRET parametresi eklendi)
$scriptContent = generateWorkerScript(
    $campaignId,
    $targetUrl,
    $botFilters,
    $devices,
    $GLOBAL_SECRET // ✅ EKLENDİ
);

// 2️⃣ Worker dosyasını kaydet
$workerDir = __DIR__ . "/../workers";
if (!is_dir($workerDir)) mkdir($workerDir, 0777, true);

$workerFile = $workerDir . "/{$campaignId}.js";
file_put_contents($workerFile, $scriptContent);

// 3️⃣ Cloudflare'a deploy et
try {
    $deployer = new WorkerDeployer();
    $deployer->deploy($campaignId, $scriptContent);

    // 🔹 bindRoute() kaldırıldı, workers.dev linki otomatik
    $workerDomain = "https://{$campaignId}.workers.dev"; // dashboard'da gösterilecek link
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
    "user" => $currentUser,
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
