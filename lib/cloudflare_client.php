<?php

class CloudflareClient {
    public string $apiToken;
    public string $accountId;

    public function __construct() {
        $config = include __DIR__ . '/../config/cloudflare.php';
        $this->apiToken = $config['api_token'];
        $this->accountId = $config['account_id'];
    }

    // Genel Cloudflare API çağrısı
    public function rawRequest(string $method, string $endpoint, $body = null, array $headers = []) {
        $url = "https://api.cloudflare.com/client/v4" . $endpoint;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $defaultHeaders = [
            "Authorization: Bearer {$this->apiToken}",
            "Accept: application/json",
        ];

        foreach ($headers as $k => $v) {
            $defaultHeaders[] = "{$k}: {$v}";
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $defaultHeaders);

        if ($body !== null) {
            if (is_array($body)) $body = json_encode($body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new Exception("cURL Error: $err");
        }

        $result = json_decode($response, true);
        if (!$result['success']) {
            throw new Exception("Cloudflare API Error: " . json_encode($result));
        }

        return $result;
    }

    // Route oluştur
    public function createRoute(string $scriptName, string $subdomain) {
        // Ücretsiz plan: preview_url aktif et
        $endpoint = "/accounts/{$this->accountId}/workers/routes";
        $body = [
            "pattern" => "{$subdomain}.eragonn.workers.dev/*",
            "script" => $scriptName
        ];

        return $this->rawRequest("POST", $endpoint, $body, ["Content-Type" => "application/json"]);
    }

    // Worker deploy
    public function deployWorker(string $scriptName, string $scriptContent) {
        return $this->rawRequest(
            "PUT",
            "/accounts/{$this->accountId}/workers/scripts/{$scriptName}",
            $scriptContent,
            ["Content-Type" => "application/javascript"]
        );
    }
}
