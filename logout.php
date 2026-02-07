<?php
session_start();

// Tüm session verilerini temizle
$_SESSION = [];

// Session’u tamamen yok et
session_destroy();

// Kullanıcıyı giriş sayfasına yönlendir
header('Location: index.php');
exit;
