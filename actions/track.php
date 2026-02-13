<?php
$input = json_decode(file_get_contents("php://input"), true);
$file = "../config/traffic_logs.json";

$logs = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

if (!is_array($input)) {
    $input = [];
}

// Kampanya ID
$input['campaign_id'] = $input['campaign_id'] ?? null;

// ------------------------------
// Gerçek IP: Worker X-Forwarded-For veya REMOTE_ADDR
// ------------------------------
$forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
if (!empty($forwarded)) {
    // X-Forwarded-For birden fazla IP içeriyorsa ilkini al
    $ips = explode(',', $forwarded);
    $input['ip'] = trim($ips[0]);
} else {
    $input['ip'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// User-Agent ve timestamp
$input['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
$input['timestamp'] = date("Y-m-d H:i:s");

// ------------------------------
// Çift loglama engeli (aynı IP + campaign + UA 5 saniye içinde)
// ------------------------------
$lastLog = end($logs);
if ($lastLog 
    && $lastLog['ip'] === $input['ip'] 
    && $lastLog['campaign_id'] === $input['campaign_id'] 
    && $lastLog['user_agent'] === $input['user_agent']
    && strtotime($input['timestamp']) - strtotime($lastLog['timestamp']) < 5
) {
    http_response_code(200);
    exit; // Tekrar loglama yapma
}

// ------------------------------
// Bot tespiti ve IP / ASN kontrolü
// ------------------------------
$input['bot'] = false;
$input['detected_by'] = null;
$input['country'] = 'Bilinmiyor';

// IP’den ülke ve ASN al
$ipInfo = @json_decode(file_get_contents("http://ip-api.com/json/{$input['ip']}?fields=status,country,as,org"), true);

if ($ipInfo && $ipInfo['status'] === 'success') {
    $input['country'] = $ipInfo['country'] ?? 'Bilinmiyor';
    $asn = $ipInfo['as'] ?? '';
} else {
    $asn = '';
}

// Datacenter ASN listesi (bot tespiti)
$datacenterASNs = [
    'AS16509', // Amazon
    'AS14061', // DigitalOcean
    'AS20473', // Vultr
    // istediğin kadar ekle
];

if (!empty($asn) && in_array($asn, $datacenterASNs)) {
    $input['bot'] = true;
    $input['detected_by'] = "datacenter_asn ({$asn})";
}

// Ayrı kategori etiketi
$input['type'] = $input['bot'] ? 'bot' : 'human';

// Status alanı
$input['status'] = $input['bot'] ? 'bot_blocked' : 'allowed';

// ------------------------------
// Loglama
// ------------------------------
$logs[] = $input;
file_put_contents($file, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// ------------------------------
// Bot ise 403 engelle
// ------------------------------
if ($input['bot']) {
    http_response_code(403);
    exit("Access denied: Bot detected ({$input['detected_by']})");
}

http_response_code(200);
