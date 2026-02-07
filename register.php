<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="d-flex align-items-center justify-content-center vh-100">
<div class="card p-5" style="width: 400px;">
    <h3 class="mb-4 text-center">📝 Yeni Hesap Oluştur</h3>
    <form method="post" action="actions/auth.php">
        <input type="hidden" name="action" value="register">
        <div class="mb-3">
            <input type="email" name="email" class="form-control" placeholder="E-posta adresi" required>
        </div>
        <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="Şifre" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
    </form>
    <div class="text-center mt-3">
        <a href="index.php">Zaten hesabın var mı? Giriş Yap</a>
    </div>
</div>
</body>
</html>
