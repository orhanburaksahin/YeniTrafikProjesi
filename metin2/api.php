<?php
header('Content-Type: application/json');

$file = 'bosses.json';

// Dosya yoksa oluştur
if(!file_exists($file)){
    file_put_contents($file, json_encode([]));
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;
$data = json_decode(file_get_contents($file), true);

if($action === 'add'){
    $name = $_POST['name'] ?? '';
    $interval = $_POST['interval'] ?? '';
    $start_time = $_POST['start_time'] ?? '';

    if($name && $interval && $start_time){
        $data[] = [
            'name' => $name,
            'interval' => $interval,
            'start_time' => $start_time
        ];
        file_put_contents($file, json_encode($data));
    }
} elseif($action === 'delete'){
    $index = $_POST['index'] ?? null;
    if(isset($index) && isset($data[$index])){
        array_splice($data, $index, 1);
        file_put_contents($file, json_encode($data));
    }
}

echo json_encode($data);
