import {spawnSync} from 'node:child_process';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const production=process.env.VERCEL_ENV==='production';
let browser={};
if(process.env.VERCEL&&!process.env.HOTEL_BROWSER_PATH){
 const chromium=(await import('@sparticuz/chromium')).default;
 browser={HOTEL_BROWSER_PATH:await chromium.executablePath(),HOTEL_BROWSER_ARGS:JSON.stringify(chromium.args)};
}
const result=spawnSync(process.execPath,[resolve(root,'tools/compile.mjs')],{stdio:'inherit',env:{...process.env,HOTEL_SITE_ORIGIN:process.env.HOTEL_SITE_ORIGIN||'https://bwsaharaskyhotel.com',HOTEL_INDEXABLE:production?'1':'0',...browser},timeout:10*60*1000});
if(result.status!==0)process.exit(result.status||1);
console.log(JSON.stringify({vercel:true,indexable:production,prerendered:true}));
