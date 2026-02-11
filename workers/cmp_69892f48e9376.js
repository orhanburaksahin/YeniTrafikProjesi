addEventListener('fetch', event => {
    event.respondWith(handleRequest(event));
});

async function handleRequest(event) {
    const request = event.request;
    const userAgent = request.headers.get('user-agent') || '';
    const cf = request.cf || {};
    const device = cf.deviceType || 'desktop';

    let isBot = false;
    let detectionReason = null;
    const botFilters = ["headless","datacenter","ua"];
    const devices = ["desktop"];

    if (botFilters.includes("headless") && /HeadlessChrome/i.test(userAgent)) {
        isBot = true;
        detectionReason = "Headless tarayıcı tespiti";
    }

    if (devices.length && !devices.includes(device)) {
        isBot = true;
        detectionReason = "Cihaz uyumsuzluğu";
    }

    // Log gönderme fonksiyonu
    async function logVisit(status, reason = null) {
        try {
            await fetch("https://gonderisorgula.xyz/api/log_visit.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    campaign_id: "cmp_69892f48e9376",
                    ip: request.headers.get("cf-connecting-ip") || "",
                    user_agent: userAgent,
                    status: status,
                    detection_reason: reason
                })
            });
        } catch (e) {
            // Sessiz geçiyoruz
        }
    }

    if (isBot) {
        await logVisit("blocked", detectionReason);
        return new Response("Bot erişimi engellendi", { status: 403 });
    }

    await logVisit("allowed", null);
    return Response.redirect("https:\/\/pazarama.com", 302);
}