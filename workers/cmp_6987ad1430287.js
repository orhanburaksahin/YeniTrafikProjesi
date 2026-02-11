(function() {
        addEventListener('fetch', event => {
            const request = event.request;
            const userAgent = request.headers.get('user-agent') || '';
            const cf = request.cf || {};
            const device = cf.deviceType || 'desktop';

            let isBot = false;
            const botFilters = ["headless"];
            const devices = ["desktop"];

            if (botFilters.includes('headless') && /HeadlessChrome/i.test(userAgent)) {
                isBot = true;
            }

            if (devices.length && !devices.includes(device)) {
                isBot = true;
            }

            if (isBot) {
                return event.respondWith(new Response('Bot detected', { status: 403 }));
            }

            return event.respondWith(Response.redirect("https://pazarama.com", 302));
        });
    })();