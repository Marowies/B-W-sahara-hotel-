import {createServer} from 'node:http';
import {readFile,stat} from 'node:fs/promises';
import {fileURLToPath} from 'node:url';
import {dirname,resolve,sep,extname} from 'node:path';
import {createHash} from 'node:crypto';
const root=resolve(dirname(fileURLToPath(import.meta.url)),'..','dist');
const port=Number(process.env.PORT||8780);
const types={'.html':'text/html; charset=utf-8','.js':'text/javascript; charset=utf-8','.css':'text/css; charset=utf-8','.json':'application/json','.webp':'image/webp','.woff2':'font/woff2','.txt':'text/plain; charset=utf-8','.xml':'application/xml; charset=utf-8'};
createServer(async(req,res)=>{
 try {
  if(!['GET','HEAD'].includes(req.method)){res.writeHead(405);res.end();return;}
  const url=new URL(req.url,'http://127.0.0.1');let path=resolve(root,'.'+decodeURIComponent(url.pathname));
  if(path!==root&&!path.startsWith(root+sep)){res.writeHead(403);res.end();return;}
  if(url.pathname.startsWith('/localized/')){res.writeHead(404);res.end();return;}
  if(url.pathname.endsWith('/index.html')){res.writeHead(301,{Location:url.pathname.replace(/index\.html$/,'')+url.search});res.end();return;}
  try{if((await stat(path)).isDirectory()){
   if(!url.pathname.endsWith('/')){res.writeHead(301,{Location:url.pathname+'/'+url.search});res.end();return;}
   path=resolve(path,'index.html');
  }}catch{}
  let status=url.pathname==='/404.html'?404:200;
  try{await stat(path);}catch{path=resolve(root,'404.html');status=404;}
  const lang=['en','ar','zh'].includes(url.searchParams.get('lang'))?url.searchParams.get('lang'):'en';
  if(path.endsWith('.html')){
   const localized=resolve(root,'localized',lang,path.slice(root.length+1));
   try{await stat(localized);path=localized;}catch{}
  }
  const type=types[extname(path)]||'application/octet-stream';
  const raw=await readFile(path),etag='"'+createHash('sha256').update(raw).digest('hex').slice(0,16)+'"';
  const immutable=/\.[a-f0-9]{12}\.(?:js|css|webp)$/.test(path)||path.includes(sep+'fonts'+sep);
  const headers={'Content-Type':type,'Cache-Control':immutable?'public, max-age=31536000, immutable':'no-cache','ETag':etag,'Vary':'Accept-Encoding','X-Content-Type-Options':'nosniff'};
  if(status===200 && req.headers['if-none-match']===etag){res.writeHead(304,headers);res.end();return;}
  let body=raw;
  for(const [encoding,suffix] of [['br','.br'],['gzip','.gz']])if((req.headers['accept-encoding']||'').includes(encoding)){
   try{body=await readFile(path+suffix);headers['Content-Encoding']=encoding;break;}catch{}
  }
  headers['Content-Length']=body.length;res.writeHead(status,headers);res.end(req.method==='HEAD'?undefined:body);
 } catch {res.writeHead(500);res.end('Preview could not serve this file.');}
}).listen(port,'127.0.0.1',()=>console.log('Frontend preview: http://127.0.0.1:'+port+'/?lang=en'));
