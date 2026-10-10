import {spawn} from 'node:child_process';
import {mkdir,readFile,writeFile} from 'node:fs/promises';
import {resolve} from 'node:path';
const base=process.env.HOTEL_AUDIT_URL||'http://127.0.0.1:8780';
if(new URL(base).hostname!=='127.0.0.1')throw Error('This script audits only the local preview.');
await mkdir('test-results',{recursive:true});
const results=[];
for(const [name,path,desktop] of [['home-mobile','/',false],['home-desktop','/',true],['rooms-mobile','/rooms/',false]]){
 const output=resolve('test-results','lighthouse-'+name);
 const args=['node_modules/lighthouse/cli/index.js',base+path,'--chrome-flags=--headless --no-first-run --no-sandbox --no-proxy-server','--only-categories=performance,accessibility,best-practices,seo','--output=json','--output=html','--output-path='+output,'--quiet'];
 if(desktop)args.push('--preset=desktop');
 await new Promise((done,reject)=>{const child=spawn(process.execPath,args,{stdio:'inherit',env:{...process.env,CHROME_PATH:process.env.HOTEL_BROWSER_PATH||'C:/Program Files/Google/Chrome/Application/chrome.exe'}});child.on('error',reject);child.on('exit',code=>code===0?done():reject(Error('Lighthouse failed: '+code)));});
 const data=JSON.parse(await readFile(output+'.report.json','utf8'));
 if(data.runtimeError)throw Error(data.runtimeError.message);
 results.push({name,url:data.finalDisplayedUrl,version:data.lighthouseVersion,scores:Object.fromEntries(Object.entries(data.categories).map(([k,v])=>[k,Math.round(v.score*100)])),metrics:Object.fromEntries(['first-contentful-paint','largest-contentful-paint','total-blocking-time','cumulative-layout-shift'].map(key=>[key,data.audits[key].numericValue])),warnings:data.runWarnings});
}
await writeFile('test-results/performance-audit-summary.json',JSON.stringify({date:'2026-10-09',environment:'local preview; lab results, not production or real-user data',results},null,2));
console.log(JSON.stringify(results,null,2));
