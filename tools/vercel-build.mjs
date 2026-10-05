// Vercel build: builds into dist/, prerenders the language snapshots when a browser is available,
// and otherwise falls back to the client-rendered pages so the deployment always succeeds.
import {spawnSync} from 'node:child_process';
import {readFile,writeFile,mkdir,copyFile} from 'node:fs/promises';
import {existsSync} from 'node:fs';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {publicPath,routePath} from './seo.mjs';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..'),dist=resolve(root,'dist');
const production=!process.env.VERCEL_ENV||process.env.VERCEL_ENV==='production';
const origin=process.env.HOTEL_SITE_ORIGIN||(process.env.VERCEL_PROJECT_PRODUCTION_URL?'https://'+process.env.VERCEL_PROJECT_PRODUCTION_URL:'https://bwsaharaskyhotel.com');
// process.env is read at call time: the Chromium package sets its library paths when imported.
const run=(script,extra={})=>spawnSync(process.execPath,[resolve(root,script)],{stdio:'inherit',env:{...process.env,HOTEL_SITE_ORIGIN:origin,HOTEL_INDEXABLE:production?'1':'0',...extra},timeout:10*60*1000}).status===0;

if(!run('tools/build.mjs'))process.exit(1);

let browser={};
if(process.env.VERCEL&&!process.env.HOTEL_BROWSER_PATH){
 try {
  const chromium=(await import('@sparticuz/chromium')).default;
  browser={HOTEL_BROWSER_PATH:await chromium.executablePath(),HOTEL_BROWSER_ARGS:JSON.stringify(chromium.args)};
 } catch(error){console.warn('No serverless Chromium available:',error.message);}
}
const prerendered=(!process.env.VERCEL||browser.HOTEL_BROWSER_PATH||process.env.HOTEL_BROWSER_PATH)&&run('tools/prerender.mjs',browser);

if(!prerendered){
 console.warn('Prerender skipped: serving client-rendered pages for every language.');
 const {routes}=JSON.parse(await readFile(resolve(dist,'build-manifest.json'),'utf8'));
 for(const file of routes){
  const source=resolve(dist,'.'+file);
  if(production&&publicPath(routePath(file))){
   const html=(await readFile(source,'utf8')).replace('<meta name="robots" content="noindex,nofollow">','<meta name="robots" content="index,follow">');
   await writeFile(source,html);
  }
  for(const lang of ['en','ar','zh']){
   const destination=resolve(dist,'localized',lang,'.'+file);
   await mkdir(dirname(destination),{recursive:true});
   await copyFile(source,destination);
  }
 }
}
if(!existsSync(resolve(dist,'localized/en/index.html')))throw new Error('Missing localized pages in dist/.');
console.log(JSON.stringify({vercel:true,origin,indexable:production,prerendered:Boolean(prerendered)}));
