<?php
$input = json_decode(file_get_contents("php://input"), true);
$file = "../config/traffic_logs.json";

$logs = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

if (!is_array($input)) {
    $input = [];
}

$input['campaign_id'] = $input['campaign_id'] ?? null;

$forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
if (!empty($forwarded)) {
    $ips = explode(',', $forwarded);
    $input['ip'] = trim($ips[0]);
} else {
    $input['ip'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

$input['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
$input['timestamp'] = date("Y-m-d H:i:s");

$lastLog = end($logs);
if ($lastLog 
    && $lastLog['ip'] === $input['ip'] 
    && $lastLog['campaign_id'] === $input['campaign_id'] 
    && $lastLog['user_agent'] === $input['user_agent']
    && strtotime($input['timestamp']) - strtotime($lastLog['timestamp']) < 5
) {
    http_response_code(200);
    exit;
}

$input['bot'] = false;
$input['detected_by'] = null;
$input['country'] = 'Bilinmiyor';

$ipInfo = @json_decode(file_get_contents("http://ip-api.com/json/{$input['ip']}?fields=status,country,as,org"), true);

if ($ipInfo && $ipInfo['status'] === 'success') {
    $input['country'] = $ipInfo['country'] ?? 'Bilinmiyor';
    $asn = $ipInfo['as'] ?? '';
} else {
    $asn = '';
}

$datacenterASNs = [
    'AS16509',
    'AS14061',
    'AS20473',
];

if (!empty($asn) && in_array($asn, $datacenterASNs)) {
    $input['bot'] = true;
    $input['detected_by'] = "datacenter_asn ({$asn})";
}

$input['type'] = $input['bot'] ? 'bot' : 'human';
$input['status'] = $input['bot'] ? 'bot_blocked' : 'allowed';

$logs[] = $input;
file_put_contents($file, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($input['bot']) {
    http_response_code(403);
    exit("Access denied: Bot detected ({$input['detected_by']})");
}

http_response_code(200);
