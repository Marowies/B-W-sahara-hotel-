import {createRequire} from 'node:module';
import {mkdir,writeFile} from 'node:fs/promises';
import {fileURLToPath} from 'node:url';
import assert from 'node:assert/strict';
const require=createRequire(import.meta.url);
let playwright;try{playwright=require('playwright');}catch{playwright=require('C:/Users/marwa/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');}
const browser=await playwright.chromium.launch({executablePath:process.env.HOTEL_BROWSER_PATH||'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true,args:['--use-angle=swiftshader','--enable-unsafe-swiftshader']});
const context=await browser.newContext({serviceWorkers:'block',reducedMotion:'reduce'});
const page=await context.newPage(),results=[],errors=[];
page.on('pageerror',e=>errors.push(e.message));
await mkdir(new URL('../test-results/',import.meta.url),{recursive:true});
const base=process.env.HOTEL_TEST_URL||'http://127.0.0.1:8780';
for(const width of [320,390,768,1024,1440])for(const lang of ['en','ar'])for(const route of ['/','/rooms/']){
 await page.setViewportSize({width,height:900});await page.goto(base+route+'?lang='+lang,{waitUntil:'networkidle'});
 const footer=page.locator('footer');await footer.scrollIntoViewIfNeeded();
 assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),true);
 const icons=footer.locator('.channel-icon');assert.equal(await icons.count(),5);
 for(const icon of await icons.all()){
  assert(await icon.getAttribute('aria-label'));assert.equal((await icon.innerText()).trim(),'');
  assert.equal(await icon.locator('svg').count(),1);
  const box=await icon.boundingBox();assert(box.width>=44&&box.height>=44);assert(box.x>=0&&box.x+box.width<=width+1);
 }
 assert.equal(await footer.locator('a[href="https://wa.me/201098255777"]').count(),1);
 assert.equal(await footer.locator('a[href="mailto:info@bwsaharaskyhotel.com"]').count(),1);
 if(route==='/')await footer.screenshot({path:fileURLToPath(new URL(`../test-results/footer-${width}-${lang}.png`,import.meta.url))});
 results.push({width,lang,route,status:'PASS'});
}
await page.setViewportSize({width:1440,height:1000});await page.goto(base+'/?lang=en',{waitUntil:'networkidle'});
await page.locator('#model-stage').scrollIntoViewIfNeeded();await page.waitForFunction(()=>window.hotel3D?.getStats().modelBuilds===1);
await page.locator('[data-view="inside"]').click();await page.waitForTimeout(1200);
await page.screenshot({path:fileURLToPath(new URL('../test-results/refined-interior.png',import.meta.url))});
await page.locator('#model-stage').focus();
for(let i=0;i<6;i++)await page.keyboard.press('ArrowDown');
for(let i=0;i<3;i++)await page.keyboard.press('ArrowRight');
await page.waitForTimeout(800);
await page.screenshot({path:fileURLToPath(new URL('../test-results/refined-perspective.png',import.meta.url))});
// Keep a frame scheduled while the home DOM is detached for client navigation.
await page.evaluate(()=>{window.__refinedCanvas=document.querySelector('#model-stage canvas');document.getElementById('rotate-model').click();});
for(let i=0;i<3;i++){
 await page.evaluate(()=>window.hotelNavigate('/rooms/?lang=en'));
 await page.evaluate(()=>window.hotelNavigate('/?lang=en'));
 assert.equal(await page.evaluate(()=>document.querySelector('#model-stage canvas')===window.__refinedCanvas),true);
 assert.equal(await page.evaluate(()=>window.hotel3D.getStats().modelBuilds),1);
}
assert.deepEqual(errors,[]);
await writeFile(new URL('../test-results/refinements.json',import.meta.url),JSON.stringify({results,activeRenderNavigation:'PASS',errors},null,2));
await browser.close();console.log(JSON.stringify({footerChecks:results.length,activeRenderNavigation:'PASS',status:'PASS'}));
