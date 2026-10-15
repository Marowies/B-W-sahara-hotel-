// The preview server allows 40 documents per minute per client (tools/traffic-limit.mjs).
// Browser suites that open many pages space their document requests out instead of failing
// on a 429 page. Client-side navigations fetch documents too, so pace at the network level.
const isDocument = request => {
  if (request.method() !== 'GET') return false;
  const {pathname} = new URL(request.url());
  return !/\.[a-z0-9]+$/i.test(pathname) && !/^\/(?:api|storage|vendor)\//.test(pathname);
};

// Redirects such as /?lang=en -> / reach the server as a second, unrouted document, so the gap allows for two.
export async function paceDocuments(context, {gapMs = 3200} = {}) {
  let last = 0, queue = Promise.resolve();
  await context.route('**/*', route => {
    if (!isDocument(route.request())) return route.continue();
    queue = queue.then(async () => {
      const wait = last + gapMs - Date.now();
      if (wait > 0) await new Promise(resolve => setTimeout(resolve, wait));
      last = Date.now();
    });
    return queue.then(() => route.continue());
  });
}
