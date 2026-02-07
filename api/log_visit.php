<?php
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid input"]);
    exit;
}

// Log dizisine ekle
$logsFile = __DIR__ . "/../config/traffic_logs.json";
$logs = json_decode(file_get_contents($logsFile), true) ?? [];

$logs[] = [
    "campaign_id" => $input['campaign_id'] ?? null,
    "ip" => $input['ip'] ?? null,
    "user_agent" => $input['user_agent'] ?? null,
    "status" => $input['status'] ?? null,
    "timestamp" => date("Y-m-d H:i:s"),
];

// Dosyaya yaz
file_put_contents($logsFile, json_encode($logs, JSON_PRETTY_PRINT));

echo json_encode(["success" => true]);
