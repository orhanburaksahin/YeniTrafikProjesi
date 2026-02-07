addEventListener("fetch", event => {
    event.respondWith(handleRequest(event.request));
});

async function handleRequest(request) {
    const userAgent = request.headers.get("user-agent") || "";

    let isBot = false;

    const botFilters = ["headless","ua","js_challenge"];
    const allowedDevices = ["desktop"];

    // Basit bot kontrolü
    if (botFilters.includes("headless") && /HeadlessChrome/i.test(userAgent)) {
        isBot = true;
    }

    // Basit cihaz kontrolü (User-Agent üzerinden)
    const device = /Mobi|Android/i.test(userAgent) ? "mobile" :
                   /iPad|Tablet/i.test(userAgent) ? "tablet" : "desktop";

    if (allowedDevices.length > 0 && !allowedDevices.includes(device)) {
        return new Response("Device not allowed", {status: 403});
    }

    // Eğer bot değilse yönlendir
    const targetUrl = "https://pazarama.com";
    return Response.redirect(targetUrl, 302);
}