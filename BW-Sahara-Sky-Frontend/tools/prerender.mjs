import {createRequire} from 'node:module';
import {readFile,writeFile,mkdir,stat} from 'node:fs/promises';
import {resolve,dirname,sep} from 'node:path';
import {fileURLToPath} from 'node:url';
import {createServer} from 'node:http';
import {gzipSync,brotliCompressSync} from 'node:zlib';
import {routeDefinitions,languages,resolveRoute,templateFile} from '../src/shared/locale.mjs';
const {chromium}=createRequire(import.meta.url)('playwright');
const root=resolve(dirname(fileURLToPath(import.meta.url)),'..','dist');
const templates=new Map();for(const row of routeDefinitions)templates.set(row[0],await readFile(resolve(root,'.'+templateFile(row))));
const server=createServer(async(req,res)=>{
 try{
  const url=new URL(req.url,'http://127.0.0.1'),route=resolveRoute(url.pathname);
  if(route&&templates.has(route.id)){res.setHeader('Content-Type','text/html; charset=utf-8');res.end(templates.get(route.id));return;}
  const path=resolve(root,'.'+url.pathname);if(!path.startsWith(root+sep))throw Error('Invalid path');
  res.setHeader('Content-Type',path.endsWith('.js')?'text/javascript':path.endsWith('.css')?'text/css':'application/octet-stream');res.end(await readFile(path));
 }catch{res.writeHead(404);res.end();}
});
await new Promise(done=>server.listen(0,'127.0.0.1',done));
let browser,count=0;
try{
 browser=await chromium.launch({headless:true,...(process.env.HOTEL_BROWSER_PATH?{executablePath:process.env.HOTEL_BROWSER_PATH}:{}),args:process.env.HOTEL_BROWSER_ARGS?JSON.parse(process.env.HOTEL_BROWSER_ARGS):['--use-angle=swiftshader','--enable-unsafe-swiftshader']});
 const page=await browser.newPage({viewport:{width:1280,height:800},serviceWorkers:'block'}),errors=[];
 page.on('pageerror',error=>errors.push(error.message));
 await page.route('**/*',route=>new URL(route.request().url()).hostname==='127.0.0.1'?route.continue():route.abort());
 for(const row of routeDefinitions)for(let i=0;i<languages.length;i++){
  errors.length=0;
  await page.goto(`http://127.0.0.1:${server.address().port}${row[i+1]}`,{waitUntil:'load'});
  await page.waitForFunction(()=>typeof window.hotelUpdateSeo==='function'&&document.querySelector('main h1'));
  if(errors.length)throw Error(`${row[i+1]}: ${errors.join('; ')}`);
  const html=await page.evaluate(()=>{const copy=document.documentElement.cloneNode(true);if(document.body.dataset.page!=='home')copy.querySelectorAll('dialog').forEach(n=>n.remove());copy.querySelectorAll('script[src]').forEach(n=>n.setAttribute('defer',''));return '<!doctype html>'+copy.outerHTML;});
  const file=row[i+1].endsWith('.html')?row[i+1]:row[i+1]+'index.html',destination=resolve(root,'.'+file);
  await mkdir(dirname(destination),{recursive:true});await writeFile(destination,html);await writeFile(destination+'.gz',gzipSync(html));await writeFile(destination+'.br',brotliCompressSync(html));count++;
 }
 await writeFile(resolve(root,'prerender-manifest.json'),JSON.stringify({pages:count,languages,publicPaths:routeDefinitions.flatMap(r=>r.slice(1,4))},null,2));
 console.log(JSON.stringify({prerendered:count}));
}finally{await browser?.close();await new Promise(done=>server.close(done));}
