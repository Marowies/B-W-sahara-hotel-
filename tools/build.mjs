import {readFile,writeFile,mkdir,readdir,rm,copyFile,stat} from 'node:fs/promises';
import {resolve,dirname,basename,join,extname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {createHash} from 'node:crypto';
import {gzipSync,brotliCompressSync,constants} from 'node:zlib';
import {createRequire} from 'node:module';
import {seoConfig,sitemap,robots} from './seo.mjs';

const require=createRequire(import.meta.url);
const seo=seoConfig();
let sharp;
try { sharp=require('sharp'); } catch { if(!process.env.HOTEL_SHARP_PATH)throw new Error('Install the pinned Sharp dependency or set HOTEL_SHARP_PATH.'); sharp=require(process.env.HOTEL_SHARP_PATH); }
const root=resolve(dirname(fileURLToPath(import.meta.url)),'..'),src=join(root,'src'),out=resolve(root,'dist');
if(dirname(out)!==root||basename(out)!=='dist')throw new Error('Refusing unexpected build directory');
await rm(out,{recursive:true,force:true});await mkdir(join(out,'assets'),{recursive:true});
const hash=data=>createHash('sha256').update(data).digest('hex').slice(0,12);
const aliases={},info={},replacement=new Map(),measure={originalImageBytes:0,optimizedBaseImageBytes:0,responsiveImageBytes:0};
for(const name of await readdir(join(src,'assets'))){
 if(!/\.(jpg|png)$/i.test(name))continue;
 const input=await readFile(join(src,'assets',name)),image=sharp(input),meta=await image.metadata();
 measure.originalImageBytes+=input.length;
 const stem=name.replace(/\.[^.]+$/,'');
 const width=meta.width, height=meta.height;
 const base=await image.clone().webp({quality:88,effort:6}).toBuffer();
 const file=`${stem}.${hash(base)}.webp`;await writeFile(join(out,'assets',file),base);
 measure.optimizedBaseImageBytes+=base.length;
 const variants=[];
 if(name!=='logo.png')for(const size of [480,800,1280].filter(w=>w<width)){
  const data=await image.clone().resize({width:size,withoutEnlargement:true}).webp({quality:size===480?80:85,effort:6}).toBuffer();
  const variant=`${stem}-${size}.${hash(data)}.webp`;await writeFile(join(out,'assets',variant),data);
  variants.push(`/assets/${variant} ${size}w`);measure.responsiveImageBytes+=data.length;
 }
 variants.push(`/assets/${file} ${width}w`);
 const item={src:`/assets/${file}`,width,height,srcset:name==='logo.png'?null:variants.join(', ')};
 aliases[name]=item;info[item.src]=item;replacement.set(name,file);
}
function images(text){
 // Resolve template-literal image names before replacing plain filenames.
 text=text.replace(/assets\/room-\$\{([^}]+)\}-(\d)\.jpg/g,(_,id,n)=>`\${window.hotelAssetUrl('room-'+(${id})+'-${n}.jpg')}`);
 text=text.replace(/assets\/room-\$\{([^}]+)\}-\$\{([^}]+)\}\.jpg/g,(_,id,n)=>`\${window.hotelAssetUrl('room-'+(${id})+'-'+(${n})+'.jpg')}`);
 text=text.replace(/assets\/\$\{([^}]+)\}/g,(_,expr)=>`\${window.hotelAssetUrl(${expr})}`);
 for(const [name,file] of replacement)text=text.split(name).join(file);
 return text;
}
const emitted={};
async function emit(name,text){const file=`${name.replace(/\.[^.]+$/,'')}.${hash(text)}${extname(name)}`;await writeFile(join(out,file),text);emitted[name]=file;return file;}
const fontDir=join(out,'fonts');await mkdir(fontDir,{recursive:true});
let fontCss=await readFile(join(src,'fonts','fonts.css'),'utf8');
for(const name of await readdir(join(src,'fonts'))){
 if(name.endsWith('.woff2')){const data=await readFile(join(src,'fonts',name)),file=`${name.replace('.woff2','')}.${hash(data)}.woff2`;await writeFile(join(fontDir,file),data);fontCss=fontCss.replaceAll('/fonts/'+name,'/fonts/'+file);}
 else if(name.endsWith('.txt'))await copyFile(join(src,'fonts',name),join(fontDir,name));
}
await emit('fonts.css',fontCss);
for(const name of ['style.css','approval.css','burgundy.css','performance.css'])await emit(name,images((await readFile(join(src,name),'utf8')).replace(/@import\s+url\(['"]?https:\/\/fonts\.googleapis\.com\/[^)]+\);?/g,'')));
await emit('three.module.js',await readFile(join(src,'three.module.js'),'utf8'));
await emit('batch-model.js',(await readFile(join(src,'batch-model.js'),'utf8')).replace('./three.module.js','./'+emitted['three.module.js']));
let dome=await readFile(join(src,'dome-model.js'),'utf8');dome=dome.replace('./three.module.js','./'+emitted['three.module.js']).replace('./batch-model.js','./'+emitted['batch-model.js']);await emit('dome-model.js',dome);
let scene=images(await readFile(join(src,'scene.js'),'utf8'));scene=scene.replace('./three.module.js','./'+emitted['three.module.js']).replace('./dome-model.js?v=7','./'+emitted['dome-model.js']);await emit('scene.js',scene);
let runtime=await readFile(join(src,'runtime.js'),'utf8');runtime=runtime.replace('./scene.js','./'+emitted['scene.js']);await emit('runtime.js',runtime);
await emit('asset-map.js',`window.__HOTEL_SEO__=${JSON.stringify(seo)};window.__HOTEL_ASSETS__=${JSON.stringify(info)};window.__HOTEL_ASSET_ALIASES__=${JSON.stringify(aliases)};window.hotelAssetUrl=name=>window.__HOTEL_ASSET_ALIASES__[name]?.src||('assets/'+name);`);
await emit('seo.js',await readFile(join(src,'seo.js'),'utf8'));
for(const name of ['app.js','edition.js','approval.js'])await emit(name,images(await readFile(join(src,name),'utf8')));
const routes=[];
async function pages(dir,relative=''){
 for(const item of await readdir(dir,{withFileTypes:true})){
  if(item.isDirectory()&&item.name!=='assets'&&item.name!=='fonts')await pages(join(dir,item.name),join(relative,item.name));
  else if(item.isFile()&&item.name.endsWith('.html')){
   let html=images(await readFile(join(dir,item.name),'utf8'));
   html=html.replace(/<link[^>]+(?:fonts\.googleapis\.com|fonts\.gstatic\.com)[^>]*>/g,'');
   html=html.replace('</head>',`<link rel="stylesheet" href="/${emitted['fonts.css']}"><link rel="stylesheet" href="/${emitted['performance.css']}"></head>`);
   for(const [original,file] of Object.entries(emitted))html=html.replace(new RegExp(`(["'])${original.replace(/\./g,'\\.')}(?:\\?[^"']*)?(["'])`,'g'),`$1/${file}$2`);
   html=html.replace(/<script src=/g,'<script defer src=');
   html=html.replace('</body>',`<script defer src="/${emitted['seo.js']}"></script></body>`);
   html=html.replace(/(<script defer src=)/,`<script defer src="/${emitted['asset-map.js']}"></script><script defer src="/${emitted['runtime.js']}"></script>$1`);
   // Responsive hero attributes are emitted into HTML, before any JS runs.
   html=html.replace(/<img\b[^>]*>/g,tag=>{
    const match=tag.match(/src="(?:assets\/|\/assets\/)([^"]+)"/);if(!match)return tag;
    const item=info['/assets/'+match[1]];if(!item)return tag;
    if(!/\bwidth=/.test(tag))tag=tag.replace('<img','<img width="'+item.width+'" height="'+item.height+'"');
    if(!/\bdecoding=/.test(tag))tag=tag.replace('<img','<img decoding="async"');
    if(item.srcset&&!/\bsrcset=/.test(tag))tag=tag.replace('<img',`<img srcset="${item.srcset}" sizes="${/hero-image|model-fallback/.test(tag)?'100vw':'(max-width: 760px) 100vw, 60vw'}"`);
    return tag;
   });
   const dest=join(out,relative,item.name);await mkdir(dirname(dest),{recursive:true});await writeFile(dest,html);
   routes.push('/'+join(relative,item.name).replaceAll('\\','/'));
  }
 }
}
await pages(src);
await writeFile(join(out,'robots.txt'),robots(seo));
await writeFile(join(out,'sitemap.xml'),sitemap(seo,routes));
await writeFile(join(out,'.htaccess'),`Options -Indexes
DirectoryIndex index.html
ErrorDocument 404 /404.html
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{THE_REQUEST} "\\s/+localized/" [NC]
RewriteRule ^ - [R=404,L]
RewriteCond %{THE_REQUEST} "\\s/+(.*/)?index\\.html[?\\s]" [NC]
RewriteRule ^(.*/)?index\\.html$ /$1 [R=301,L]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^(.+[^/])$ /$1/ [R=301,L]
RewriteCond %{QUERY_STRING} (^|&)lang=(en|ar|zh)(&|$)
RewriteCond %{DOCUMENT_ROOT}/localized/%2/$1index.html -f
RewriteRule ^(.*\/|)$ localized/%2/$1index.html [END]
</IfModule>
<IfModule mod_headers.c>
Header always set X-Content-Type-Options "nosniff"
${seo.indexable?'':'Header always set X-Robots-Tag "noindex, nofollow"'}
</IfModule>
`);
const version=hash(JSON.stringify(emitted));
const shell=['asset-map.js','runtime.js','app.js','edition.js','approval.js','style.css','approval.css','burgundy.css','performance.css','fonts.css'].map(n=>'/'+emitted[n]);
const sw=`const VERSION=${JSON.stringify(version)},STATIC='hotel-static-'+VERSION,PAGES='hotel-pages-'+VERSION,CORE=${JSON.stringify(shell)};
self.addEventListener('install',e=>e.waitUntil(caches.open(STATIC).then(c=>c.addAll(CORE))));
self.addEventListener('activate',e=>e.waitUntil((async()=>{const keys=await caches.keys();for(const prefix of ['hotel-static-','hotel-pages-']){const old=keys.filter(k=>k.startsWith(prefix)&&k!==prefix+VERSION);for(const k of old.slice(0,-1))await caches.delete(k);}await self.clients.claim();})()));
async function boundedPut(cache,key,response){try{await cache.put(key,response);const keys=await cache.keys();if(keys.length>100)await cache.delete(keys[0]);}catch{}}
self.addEventListener('fetch',e=>{const url=new URL(e.request.url);if(e.request.method!=='GET'||url.origin!==self.location.origin)return;
if(e.request.mode==='navigate'||e.request.headers.has('X-Frontend-Navigation')){e.respondWith((async()=>{const cache=await caches.open(PAGES),lang=['en','ar','zh'].includes(url.searchParams.get('lang'))?url.searchParams.get('lang'):'en',key=url.pathname+'?lang='+lang;try{const response=await fetch(e.request);if(response.ok)await boundedPut(cache,key,response.clone());return response;}catch{return await cache.match(key)||Response.error();}})());return;}
if(/\\.(?:js|css|webp|woff2)$/.test(url.pathname)){e.respondWith((async()=>{const cache=await caches.open(STATIC),hit=await cache.match(e.request);if(hit)return hit;const response=await fetch(e.request);if(response.ok)await boundedPut(cache,e.request,response.clone());return response;})());}});`;
await writeFile(join(out,'sw.js'),sw);
await writeFile(join(out,'_headers'),'/*\n  Cache-Control: no-cache\n/assets/*\n  Cache-Control: public, max-age=31536000, immutable\n/fonts/*\n  Cache-Control: public, max-age=31536000, immutable\n/sw.js\n  Cache-Control: no-cache\n');
async function compress(dir){for(const item of await readdir(dir,{withFileTypes:true})){const path=join(dir,item.name);if(item.isDirectory())await compress(path);else if(/\.(js|css|html)$/.test(item.name)){const data=await readFile(path);await writeFile(path+'.gz',gzipSync(data,{level:9}));await writeFile(path+'.br',brotliCompressSync(data,{params:{[constants.BROTLI_PARAM_QUALITY]:9}}));}}}
await compress(out);
await writeFile(join(out,'build-manifest.json'),JSON.stringify({version,routes,files:emitted,images:aliases,measure},null,2));
console.log(JSON.stringify({version,pages:routes.length,...measure,baseImageReductionPercent:Math.round((1-measure.optimizedBaseImageBytes/measure.originalImageBytes)*100)}));
