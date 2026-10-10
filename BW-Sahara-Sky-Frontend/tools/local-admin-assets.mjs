import { readFile, realpath, stat } from 'node:fs/promises';
import { resolve, sep, extname } from 'node:path';
import { createHash } from 'node:crypto';
import { gzip } from 'node:zlib';
import { promisify } from 'node:util';
const zip = promisify(gzip);
const types = {'.js':'text/javascript; charset=utf-8','.css':'text/css; charset=utf-8','.svg':'image/svg+xml','.png':'image/png','.jpg':'image/jpeg','.jpeg':'image/jpeg','.webp':'image/webp','.gif':'image/gif','.ico':'image/x-icon','.woff':'font/woff','.woff2':'font/woff2','.ttf':'font/ttf','.json':'application/json','.map':'application/json'};
const cache = new Map();
let cachedBytes = 0;
const budget = 32 * 1024 * 1024;
function gzipAllowed(header = '') {
 const entries = header.toLowerCase().split(',').map(part => {
  const [name,...args] = part.trim().split(';');
  const quality = args.find(value=>value.trim().startsWith('q='));
  return [name, quality ? Number(quality.trim().slice(2)) : 1];
 });
 return (entries.find(([name])=>name==='gzip')?.[1] ?? entries.find(([name])=>name==='*')?.[1] ?? 0) > 0;
}
// Only existing public asset files are cacheable. Admin/API responses never enter
// this handler. Resolve real paths to reject traversal and escaping symlinks.
export async function serveAdminAsset(req, res, publicRoot) {
 if (!['GET','HEAD'].includes(req.method)) return false;
 const url = new URL(req.url, 'http://127.0.0.1');
 const prefix = ['/vendor/','/storage/'].find(value=>url.pathname.startsWith(value));
 if (!prefix) return false;
 if (!types[extname(url.pathname).toLowerCase()]) return false;
 const missing = () => {res.writeHead(404,{'Content-Type':'text/plain','Cache-Control':'no-store'});res.end('Asset not found.');return true;};
 let file, info;
 try {
  const root = await realpath(resolve(publicRoot, '.' + prefix));
  file = await realpath(resolve(root, '.' + decodeURIComponent(url.pathname.slice(prefix.length - 1))));
  if (!file.startsWith(root + sep) || !types[extname(file).toLowerCase()]) return missing();
  info = await stat(file);
  if (!info.isFile()) return missing();
  if (info.size > 8 * 1024 * 1024) return false;
 } catch { return missing(); }
 const version = `${info.mtimeMs}:${info.size}`;
 let entry = cache.get(file);
 if (entry?.version !== version) {
  if (entry) {cachedBytes -= entry.bytes;cache.delete(file);}
  const raw = await readFile(file);
  const zipped = raw.length >= 1024 && /\.(?:js|css|svg|json|map)$/i.test(file) ? await zip(raw) : null;
  entry = {version,raw,zipped,etag:`W/"${createHash('sha256').update(raw).digest('hex')}"`,bytes:raw.length + (zipped?.length || 0)};
  if (entry.bytes <= budget / 4) {
   while (cache.size && (cachedBytes + entry.bytes > budget || cache.size >= 128)) {
    const key = cache.keys().next().value;cachedBytes -= cache.get(key).bytes;cache.delete(key);
   }
   cache.set(file,entry);cachedBytes += entry.bytes;
  }
 }
 const headers = {'Content-Type':types[extname(file).toLowerCase()],'Cache-Control':'public, max-age=3600','ETag':entry.etag,'Vary':'Accept-Encoding','X-Content-Type-Options':'nosniff'};
 if ((req.headers['if-none-match'] || '').split(',').map(v=>v.trim()).some(v=>v===entry.etag || v==='*')) {
  res.writeHead(304,headers);res.end();return true;
 }
 const compressed = entry.zipped && gzipAllowed(req.headers['accept-encoding']);
 const body = compressed ? entry.zipped : entry.raw;
 if (compressed) headers['Content-Encoding'] = 'gzip';
 headers['Content-Length'] = body.length;
 res.writeHead(200,headers);res.end(req.method === 'HEAD' ? undefined : body);return true;
}
