import {createServer,request as backendRequest} from 'node:http';
import {readFile,stat} from 'node:fs/promises';
import {fileURLToPath} from 'node:url';
import {dirname,resolve,sep,extname} from 'node:path';
import {createHash} from 'node:crypto';
import {localeRedirect} from '../src/shared/locale-resolver.mjs';
import {resolveRoute} from '../src/shared/locale.mjs';
import {serveAdminAsset} from './local-admin-assets.mjs';
const root=resolve(dirname(fileURLToPath(import.meta.url)),'..','dist');
const port=Number(process.env.PORT||8780);
const backend=process.env.HOTEL_BACKEND_ORIGIN?new URL(process.env.HOTEL_BACKEND_ORIGIN):null;
if(backend&&(backend.protocol!=='http:'||backend.hostname!=='127.0.0.1'))throw new Error('Local integration requires a loopback HTTP backend');
const types={'.html':'text/html; charset=utf-8','.js':'text/javascript; charset=utf-8','.css':'text/css; charset=utf-8','.json':'application/json','.webp':'image/webp','.woff2':'font/woff2','.txt':'text/plain; charset=utf-8','.md':'text/markdown; charset=utf-8','.xml':'application/xml; charset=utf-8'};
function acceptedEncodings(header=''){
 const entries=header.toLowerCase().split(',').map(part=>{const [encoding,...parameters]=part.trim().split(';');const q=parameters.find(p=>p.trim().startsWith('q='));return [encoding,q?Number(q.trim().slice(2)):1];});
 const quality=encoding=>entries.find(([name])=>name===encoding)?.[1]??entries.find(([name])=>name==='*')?.[1]??0;
 return ['br','gzip'].filter(encoding=>quality(encoding)>0).sort((a,b)=>quality(b)-quality(a));
}
createServer(async(req,res)=>{
 try {
  const incoming=new URL(req.url,'http://127.0.0.1');
  if(backend && await serveAdminAsset(req,res,process.env.HOTEL_BACKEND_PUBLIC||resolve(root,'../../BW-Sahara-Sky-Hotel/public_html/public')))return;
  if(backend&&incoming.pathname==='/login/admin'){res.writeHead(302,{Location:'/admin/login','Cache-Control':'no-store'});res.end();return;}
  if(backend&&/^\/(?:admin(?:\/|$)|api\/hotel(?:\/|$)|vendor\/|storage\/)/.test(incoming.pathname)){
   const headers={...req.headers,host:'127.0.0.1:'+port};delete headers['connection'];
   const target=new URL(backend);target.pathname=incoming.pathname;target.search=incoming.search;
   const upstream=backendRequest(target,{method:req.method,headers},response=>{
    const responseHeaders={...response.headers,'cache-control':'no-store'};delete responseHeaders['connection'];
    res.writeHead(response.statusCode||502,responseHeaders);response.pipe(res);
   });upstream.setTimeout(30000,()=>upstream.destroy());upstream.on('error',()=>{if(!res.headersSent)res.writeHead(502,{'Content-Type':'text/plain','Cache-Control':'no-store'});res.end('Local backend is unavailable.');});req.on('aborted',()=>upstream.destroy());req.pipe(upstream);return;
  }
  if(!['GET','HEAD'].includes(req.method)){res.writeHead(405);res.end();return;}
  const redirect=localeRedirect(incoming,new Headers(Object.entries(req.headers).map(([k,v])=>[k,Array.isArray(v)?v.join(','):v||''])),{method:req.method,trustedCountryHeader:false});
  if(redirect){res.writeHead(redirect.status,{Location:redirect.location,'Cache-Control':'no-store',Vary:'Cookie, User-Agent'});res.end();return;}
  const url=new URL(req.url,'http://127.0.0.1');let path=resolve(root,'.'+decodeURIComponent(url.pathname));
  if(path!==root&&!path.startsWith(root+sep)){res.writeHead(403);res.end();return;}
  if(url.pathname.startsWith('/localized/')){res.writeHead(404);res.end();return;}
  if(url.pathname.endsWith('/index.html')){res.writeHead(301,{Location:url.pathname.replace(/index\.html$/,'')+url.search});res.end();return;}
  try{if((await stat(path)).isDirectory()){
   if(!url.pathname.endsWith('/')){res.writeHead(301,{Location:url.pathname+'/'+url.search});res.end();return;}
   path=resolve(path,'index.html');
  }}catch{}
  let status=resolveRoute(url.pathname)?.id==='404'?404:200;
  try{await stat(path);}catch{path=resolve(root,'404.html');status=404;}
  const type=types[extname(path)]||'application/octet-stream';
  let raw=await readFile(path);
  if(backend&&path.endsWith('.html')&&!/<meta\b[^>]*name=["']hotel-backend["']/i.test(raw.toString()))raw=Buffer.from(raw.toString().replace('</head>','<meta name="hotel-backend" content="same-origin"></head>'));
  const etag='W/"'+createHash('sha256').update(raw).digest('hex').slice(0,16)+'"';
  const immutable=/\.[a-f0-9]{12}\.(?:js|css|webp)$/.test(path)||path.includes(sep+'fonts'+sep);
  const headers={'Content-Type':type,'Cache-Control':immutable?'public, max-age=31536000, immutable':'no-cache','ETag':etag,'Vary':path.endsWith('.html')?'Accept-Encoding, Cookie, User-Agent':'Accept-Encoding','X-Content-Type-Options':'nosniff'};
  if(status===200 && req.headers['if-none-match']===etag){res.writeHead(304,headers);res.end();return;}
  let body=raw;
  for(const encoding of acceptedEncodings(req.headers['accept-encoding']))if(!(backend&&path.endsWith('.html'))){
   const suffix=encoding==='br'?'.br':'.gz';
   try{body=await readFile(path+suffix);headers['Content-Encoding']=encoding;break;}catch{}
  }
  headers['Content-Length']=body.length;res.writeHead(status,headers);res.end(req.method==='HEAD'?undefined:body);
 } catch {res.writeHead(500);res.end('Preview could not serve this file.');}
}).listen(port,'127.0.0.1',()=>console.log('Frontend preview: http://127.0.0.1:'+port+'/'));
