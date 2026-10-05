import {createRequire} from 'node:module';
import {readFile,writeFile,mkdir,stat} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {createServer} from 'node:http';
import {gzipSync,brotliCompressSync} from 'node:zlib';
const require=createRequire(import.meta.url),{chromium}=require('playwright');
const root=resolve(dirname(fileURLToPath(import.meta.url)),'..','dist');
const manifest=JSON.parse(await readFile(resolve(root,'build-manifest.json'),'utf8'));
// Only original build files are served here; snapshots cannot feed into their own generation.
const server=createServer(async(req,res)=>{
 try {
  const url=new URL(req.url,'http://127.0.0.1'),path=resolve(root,'.'+url.pathname);
  if(!path.startsWith(root) || url.pathname.includes('..')){res.writeHead(403);return res.end();}
  const file=(await stat(path)).isDirectory()?resolve(path,'index.html'):path;
  res.setHeader('Content-Type',file.endsWith('.js')?'text/javascript':file.endsWith('.css')?'text/css':file.endsWith('.html')?'text/html; charset=utf-8':'application/octet-stream');
  res.end(await readFile(file));
 } catch {res.writeHead(404);res.end();}
});
await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
let browser;
try {
 browser=await chromium.launch({headless:true,...(process.env.HOTEL_BROWSER_PATH?{executablePath:process.env.HOTEL_BROWSER_PATH}:{}),args:process.env.HOTEL_BROWSER_ARGS?JSON.parse(process.env.HOTEL_BROWSER_ARGS):['--use-angle=swiftshader','--enable-unsafe-swiftshader']});
 const page=await browser.newPage({viewport:{width:1280,height:800},serviceWorkers:'block'});
 // No external provider or production page is fetched during rendering.
 await page.route('**/*',route=>new URL(route.request().url()).hostname==='127.0.0.1'?route.continue():route.abort());
 let count=0;
 for(const file of manifest.routes)for(const lang of ['en','ar','zh']){
  await page.goto(`http://127.0.0.1:${server.address().port}${file}?lang=${lang}`,{waitUntil:'load'});
  await page.waitForFunction(()=>typeof window.hotelUpdateSeo==='function'&&document.querySelector('main h1'));
  const html=await page.evaluate(()=>{
   const copy=document.documentElement.cloneNode(true);
   // These are reconstructed by the existing page scripts, avoiding duplicate dialogs on hydration.
   if(document.body.dataset.page!=='home')copy.querySelectorAll('dialog').forEach(node=>node.remove());
   copy.querySelectorAll('script[src]').forEach(node=>node.setAttribute('defer',''));
   return '<!doctype html>'+copy.outerHTML;
  });
  const destination=resolve(root,'localized',lang,'.'+file);
  await mkdir(dirname(destination),{recursive:true});
  await writeFile(destination,html);await writeFile(destination+'.gz',gzipSync(html));await writeFile(destination+'.br',brotliCompressSync(html));count++;
 }
 // English is the default when no language query is present.
 for(const file of manifest.routes)for(const suffix of ['','.gz','.br'])await writeFile(resolve(root,'.'+file+suffix),await readFile(resolve(root,'localized/en','.'+file+suffix)));
 await writeFile(resolve(root,'prerender-manifest.json'),JSON.stringify({pages:count,languages:['en','ar','zh']},null,2));
 console.log(JSON.stringify({prerendered:count}));
} finally {await browser?.close();await new Promise(resolve=>server.close(resolve));}
