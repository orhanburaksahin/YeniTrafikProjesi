<?php
$input = json_decode(file_get_contents("php://input"), true);
$file = "../config/logs.json";

$logs = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
$logs[] = $input;

file_put_contents($file, json_encode($logs, JSON_PRETTY_PRINT));
http_response_code(200);
