addEventListener('fetch', function(event) {
  var request = event.request;
  var userAgent = request.headers.get('user-agent') || '';

  if (!/windows|macintosh|linux/i.test(userAgent)) {
    event.respondWith(new Response('Device not allowed', {status: 403}));
    return;
  }
  event.respondWith(Response.redirect('https://pazarama.com', 302));
});