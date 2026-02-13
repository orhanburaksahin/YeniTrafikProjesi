<?php
header("Content-Type: application/json");

// ==========================
// GLOBAL SECRET
// ==========================
$GLOBAL_SECRET = "cok_guclu_uzun_random_bir_secret_123XYZ"; // create_campaign.php ile aynı olacak

$incomingSecret = $_SERVER['HTTP_X_WORKER_SECRET'] ?? '';

if ($incomingSecret !== $GLOBAL_SECRET) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// ==========================
// INPUT
// ==========================
$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid input"]);
    exit;
}

// ==========================
// LOG SAVE
// ==========================
$logsFile = __DIR__ . "/../config/traffic_logs.json";
$logs = json_decode(file_get_contents($logsFile), true) ?? [];

// 🔹 Tekil log kontrolü için token (opsiyonel, Worker'dan gelecektir)
$logToken = $input['log_token'] ?? null;
if ($logToken) {
    foreach ($logs as $existingLog) {
        if (isset($existingLog['log_token']) && $existingLog['log_token'] === $logToken) {
            // Aynı tokenlı log zaten var → tekrar düşmesin
            echo json_encode(["success" => true, "skipped" => true]);
            exit;
        }
    }
}

$logs[] = [
    "campaign_id" => $input['campaign_id'] ?? null,
    "ip" => $input['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? null),
    "user_agent" => $input['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
    "status" => $input['status'] ?? null,
    "detected_by" => $input['detected_by'] ?? null,
    "is_bot" => $input['is_bot'] ?? false,
    "filter_hit" => $input['filter_hit'] ?? null,
    "timestamp" => date("Y-m-d H:i:s"),
    "log_token" => $logToken
];

file_put_contents($logsFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(["success" => true]);
