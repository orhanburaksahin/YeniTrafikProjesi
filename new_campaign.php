<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// $_SESSION['user'] array ise email alanını al
$currentUserEmail = is_array($_SESSION['user']) ? ($_SESSION['user']['email'] ?? '') : $_SESSION['user'];

// Kampanyaları yükle
$campaigns = json_decode(file_get_contents("config/campaigns.json"), true) ?? [];
$userCampaigns = array_filter($campaigns, fn($c) => $c['user'] === $currentUserEmail);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Kampanya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="d-flex">
    <!-- Sol Menü -->
    <aside class="sidebar p-4 d-flex flex-column">
        <div class="brand mb-4">
            <div class="fw-bold fs-5 text-info">Traffic Guard</div>
            <div class="text-muted small">Güvenlik Yönetim Paneli</div>
        </div>

        <nav class="nav flex-column mb-auto">
            <a class="nav-link" href="dashboard.php">Dashboard</a>
            <a class="nav-link active" href="dashboard.php">Yeni Kampanya</a>
           <!-- <a class="nav-link" href="new_campaign.php">Yeni Kampanya Oluştur</a> -->
        </nav>

        <div class="user-box mt-auto pt-3 border-top border-secondary">
            <div class="small text-muted">Oturum açan kullanıcı</div>
            <div class="fw-semibold"><?= htmlspecialchars($currentUserEmail) ?></div>
            <a href="logout.php" class="btn btn-outline-danger btn-sm mt-3 w-100">Çıkış Yap</a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="content flex-grow-1 p-4">

        <div class="card p-4 mb-4" style="max-width: 900px;">
            <h4 class="mb-4">➕ Yeni Kampanya Oluştur</h4>

            <form method="post" action="actions/create_campaign.php" class="row g-4">

                <input type="hidden" name="user" value="<?= htmlspecialchars($currentUserEmail) ?>">

                <div class="col-md-6">
                    <label class="form-label">Kampanya Adı</label>
                    <input type="text" name="name" class="form-control" placeholder="Örn: TR Trafik Testi" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Hedef URL</label>
                    <input type="url" name="target_url" class="form-control" placeholder="https://example.com" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Ülke</label>
                    <select class="form-select" disabled>
                        <option selected>Türkiye</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Dil</label>
                    <select class="form-select" disabled>
                        <option selected>Türkçe</option>
                    </select>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Cihaz Filtresi</label>
                    <small class="d-block text-muted mb-2">Hiçbiri seçilmezse tüm cihazlar kabul edilir.</small>
                    <div class="d-flex flex-wrap gap-4">
                        <?php
                        $devices = [
                            "desktop" => "Masaüstü",
                            "mobile" => "Mobil",
                            "tablet" => "Tablet",
                            "laptop" => "Laptop"
                        ];
                        foreach ($devices as $val => $label): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="devices[]" value="<?= htmlspecialchars($val) ?>" id="dev_<?= htmlspecialchars($val) ?>">
                            <label class="form-check-label" for="dev_<?= htmlspecialchars($val) ?>">
                                <?= htmlspecialchars($label) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Bot Tespit Mekanizmaları</label>
                    <small class="d-block text-muted mb-2">Seçilmezse sistem varsayılan korumayı uygular.</small>
                    <div class="d-flex flex-wrap gap-4">
                        <?php
                        $bots = [
                            "headless" => "Otomasyon tarayıcılarını (Chrome bot, Selenium vb.) engeller.",
                            "datacenter" => "Sunucu, proxy ve VPN üzerinden gelen trafiği engeller.",
                            "ua" => "Gerçek tarayıcı gibi görünmeyen sahte tarayıcıları engeller.",
                            "behavior" => "İnsan gibi davranmayan (çok hızlı tıklayan vb.) ziyaretçileri engeller.",
                            "js_challenge" => "Tarayıcıyı test ederek gerçek kullanıcı mı bot mu olduğunu doğrular.",
                            "rate_limit" => "Aşırı istek atan IP adreslerini geçici olarak engeller.",
                            "fingerprint" => "Tarayıcı parmak izi ile aynı botu tekrar yakalar."
                        ];
                        foreach ($bots as $val => $desc): ?>
                        <div class="form-check" style="min-width: 250px;">
                            <input class="form-check-input" type="checkbox" name="bot_filters[]" value="<?= htmlspecialchars($val) ?>" id="bot_<?= htmlspecialchars($val) ?>" checked>
                            <label class="form-check-label" for="bot_<?= htmlspecialchars($val) ?>">
                                <strong><?= htmlspecialchars(ucfirst(str_replace('_',' ',$val))) ?></strong>
                                <small class="d-block text-muted"><?= htmlspecialchars($desc) ?></small>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">Kampanya Oluştur</button>
                </div>

            </form>
        </div>

        <!-- Kullanıcının mevcut kampanyaları -->
        <div class="card p-4" style="max-width: 900px;">
            <h4 class="mb-4">Mevcut Kampanyalarım</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Ad</th>
                            <th>Hedef URL</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($userCampaigns as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['name']) ?></td>
                            <td><?= htmlspecialchars($c['target_url']) ?></td>
                            <td>
                                <?php if ($c['status'] === 'pending'): ?>
                                    <span class="badge bg-warning text-dark">Bekleniyor</span>
                                <?php elseif ($c['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Error</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($userCampaigns) === 0): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">Henüz kampanya yok.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

</body>
</html>
