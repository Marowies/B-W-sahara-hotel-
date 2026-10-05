import {createRequire} from 'node:module';
import {readFile,writeFile,mkdir} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import assert from 'node:assert/strict';
const require=createRequire(import.meta.url);
let playwright;
try{playwright=require('playwright');}catch{playwright=require('C:/Users/marwa/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');}
const root=resolve(dirname(fileURLToPath(import.meta.url)),'..'),out=resolve(root,'test-results');await mkdir(out,{recursive:true});
const manifest=JSON.parse(await readFile(resolve(root,'dist/build-manifest.json'),'utf8'));
const base=process.env.HOTEL_TEST_URL||'http://127.0.0.1:8780';
const browser=await playwright.chromium.launch({executablePath:process.env.HOTEL_BROWSER_PATH||'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true,args:['--use-angle=swiftshader','--enable-unsafe-swiftshader']});
const context=await browser.newContext({viewport:{width:1280,height:900},serviceWorkers:'block'});
const page=await context.newPage(),errors=[],badResponses=[],requests=[];
page.on('pageerror',e=>errors.push(e.message));
page.on('response',r=>{if(r.status()>=400)badResponses.push([r.status(),r.url()]);});
page.on('request',r=>requests.push({url:r.url(),method:r.method()}));
const results=[];
async function check(name,run){try{await run();results.push({name,status:'PASS'});}catch(e){results.push({name,status:'FAIL',error:e.message});}}
await page.goto(base+'/?lang=en',{waitUntil:'networkidle'});
const initialMetrics=await page.evaluate(()=>({...window.hotelPerformance}));
await check('Heavy 3D dependencies are not requested above the fold',async()=>assert.equal(requests.filter(x=>/scene\.|three\.module\.|dome-model\./.test(x.url)).length,0));
await page.screenshot({path:resolve(out,'home-desktop.png')});
await check('Desktop home has no horizontal overflow',async()=>assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),true));
await page.locator('#model-stage').scrollIntoViewIfNeeded();
await check('3D loads one scene and one renderer',async()=>{
 await page.waitForFunction(()=>window.hotel3D?.getStats().modelBuilds===1&&document.querySelector('#model-stage.ready canvas'),{timeout:30000});
 assert.equal(await page.evaluate(()=>window.hotel3D.getStats().renderersCreated),1);
});
await page.waitForTimeout(3500);
await page.screenshot({path:resolve(out,'model-desktop.png')});
await check('Idle 3D stops drawing frames',async()=>{
 await page.waitForFunction(()=>window.hotel3D.getStats().idle,null,{timeout:30000});
 const before=await page.evaluate(()=>window.hotel3D.getStats().frames);await page.waitForTimeout(800);
 assert.equal(await page.evaluate(()=>window.hotel3D.getStats().frames),before);
});
await check('Immersive view reuses the same canvas',async()=>{
 await page.evaluate(()=>{window.__canvas=document.querySelector('#model-stage canvas');document.getElementById('open-immersive').click();});
 await page.waitForTimeout(400);
 assert.equal(await page.evaluate(()=>document.querySelector('#immersive-stage canvas')===window.__canvas),true);
 await page.locator('.close-immersive').click();
});
await check('Room layout and cutaway controls still work',async()=>{
 await page.locator('[data-layout="twin"]').click();
 assert.equal(await page.locator('[data-layout="twin"]').getAttribute('aria-pressed'),'true');
 await page.waitForTimeout(1500);
 await page.screenshot({path:resolve(out,'model-interior.png')});
});
await check('Cross-page navigation returns to the original 3D instance',async()=>{
 await page.evaluate(()=>document.querySelector('header a[href="/rooms/?lang=en"]').click());
 await page.waitForFunction(()=>document.body.dataset.page==='rooms');
 assert.equal(await page.evaluate(()=>window.hotelPerformance.clientNavigations),1);
 await page.evaluate(()=>document.querySelector('header .brand').click());
 await page.waitForFunction(()=>document.body.dataset.page==='home');
 await page.locator('#model-stage').scrollIntoViewIfNeeded();await page.waitForTimeout(400);
 assert.equal(await page.evaluate(()=>document.querySelector('#model-stage canvas')===window.__canvas),true);
 assert.equal(await page.evaluate(()=>window.hotel3D.getStats().modelBuilds),1);
 assert.equal(requests.filter(x=>/three\.module\./.test(x.url)).length,1);
});
await check('Offscreen 3D stops drawing frames',async()=>{
 await page.evaluate(()=>scrollTo(0,0));await page.waitForTimeout(400);
 const before=await page.evaluate(()=>window.hotel3D.getStats().frames);await page.waitForTimeout(800);
 assert.equal(await page.evaluate(()=>window.hotel3D.getStats().frames),before);
});
await check('Missing WebGL has a photographic fallback',async()=>{
 const fallback=await context.newPage();
 await fallback.addInitScript(()=>{const original=HTMLCanvasElement.prototype.getContext;HTMLCanvasElement.prototype.getContext=function(type,...args){if(type.includes('webgl'))return null;return original.call(this,type,...args);};});
 await fallback.goto(base+'/?lang=en');await fallback.locator('#model-stage').scrollIntoViewIfNeeded();
 await fallback.waitForFunction(()=>document.querySelector('[data-retry-model]'));
 assert.equal(await fallback.locator('#model-stage canvas').count(),0);
 assert.equal(await fallback.locator('.model-fallback').evaluate(e=>getComputedStyle(e).opacity),'1');
 await fallback.close();
});
const homeMetrics=await page.evaluate(()=>window.hotel3D.getStats());
await check('Every English page renders without missing assets or overflow',async()=>{
 for(const route of manifest.routes.filter(r=>r!=='/404.html')){
  const url=route.replace(/index\.html$/,'')+'?lang=en';
  await page.goto(base+url,{waitUntil:'networkidle'});
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),true,url+' overflows');
  assert.ok((await page.locator('main').innerText()).trim().length>20,url+' empty main');
 }
});
await page.setViewportSize({width:390,height:844});
await check('Mobile English and Arabic layouts remain readable without overflow',async()=>{
 for(const lang of ['en','ar'])for(const route of ['/','/rooms/','/galleries/','/contact-us/','/booking/','/design-review/']){
  await page.goto(base+route+'?lang='+lang,{waitUntil:'networkidle'});
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),true,route+' '+lang+' overflows');
  if(route==='/')await page.screenshot({path:resolve(out,'home-mobile-'+lang+'.png')});
 }
});
await check('Reduced-motion preference is respected',async()=>{
 await page.emulateMedia({reducedMotion:'reduce'});await page.goto(base+'/?lang=en');
 assert.equal(await page.evaluate(()=>window.motionPaused),true);
});
await check('No backend requests or unexpected external asset requests',async()=>{
 assert.equal(requests.filter(x=>x.method!=='GET').length,0);
 assert.equal(requests.filter(x=>/^https?:/.test(x.url)&&!x.url.startsWith(base)).length,0);
});
await check('No JavaScript errors or failed asset responses',async()=>{assert.deepEqual(errors,[]);assert.deepEqual(badResponses,[]);});
const three=manifest.files['three.module.js'];
await check('Cache and compression headers are enabled',async()=>{
 const response=await context.request.get(base+'/'+three,{headers:{'Accept-Encoding':'br'}});
 assert.match(response.headers()['cache-control'],/immutable/);assert.equal(response.headers()['content-encoding'],'br');
 const repeat=await context.request.get(base+'/'+three,{headers:{'If-None-Match':response.headers().etag}});assert.equal(repeat.status(),304);
});
await context.close();
const cached=await browser.newContext({viewport:{width:1280,height:900}}),cachedPage=await cached.newPage();
await check('Service worker activates without eagerly fetching the 3D model',async()=>{
 const heavy=[];cachedPage.on('request',r=>{if(/three\.module\.|dome-model\.|scene\./.test(r.url()))heavy.push(r.url());});
 await cachedPage.goto(base+'/?lang=en',{waitUntil:'networkidle'});
 await cachedPage.waitForFunction(()=>!!navigator.serviceWorker.controller,{timeout:15000});
 assert.equal(heavy.length,0);
});
await cached.close();await browser.close();
const report={date:'2026-10-05',results,initialMetrics,homeMetrics,assets:manifest.measure,badResponses,errors,externalRequests:requests.filter(x=>/^https?:/.test(x.url)&&!x.url.startsWith(base)),browser:'Chrome headless, SwiftShader software WebGL; local preview',limitations:'Local functional/performance checks, not field Core Web Vitals or a physical-device benchmark.'};
await writeFile(resolve(out,'verification.json'),JSON.stringify(report,null,2));
console.log(JSON.stringify({pass:results.filter(x=>x.status==='PASS').length,fail:results.filter(x=>x.status==='FAIL').length,failures:results.filter(x=>x.status==='FAIL'),homeMetrics}));
process.exitCode=results.some(x=>x.status==='FAIL')?1:0;
