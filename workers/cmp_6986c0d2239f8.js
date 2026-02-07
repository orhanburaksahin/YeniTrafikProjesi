addEventListener("fetch", event => {
    event.respondWith(handleRequest(event.request));
});

async function handleRequest(request) {
    const url = new URL(request.url);
    const userAgent = request.headers.get("User-Agent") || "";
    const cf = request.cf || {};
    const device = cf.deviceType || "desktop";

    let isBot = false;

    const botFilters = JSON.parse('["headless"]');
    const devices = JSON.parse('["desktop"]');

    if (botFilters.includes("headless") && /HeadlessChrome/i.test(userAgent)) {
        isBot = true;
    }

    if (devices.length && !devices.includes(device)) {
        isBot = true;
    }

    const targetUrl = "https://pazarama.com";

    return Response.redirect(targetUrl, 302);
}