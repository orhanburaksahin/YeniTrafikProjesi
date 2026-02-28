<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$currentUserEmail = is_array($_SESSION['user']) ? ($_SESSION['user']['email'] ?? '') : $_SESSION['user'];

$campaignId = $_GET['id'] ?? '';
if (!$campaignId) die("Kampanya ID belirtilmedi.");

$campaigns = json_decode(file_get_contents("config/campaigns.json"), true) ?? [];
$campaign = null;

foreach ($campaigns as $c) {
    $userEmail = is_array($c['user']) ? ($c['user']['email'] ?? '') : $c['user'];
    if ($c['id'] === $campaignId && $userEmail === $currentUserEmail) {
        $campaign = $c;
        break;
    }
}

if (!$campaign) die("Kampanya bulunamadı veya yetkiniz yok.");

// Log temizleme
if (isset($_POST['clear_logs']) && $_POST['clear_logs'] === 'yes') {
    $logs = json_decode(file_get_contents("config/traffic_logs.json"), true) ?? [];
    $filteredLogs = array_filter($logs, fn($l) => ($l['campaign_id'] ?? '') !== $campaignId);
    file_put_contents("config/traffic_logs.json", json_encode(array_values($filteredLogs), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: campaign_detail.php?id={$campaignId}");
    exit;
}

$logs = json_decode(file_get_contents("config/traffic_logs.json"), true) ?? [];
$campaignLogs = array_filter($logs, fn($l) => ($l['campaign_id'] ?? '') === $campaignId);
$campaignLogs = array_reverse($campaignLogs);

function getFlagUrl($countryCode) {
    return "https://flagcdn.com/16x12/" . strtolower($countryCode) . ".png";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($campaign['name']) ?> | Kampanya Detayı</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
<div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-shield-halved"></i></div>
                <div class="logo-text"><h2>TrafficGuard</h2><p>Admin Panel</p></div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Ana Menü</div>
            <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-pie"></i>Dashboard</a>
            <a href="new_campaign.php" class="nav-item"><i class="fas fa-plus-circle"></i>Yeni Kampanya</a>
            <a href="campaigns.php" class="nav-item active"><i class="fas fa-bullhorn"></i>Kampanyalar</a>
            <div class="nav-label">Raporlar</div>
            <a href="reports.php" class="nav-item"><i class="fas fa-chart-line"></i>İstatistikler</a>
            <a href="logs.php" class="nav-item"><i class="fas fa-history"></i>Log Kayıtları</a>
            <div class="nav-label">Sistem</div>
            <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i>Ayarlar</a>
            <a href="support.php" class="nav-item"><i class="fas fa-question-circle"></i>Destek</a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-info">
                    <div class="user-avatar"><?= strtoupper(substr($currentUserEmail, 0, 1)) ?></div>
                    <div class="user-details"><h4><?= htmlspecialchars(explode('@', $currentUserEmail)[0]) ?></h4><p><?= htmlspecialchars($currentUserEmail) ?></p></div>
                </div>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap</a>
        </div>
    </aside>

    <!-- Main -->
    <main class="main">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i></a>
                <div><h2>Kampanya Detayı</h2><p><?= htmlspecialchars($campaign['name']) ?></p></div>
            </div>
            <div class="campaign-badge"><i class="fas fa-shield"></i>ID: <?= htmlspecialchars($campaignId) ?></div>
        </div>

        <!-- Info Card -->
        <div class="info-card">
            <div class="info-grid">
                <div class="info-item"><div class="info-icon"><i class="fas fa-link"></i></div><div class="info-content"><label>Hedef URL</label><div class="url"><?= htmlspecialchars($campaign['target_url'] ?? '') ?></div></div></div>
                <div class="info-item"><div class="info-icon"><i class="fas fa-globe"></i></div><div class="info-content"><label>Worker URL</label><div class="url">https://<?= htmlspecialchars($campaignId) ?>.eragonn.workers.dev</div></div></div>
                <div class="info-item"><div class="info-icon"><i class="fas fa-calendar"></i></div><div class="info-content"><label>Oluşturulma</label><div><?= isset($campaign['created_at']) ? (is_numeric($campaign['created_at']) ? date('d.m.Y H:i', $campaign['created_at']) : htmlspecialchars($campaign['created_at'])) : '-' ?></div></div></div>
                <div class="info-item"><div class="info-icon"><i class="fas fa-shield"></i></div><div class="info-content"><label>Durum</label><div><?php if ($campaign['status'] === 'active'): ?><span style="color: #10b981;">● Aktif</span><?php elseif ($campaign['status'] === 'pending'): ?><span style="color: #f59e0b;">● Beklemede</span><?php else: ?><span style="color: #ef4444;">● Hata</span><?php endif; ?></div></div></div>
            </div>
        </div>

        <!-- Butonlar -->
        <div class="action-buttons">
            <a href="edit_campaign.php?id=<?= $campaignId ?>" class="btn-primary"><i class="fas fa-edit"></i>Kampanyayı Düzenle</a>
            <button class="btn-secondary" onclick="window.open('https://<?= $campaignId ?>.eragonn.workers.dev', '_blank')"><i class="fas fa-external-link-alt"></i>Worker'ı Test Et</button>
            <button class="btn-danger" onclick="confirmClearLogs()"><i class="fas fa-trash-alt"></i>Logları Temizle</button>
        </div>

        <!-- Log Tablosu -->
        <div class="section-header"><h3>Ziyaretçi Kayıtları</h3><span class="log-count"><?= count($campaignLogs) ?> kayıt</span></div>
        <div class="table-container" id="log-table-container">
            <?php if (count($campaignLogs) > 0): ?>
                <table>
                    <thead><tr><th>IP</th><th>Tarih</th><th>Ülke</th><th>Cihaz</th><th>Tip</th><th>Durum</th><th>Detay</th></tr></thead>
                    <tbody id="log-tbody">
                        <?php foreach ($campaignLogs as $log): 
                            $isBot = $log['is_bot'] ?? false;
                            $status = $log['status'] ?? ($isBot ? 'blocked' : 'allowed');
                            $country = $log['country'] ?? '';
                            $ua = $log['user_agent'] ?? '';
                            $device = 'Bilinmiyor';
                            if (strpos($ua, 'Windows') !== false) $device = 'Windows';
                            elseif (strpos($ua, 'Mac') !== false) $device = 'macOS';
                            elseif (strpos($ua, 'iPhone') !== false) $device = 'iPhone';
                            elseif (strpos($ua, 'iPad') !== false) $device = 'iPad';
                            elseif (strpos($ua, 'Android') !== false) $device = 'Android';
                            elseif (strpos($ua, 'Linux') !== false) $device = 'Linux';
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($log['ip'] ?? '') ?></strong></td>
                            <td><?= htmlspecialchars($log['timestamp'] ?? '') ?></td>
                            <td><?php if (!empty($country)): ?><img src="<?= getFlagUrl($country) ?>" width="16" height="12" style="margin-right: 6px; vertical-align: middle;" alt=""><?= htmlspecialchars($country) ?><?php else: ?>-<?php endif; ?></td>
                            <td><?= $device ?></td>
                            <td><?= $isBot ? '<span class="type-icon">🤖</span> Bot' : '<span class="type-icon">👤</span> İnsan' ?></td>
                            <td><?= ($status === 'blocked' || $isBot) ? '<span class="status-badge status-blocked">Engellendi</span>' : '<span class="status-badge status-allowed">İzin Verildi</span>' ?></td>
                            <td><?php if (!empty($log['filter_hit'])): $filters = explode(',', $log['filter_hit']); ?><i class="fas fa-eye eye-icon" onclick='showFilters(<?= json_encode($filters) ?>)' title="Detayları göster"></i><?php else: ?>-<?php endif; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-chart-line empty-icon"></i><h4>Henüz ziyaretçi kaydı yok</h4><p>Worker linki paylaşıldığında kayıtlar burada görünecek.</p></div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal -->
<div id="filterModal" class="modal"><div class="modal-content"><div class="modal-header"><h3><i class="fas fa-filter me-2"></i>Yakalanan Bot Filtreleri</h3><span class="close-btn" onclick="closeModal()">&times;</span></div><ul id="filterList" class="filter-list"></ul></div></div>
<div id="confirmModal" class="modal"><div class="modal-content" style="width: 400px;"><div class="modal-header"><h3><i class="fas fa-trash-alt me-2" style="color: #ef4444;"></i>Logları Temizle</h3><span class="close-btn" onclick="closeConfirmModal()">&times;</span></div><div style="padding: 20px 0; text-align: center;"><p style="margin-bottom: 20px;">Bu kampanyaya ait tüm log kayıtları silinecek. Emin misiniz?</p><form method="POST" style="display: flex; gap: 10px; justify-content: center;"><input type="hidden" name="clear_logs" value="yes"><button type="button" class="btn-secondary" onclick="closeConfirmModal()">İptal</button><button type="submit" class="btn-danger">Evet, Temizle</button></form></div></div></div>

<script>
let refreshInterval;
function showFilters(filters) {
    const names = {
        'headless': 'Headless Browser Tespiti', 'ua': 'User-Agent Anormalliği', 'datacenter': 'Veri Merkezi IP Tespiti',
        'accept_mismatch': 'Accept Mismatch', 'header_consistency': 'Header Tutarlılığı', 'navigation_token': 'Navigation Token Doğrulama',
        'behavioral_delay': 'Davranış Gecikme Kontrolü', 'advanced_js': 'Gelişmiş JavaScript Challenge',
        'fingerprint_v2_flood': 'Parmak İzi Flood', 'fingerprint_v2_rapid': 'Hızlı Parmak İzi',
        'tls_ua_chrome_mismatch': 'TLS/Chrome Uyumsuzluğu', 'tls_ua_firefox_mismatch': 'TLS/Firefox Uyumsuzluğu',
        'tls_ua_safari_mismatch': 'TLS/Safari Uyumsuzluğu', 'lang_country_mismatch': 'Dil/Ülke Uyumsuzluğu',
        'encoding_abnormal': 'Accept-Encoding Anormalliği', 'suspicious_referer': 'Şüpheli Yönlendirme',
        'header_missing_many': 'Header Eksikliği', 'country': 'Ülke Engeli', 'device': 'Cihaz Engeli'
    };
    let html = '';
    filters.forEach(f => html += `<li class="filter-item"><i class="fas fa-shield-halved"></i><span>${names[f] || f.replace(/_/g, ' ')}</span></li>`);
    document.getElementById('filterList').innerHTML = html;
    document.getElementById('filterModal').style.display = 'flex';
}
function closeModal() { document.getElementById('filterModal').style.display = 'none'; }
function confirmClearLogs() { document.getElementById('confirmModal').style.display = 'flex'; }
function closeConfirmModal() { document.getElementById('confirmModal').style.display = 'none'; }
window.onclick = function(e) {
    if (e.target === document.getElementById('filterModal')) closeModal();
    if (e.target === document.getElementById('confirmModal')) closeConfirmModal();
}

// AJAX ile canlı güncelleme
function fetchLogs() {
    console.log('AJAX isteği gönderiliyor...');
    fetch('api/get_campaign_logs.php?id=<?= $campaignId ?>')
        .then(res => {
            console.log('Cevap alındı, status:', res.status);
            return res.json();
        })
        .then(data => {
            console.log('Gelen veri:', data);
            console.log('Veri sayısı:', data.length);
            // geri kalan kod...
        })
        .catch(err => {
            console.error('AJAX hatası:', err);
        });
}

document.addEventListener('DOMContentLoaded', () => {
    refreshInterval = setInterval(fetchLogs, 5000);
});
</script>
</body>
</html>