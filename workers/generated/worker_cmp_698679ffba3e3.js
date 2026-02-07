export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);
    const userAgent = request.headers.get("user-agent") || "";
    const ip = request.headers.get("cf-connecting-ip") || "";
    const country = request.headers.get("cf-ipcountry") || "XX";
    const device = request.headers.get("sec-ch-ua-mobile") || "unknown";

    // === CONFIG (SYSTEM WILL REPLACE THESE) ===
    const TARGET_URL = "https://google.com";
    const ALLOWED_COUNTRY = "TR";
    const ENABLED_BOT_FILTERS = [];
    const ENABLED_DEVICES = ["mobile"];

    // === BOT DETECTION ===
    function isBot() {
      if (ENABLED_BOT_FILTERS.includes("headless") && /HeadlessChrome|puppeteer|selenium|phantomjs/i.test(userAgent)) {
        return true;
      }

      if (ENABLED_BOT_FILTERS.includes("ua") && userAgent.length < 40) {
        return true;
      }

      if (ENABLED_BOT_FILTERS.includes("datacenter") && request.cf?.asOrganization?.toLowerCase().includes("amazon")) {
        return true;
      }

      if (ENABLED_BOT_FILTERS.includes("behavior")) {
        // placeholder — gerçek davranış analizi sonra eklenecek
      }

      return false;
    }

    // === DEVICE FILTER ===
    function isAllowedDevice() {
      if (ENABLED_DEVICES.length === 0) return true;

      const isMobile = /Mobi|Android/i.test(userAgent);
      const isTablet = /Tablet|iPad/i.test(userAgent);
      const isDesktop = !isMobile && !isTablet;

      if (isMobile && ENABLED_DEVICES.includes("mobile")) return true;
      if (isTablet && ENABLED_DEVICES.includes("tablet")) return true;
      if (isDesktop && ENABLED_DEVICES.includes("desktop")) return true;

      return false;
    }

    // === COUNTRY FILTER ===
    if (country !== ALLOWED_COUNTRY) {
      return new Response("Access denied.", { status: 403 });
    }

    // === BOT CHECK ===
    if (isBot()) {
      return new Response("Bot traffic blocked.", { status: 403 });
    }

    // === DEVICE CHECK ===
    if (!isAllowedDevice()) {
      return new Response("Device not allowed.", { status: 403 });
    }

    // === REDIRECT REAL USERS ===
    return Response.redirect(TARGET_URL, 302);
  }
};
