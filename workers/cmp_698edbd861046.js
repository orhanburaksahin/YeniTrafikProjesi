addEventListener("fetch", event => {
  event.respondWith(handleRequest(event.request))
})

async function handleRequest(request) {

  const url = new URL(request.url)
  const method = request.method
  const userAgent = request.headers.get("user-agent") || ""
  const ip = request.headers.get("cf-connecting-ip") || ""
  const country = request.headers.get("cf-ipcountry") || "XX"
  const accept = request.headers.get("accept") || ""
  const acceptLang = request.headers.get("accept-language") || ""
  const secChUa = request.headers.get("sec-ch-ua") || ""
  const referer = request.headers.get("referer") || ""
  const cookie = request.headers.get("cookie") || ""
  const secFetchDest = request.headers.get("sec-fetch-dest") || ""
  const secFetchMode = request.headers.get("sec-fetch-mode") || ""

  const TARGET_URL = "https:\/\/pazarama.com"
  const ALLOWED_COUNTRY = "TR"
  const ENABLED_BOT_FILTERS = ["headless","datacenter","ua","behavior","js_challenge","rate_limit","fingerprint"]
  const ENABLED_DEVICES = ["desktop"]
  const CAMPAIGN_ID = "cmp_698edbd861046"
  const LOG_ENDPOINT = "https:\/\/gonderisorgula.xyz\/api\/log_visit.php"
  const WORKER_SECRET = "cok_guclu_uzun_random_bir_secret_123XYZ"

  let detectedBy = null
  let logSent = false

  async function sendLog(isBot, filterHit = null) {
    if (logSent) return
    logSent = true

    try {
      await fetch(LOG_ENDPOINT, {
        method: "POST",
        headers: { 
          "Content-Type": "application/json",
          "X-Worker-Secret": WORKER_SECRET
        },
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

    if (ENABLED_BOT_FILTERS.includes("accept_mismatch")) {
      if (!accept.includes("text/html")) {
        detectedBy = "accept_mismatch"
        return true
      }
    }

    if (ENABLED_BOT_FILTERS.includes("header_consistency")) {
      if (!accept || !acceptLang || !secChUa) {
        detectedBy = "header_consistency"
        return true
      }
    }
	
	if (ENABLED_BOT_FILTERS.includes("navigation_token")) {
	  const token = url.searchParams.get("nv")
	  if (!token) {
		const newUrl = new URL(request.url)
		newUrl.searchParams.set("nv", crypto.randomUUID())
		return Response.redirect(newUrl.toString(), 302)
	  }
	}
	
	if (ENABLED_BOT_FILTERS.includes("behavioral_delay")) {
	  if (!globalThis.__delayMap) globalThis.__delayMap = {}
	  const now = Date.now()
	  if (globalThis.__delayMap[ip] && now - globalThis.__delayMap[ip] < 300) {
		detectedBy = "behavioral_delay"
		return true
	  }
	  globalThis.__delayMap[ip] = now
	}
	
	if (ENABLED_BOT_FILTERS.includes("advanced_js")) {
	  if (!cookie.includes("adv_verified=true")) {
		return new Response(
		  "<html><body><script>setTimeout(()=>{document.cookie='adv_verified=true; path=/'; location.reload();},300);</script></body></html>",
		  { headers: { "Content-Type": "text/html" } }
		)
	  }
	}
	
	if (ENABLED_BOT_FILTERS.includes("fingerprint_v2")) {
	  const fp = userAgent + accept + acceptLang + secChUa
	  if (!globalThis.__fpv2) globalThis.__fpv2 = {}
	  if (globalThis.__fpv2[fp]) {
		detectedBy = "fingerprint_v2"
		return true
	  }
	  globalThis.__fpv2[fp] = true
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
    if (isDesktop && ENABLED_DEVICES.includes("desktop")) return true
    return false
  }

  // ✅ SADECE GERÇEK SAYFA NAVIGATION LOG ATAR
  const isRealPageVisit =
    method === "GET" &&
    accept.includes("text/html") &&
    (secFetchDest === "document" || secFetchMode === "navigate")

  if (country !== ALLOWED_COUNTRY) {
    await sendLog(true, "country")
    return new Response("Access denied.", { status: 403 })
  }

  const botResult = isBot()
  if (botResult === true) {
    await sendLog(true, detectedBy)
    return new Response("Bot traffic blocked.", {
      status: 403,
      headers: { "X-Bot-Detected-By": detectedBy || "unknown" }
    })
  }

  if (!isAllowedDevice()) {
    await sendLog(true, "device")
    return new Response("Device not allowed.", { status: 403 })
  }

  // 🔥 İnsan logu TEK KERE atılacak şekilde revize edildi
  if (isRealPageVisit) {
    const hasNavToken = !ENABLED_BOT_FILTERS.includes("navigation_token") 
      || url.searchParams.get("nv")

    const hasAdvancedCookie = !ENABLED_BOT_FILTERS.includes("advanced_js") 
      || cookie.includes("adv_verified=true")

    if (hasNavToken && hasAdvancedCookie) {
      await sendLog(false, null)
    }
  }

  return Response.redirect(TARGET_URL, 302)

}