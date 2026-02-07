export default {
  async fetch(request, env, ctx) {
    try {
      
      const ua = request.headers.get("User-Agent") || "";
let deviceAllowed = false;if (/windows|macintosh|linux/i.test(ua)) deviceAllowed = true;
if (!deviceAllowed) {
    return new Response("Device not allowed.", { status: 403 });
}

      return Response.redirect("https://google.com", 302);
    } catch (err) {
      return new Response("Worker error: " + err.message, { status: 500 });
    }
  }
};