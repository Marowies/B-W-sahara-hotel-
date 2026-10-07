import {createRequire} from 'node:module';
import {writeFile} from 'node:fs/promises';
import assert from 'node:assert/strict';
const {chromium}=createRequire(import.meta.url)('playwright'),base=process.env.HOTEL_TEST_URL||'http://127.0.0.1:8782';
assert.equal(new URL(base).hostname,'127.0.0.1');
const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
try{
 const context=await browser.newContext({serviceWorkers:'block'}),page=await context.newPage();
 await page.addInitScript(()=>{window.analyticsCalls=[];window.hotelAnalyticsAdapter=(event,properties)=>window.analyticsCalls.push({event,properties});});
 const missing=await context.request.post(base+'/api/hotel/locale',{data:{ui_locale:'ar'},headers:{Accept:'application/json'}});assert.equal(missing.status(),419);
 const session=await (await context.request.get(base+'/api/hotel/session')).json();
 const guest=await context.request.post(base+'/api/hotel/locale',{data:{ui_locale:'ar'},headers:{Accept:'application/json','X-CSRF-TOKEN':session.csrf_token}});assert.equal(guest.status(),401);
 await page.goto(base+'/rooms/?utm_source=chatgpt&email=private-value',{waitUntil:'load'});
 assert.equal((await page.evaluate(()=>window.analyticsCalls[0])).properties.traffic_channel,'ai_referral');
 await page.locator('header a[href="/contact-us/"]').click();await page.waitForFunction(()=>document.body.dataset.page==='contact');
 const events=await page.evaluate(()=>window.analyticsCalls);assert.equal(events.length,2);assert(!JSON.stringify(events).includes('private-value'));assert.equal(events[1].properties.page_id,'contact');
 await page.locator('.lang-btn').click();await page.locator('.lang-list [data-v="ar"]').click();await page.waitForURL(base+'/ar/tawasul/');assert.equal(await page.locator('html').getAttribute('dir'),'rtl');
 await page.locator('.lang-btn').click();await page.locator('.lang-list [data-v="zh"]').click();await page.waitForURL(base+'/zh/lianxi/');assert.equal((await context.cookies()).find(c=>c.name==='hotel_locale')?.value,'zh');
 const preferred=await context.request.get(base+'/',{maxRedirects:0});assert.equal(preferred.status(),307);assert.equal(preferred.headers().location,'/zh/');assert(preferred.headers()['cache-control'].includes('no-store'));
 await page.locator('.lang-btn').click();await page.locator('.lang-list [data-v="en"]').click();await page.waitForURL(base+'/contact-us/');assert.equal((await context.request.get(base+'/',{maxRedirects:0})).status(),200);
 await page.goto(base+'/guides/planning-a-desert-stay/');await page.screenshot({path:'test-results/seo/guide-desktop.png',fullPage:true});await page.setViewportSize({width:390,height:844});await page.screenshot({path:'test-results/seo/guide-mobile.png',fullPage:true});
 const result={status:'PASS',checks:['locale CSRF 419','guest locale 401','AI referral separated','client navigation analytics','no query PII in events','native language switching','cookie redirect no-store','English override'],customerDatabase:'Separately verified with rolled-back local fixture'};await writeFile('test-results/seo/locale-preference.json',JSON.stringify(result,null,2));console.log(JSON.stringify(result));
}finally{await browser.close();}
