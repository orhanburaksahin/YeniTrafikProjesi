<?php

class WorkerDeployer {
    private $apiToken;
    private $accountId;

    public function __construct() {
        // Cloudflare config dosyasından al
        $config = include __DIR__ . '/../config/cloudflare.php';
        $this->apiToken = $config['api_token'];
        $this->accountId = $config['account_id'];
    }

    /**
     * Mevcut deploy fonksiyonunuzu buraya alıyoruz, değiştirmiyoruz
     * Örneğin create_campaign.php bunu kullanıyor
     */
    public function deploy($workerName, $workerCode) {
        $url = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/workers/scripts/{$workerName}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$this->apiToken}",
            "Content-Type: application/javascript"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $workerCode);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode >= 400) {
            throw new Exception("Cloudflare Worker deploy edilemedi: HTTP $httpcode, Response: $response");
        }

        return true;
    }

    /**
     * Yeni eklenen deleteWorker fonksiyonu
     */
    public function deleteWorker($workerName) {
        $url = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/workers/scripts/{$workerName}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$this->apiToken}",
            "Content-Type: application/javascript"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode >= 400) {
            throw new Exception("Cloudflare Worker silinemedi: HTTP $httpcode, Response: $response");
        }

        return true;
    }
}
