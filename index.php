

<?php
session_start();

// Kullanıcı zaten giriş yapmışsa
if (isset($_SESSION['user'])) {
    // Kullanıcı admin mi?
    if ($_SESSION['user']['role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit;
    } else {
        header("Location: dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="d-flex align-items-center justify-content-center vh-100">
<div class="card p-5" style="width: 380px;">
    <h3 class="mb-4 text-center">🔐 Panel Girişi</h3>
    <form method="post" action="actions/auth.php">
        <input type="hidden" name="action" value="login">
        <div class="mb-3">
            <input type="email" name="email" class="form-control" placeholder="E-posta adresi" required>
        </div>
        <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="Şifre" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
    </form>
    <div class="text-center mt-3">
        <a href="register.php">Hesabın yok mu? Kayıt Ol</a>
    </div>
</div>
</body>
</html>
