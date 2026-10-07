// Local preview of the REAL admin with the brand layer, without uploading anything.
// Run: node admin/preview/live-proxy.mjs  →  open http://localhost:8791/admin
// Every request is forwarded to the live site; HTML pages get bw-admin.css injected.
// Anything you save here is saved on the live site — this is the real admin, only restyled.
import {createServer} from 'node:http';
import {request,Agent} from 'node:https';
import {readFile} from 'node:fs/promises';
import {resolve,dirname,extname} from 'node:path';
import {fileURLToPath} from 'node:url';

const TARGET='bwsaharaskyhotel.com';
const PORT=Number(process.env.PORT||8791);
const LOCAL=`http://localhost:${PORT}`;
const brandDir=resolve(dirname(fileURLToPath(import.meta.url)),'../public/vendor/core/core/base/css/bw-brand');
const types={'.css':'text/css; charset=utf-8','.woff2':'font/woff2','.webp':'image/webp','.txt':'text/plain'};
// One pool of kept-alive TLS connections instead of a new handshake per request
const agent=new Agent({keepAlive:true,maxSockets:16});
// Versioned static files (vendor/theme/storage) are kept in memory after the first load
const staticCache=new Map();
const isStatic=url=>/^\/(vendor|themes|storage)\//.test(url)&&/\.(js|css|woff2?|ttf|svg|png|jpe?g|webp|gif|ico)(\?|$)/.test(url);
const rewrite=text=>text.split(`https://${TARGET}`).join(LOCAL).split(`https:\\/\\/${TARGET}`).join(LOCAL.replace(/\//g,'\\/'));
const loginPanel=/<div class="end-0 bottom-0 position-absolute">\s*<div class="text-white me-5 mb-4">\s*<h1 class="mb-1">([\s\S]*?)<\/h1>[\s\S]*?<\/p>\s*<\/div>\s*<\/div>/;

createServer(async(req,res)=>{
  // The brand layer itself is served from this project, so edits show up on refresh
  if(req.url.startsWith('/__bw/')){
    const file=resolve(brandDir,'.'+decodeURIComponent(req.url.slice(5).split('?')[0]));
    if(!file.startsWith(brandDir)){res.writeHead(403);return res.end();}
    try{
      const isCss=file.endsWith('.css');
      res.writeHead(200,{'Content-Type':types[extname(file)]||'application/octet-stream','Cache-Control':isCss?'no-cache':'public, max-age=86400'});
      return res.end(await readFile(file));
    }catch{res.writeHead(404);return res.end();}
  }
  if(req.method==='GET'&&staticCache.has(req.url)){const hit=staticCache.get(req.url);res.writeHead(200,hit.headers);return res.end(hit.body);}

  const headers={...req.headers,host:TARGET};
  if(headers.origin)headers.origin=`https://${TARGET}`;
  if(headers.referer)headers.referer=headers.referer.replace(LOCAL,`https://${TARGET}`);
  const wantsStatic=req.method==='GET'&&isStatic(req.url);
  // Pages are rewritten, so ask for them uncompressed; everything else keeps gzip/br end to end
  if(!wantsStatic)headers['accept-encoding']='identity';

  const upstream=request({host:TARGET,port:443,method:req.method,path:req.url,headers,agent},up=>{
    const out={...up.headers};
    delete out['content-security-policy'];delete out['strict-transport-security'];
    if(out.location)out.location=rewrite(out.location);
    if(out['set-cookie'])out['set-cookie']=out['set-cookie'].map(c=>c.replace(/;\s*domain=[^;]*/i,'').replace(/;\s*secure/i,'').replace(/;\s*samesite=none/i,'; SameSite=Lax'));
    const type=out['content-type']||'';
    const rewriteBody=!wantsStatic&&/text\/html|application\/json/.test(type);
    if(!rewriteBody){
      if(wantsStatic&&up.statusCode===200){
        out['cache-control']='public, max-age=86400';
        const chunks=[];up.on('data',c=>chunks.push(c));
        up.on('end',()=>{const body=Buffer.concat(chunks);staticCache.set(req.url,{headers:out,body});res.writeHead(200,out);res.end(body);});
        return;
      }
      res.writeHead(up.statusCode,out);return up.pipe(res);
    }
    delete out['content-length'];
    const chunks=[];up.on('data',c=>chunks.push(c));up.on('end',()=>{
      let body=rewrite(Buffer.concat(chunks).toString('utf8'));
      if(/text\/html/.test(type)){
        // Mirror the edited guest.blade.php (login side panel) until it is deployed
        body=body.replace(loginPanel,(_,title)=>`<div class="bw-login-brand"><h1 class="bw-login-title">${title.trim()}</h1><span class="bw-login-rule" aria-hidden="true"></span><p class="bw-login-copy">Copyright ${new Date().getFullYear()} ${title.trim()}.</p></div>`);
        body=body.replace('</head>','<link rel="preload" href="/__bw/bw-admin.css" as="style"><link rel="stylesheet" href="/__bw/bw-admin.css"></head>');
      }
      res.writeHead(up.statusCode,out);res.end(body);
    });
  });
  upstream.on('error',e=>{if(!res.headersSent)res.writeHead(502,{'Content-Type':'text/plain'});res.end('Live site unreachable: '+e.message);});
  req.pipe(upstream);
}).listen(PORT,'127.0.0.1',()=>console.log(`Admin with brand layer: ${LOCAL}/admin`));
