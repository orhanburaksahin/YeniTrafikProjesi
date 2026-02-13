<?php
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid input"]);
    exit;
}

$logsFile = __DIR__ . "/../config/traffic_logs.json";
$logs = json_decode(file_get_contents($logsFile), true) ?? [];

$logs[] = [
    "campaign_id" => $input['campaign_id'] ?? null,
    "ip" => $input['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? null),
    "user_agent" => $input['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
    "status" => $input['status'] ?? null, // allowed / bot_blocked / device_blocked / country_blocked
    "detected_by" => $input['detected_by'] ?? null, // headless, ua, datacenter, vb.
    "is_bot" => $input['is_bot'] ?? false,
    "filter_hit" => $input['filter_hit'] ?? null,
    "timestamp" => date("Y-m-d H:i:s"),
];

file_put_contents($logsFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(["success" => true]);
