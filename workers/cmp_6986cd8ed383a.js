addEventListener("fetch", event => {
    event.respondWith(handleRequest(event.request))
})

async function handleRequest(request) {
    const url = new URL(request.url)
    const userAgent = request.headers.get("user-agent") || ""
    const country = request.headers.get("cf-ipcountry") || "XX"

    const TARGET_URL = "https://pazarama.com"
    const ALLOWED_COUNTRY = "TR"
    const ENABLED_BOT_FILTERS = ["headless","ua"]
    const ENABLED_DEVICES = ["desktop","mobile"]

    function isBot() {
        if (ENABLED_BOT_FILTERS.includes("headless") && /HeadlessChrome/i.test(userAgent)) return true
        if (ENABLED_BOT_FILTERS.includes("ua") && userAgent.length < 40) return true
        return false
    }

    function isAllowedDevice() {
        if (ENABLED_DEVICES.length === 0) return true
        const isMobile = /Mobi|Android/i.test(userAgent)
        const isTablet = /Tablet|iPad/i.test(userAgent)
        const isDesktop = !isMobile && !isTablet
        if (isMobile && ENABLED_DEVICES.includes("mobile")) return true
        if (isTablet && ENABLED_DEVICES.includes("tablet")) return true
        if (isDesktop && ENABLED_DEVICES.includes("desktop")) return true
        return false
    }

    if (country !== ALLOWED_COUNTRY) return new Response("Access denied.", {status: 403})
    if (isBot()) return new Response("Bot traffic blocked.", {status: 403})
    if (!isAllowedDevice()) return new Response("Device not allowed.", {status: 403})

    return Response.redirect(TARGET_URL, 302)
}