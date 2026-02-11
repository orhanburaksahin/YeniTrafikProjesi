<?php
session_start();
$usersFile = "../config/users.json";
$users = json_decode(file_get_contents($usersFile), true);

$action = $_POST['action'] ?? '';

if ($action === 'register') {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Kullanıcı zaten var mı kontrol et
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            die("Bu e-posta zaten kayıtlı.");
        }
    }

    // Yeni kullanıcı ekle (default role: user)
    $users[] = [
        'email' => $email,
        'password' => $password,
        'role' => 'user'
    ];

    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    header("Location: ../index.php");
    exit;
}

if ($action === 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    foreach ($users as $user) {
        if ($user['email'] === $email && password_verify($password, $user['password'])) {
            // session içine tüm kullanıcı bilgilerini kaydet
            $_SESSION['user'] = $user;

            // Role bazlı yönlendirme
            if ($user['role'] === 'admin') {
                header("Location: ../admin_dashboard.php");
            } else {
                header("Location: ../dashboard.php");
            }
            exit;
        }
    }

    die("Giriş başarısız.");
}
