<?php

function generateWranglerToml(string $campaignId): string {
    $filePath = __DIR__ . "/../workers/{$campaignId}.toml";
    $scriptName = $campaignId;

    $toml = <<<TOML
name = "{$scriptName}"
main = "{$scriptName}.js"
type = "javascript"
account_id = "ffdc3fe1da8b232842d021fdca186bea"
workers_dev = true
compatibility_date = "2026-02-07"
TOML;

    file_put_contents($filePath, $toml);
    return $filePath;
}
