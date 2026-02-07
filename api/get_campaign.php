<?php
header("Content-Type: application/json");

$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "Campaign ID missing"]);
    exit;
}

$campaigns = json_decode(file_get_contents("../config/campaigns.json"), true) ?? [];

foreach ($campaigns as $campaign) {
    if ($campaign['id'] === $id && $campaign['status'] === "active") {
        echo json_encode([
            "target_url" => $campaign['target_url'],
            "bot_filters" => [
                "user_agent" => in_array("user_agent", $campaign['bot_filters']),
                "cloudflare" => in_array("cloudflare", $campaign['bot_filters']),
            ],
            "devices" => $campaign['devices'],
            "country" => $campaign['country'],
        ]);
        exit;
    }
}

http_response_code(404);
echo json_encode(["error" => "Campaign not found"]);
