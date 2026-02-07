<?php

function generateWorkerScript(string $campaignId, string $targetUrl, array $botFilters, array $devices): string {
    $botFiltersJson = json_encode($botFilters);
    $devicesJson = json_encode($devices);
    $targetUrlJs = json_encode($targetUrl);

    return <<<JS
addEventListener('fetch', event => {
    const request = event.request;
    const userAgent = request.headers.get('user-agent') || '';
    const cf = request.cf || {};
    const device = cf.deviceType || 'desktop';

    let isBot = false;
    const botFilters = $botFiltersJson;
    const devices = $devicesJson;

    if (botFilters.includes("headless") && /HeadlessChrome/i.test(userAgent)) {
        isBot = true;
    }

    if (devices.length && !devices.includes(device)) {
        isBot = true;
    }

    if (isBot) {
        return event.respondWith(new Response('Bot detected', { status: 403 }));
    }

    return event.respondWith(Response.redirect($targetUrlJs, 302));
});
JS;
}
