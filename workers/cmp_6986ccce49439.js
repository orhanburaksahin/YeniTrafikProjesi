addEventListener("fetch", function(event) {
    event.respondWith(handleRequest(event.request));
});

async function handleRequest(request) {
    var userAgent = request.headers.get("user-agent") || "";
    var isBot = false;

    var botFilters = ["datacenter","ua"];
    var allowedDevices = ["desktop"];

    var device = /Mobi|Android/i.test(userAgent) ? "mobile" : "desktop";

    if (botFilters.indexOf("headless") !== -1 && /HeadlessChrome/i.test(userAgent)) {
        isBot = true;
    }

    if (allowedDevices.length > 0 && allowedDevices.indexOf(device) === -1) {
        return new Response("Device not allowed", {status: 403});
    }

    return Response.redirect("https://pazarama.com", 302);
}