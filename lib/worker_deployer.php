<?php
require_once __DIR__ . "/cloudflare_client.php";

class WorkerDeployer {
    private CloudflareClient $cf;

    public function __construct() {
        $this->cf = new CloudflareClient();
    }

    public function deploy(string $campaignId, string $workerFilePath): string {
        $scriptName = $campaignId;
        $scriptContent = file_get_contents($workerFilePath);

        if (!$scriptContent) {
            throw new Exception("Worker dosyası okunamadı: $workerFilePath");
        }

        $this->cf->deployWorker($scriptName, $scriptContent);

        // Preview URL döndür
        return $scriptName . ".workers.dev";
    }

    /**
     * Preview URL'yi aktif et
     */
    public function enablePreview(string $campaignId): array {
        try {
            $result = $this->cf->enablePreview($campaignId);
            return ['success' => true, 'result' => $result];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
