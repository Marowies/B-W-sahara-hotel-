import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
const mode = process.env.ADMIN_AUDIT_MODE || 'after';
const dir = 'test-results/admin-polish';
await mkdir(dir, {recursive:true});
const admin = JSON.parse(await readFile('../BW-Sahara-Sky-Hotel/tmp/local-integration-20261007/private-admin-access.json','utf8'));
const browser = await chromium.launch({headless:true, executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',args:['--host-resolver-rules=MAP * ~NOTFOUND, EXCLUDE 127.0.0.1']});
const context = await browser.newContext({viewport:{width:320,height:844},hasTouch:true,serviceWorkers:'block'});
const page = await context.newPage();
page.setDefaultTimeout(45000);page.setDefaultNavigationTimeout(60000);
const checks=[],timings=[],errors=[];
page.on('pageerror',e=>errors.push(e.message));
try {
 await page.goto('http://127.0.0.1:8782/admin/login');
 await page.locator('[name=username]').fill(admin.username);await page.locator('[name=password]').fill(admin.password);
 await page.locator('button[type=submit]').click();await page.waitForURL(u=>!u.pathname.includes('/login'),{timeout:45000});
 for(const [name,path,ready] of [
  ['dashboard','/admin','#widget_audit_logs tbody tr'],
  ['categories','/admin/hotel/room-categories','table.dataTable tbody tr'],
  ['payments','/admin/payments/methods','.bw-payment-settings'],
  ['media','/admin/media','.rv-media-grid .js-media-list-title'],
 ]) {
  const start=Date.now();const response=await page.goto('http://127.0.0.1:8782'+path,{waitUntil:'domcontentloaded'});
  assert.equal(response.status(),200,`${name}: HTTP status`);
  assert.match(response.headers()['cache-control'],/no-store/,`${name}: admin response must not be cached`);
  await page.waitForSelector(ready);await page.waitForTimeout(500);
  timings.push({page:name,readyMs:Date.now()-start,...await page.evaluate(()=>{const n=performance.getEntriesByType('navigation')[0],r=performance.getEntriesByType('resource');return {ttfbMs:Math.round(n.responseStart-n.startTime),resourceTransferBytes:r.reduce((s,v)=>s+v.transferSize,0),resourceCount:r.length};})});
  if(mode==='after') {
   assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+2),`${name}: page overflow`);
   checks.push(`${name}: no page overflow at 320px`);
   if(name==='dashboard') {
    assert(await page.locator('#widget_audit_logs .table-responsive').evaluate(e=>e.scrollWidth<=e.clientWidth+2),'Activity logs overflow');
    assert(await page.locator('#widget_posts_recent .table-responsive').evaluate(e=>e.scrollWidth<=e.clientWidth+2),'Recent posts overflow');
    const suggestion=page.locator('#shortcode-cache-suggestion');
    if(await suggestion.count()) assert(await suggestion.evaluate(e=>e.scrollWidth<=e.clientWidth+2),'Cache suggestion overflow');
    const next=page.locator('#widget_audit_logs .simple-pagination a').last();
    const prevText=await page.locator('#widget_audit_logs .simple-pagination p').textContent();
    await next.click();await page.waitForFunction(t=>document.querySelector('#widget_audit_logs .simple-pagination p')?.textContent!==t,prevText);
    assert.equal(await page.locator('#widget_audit_logs .simple-pagination a').first().getAttribute('aria-disabled'),null);
    checks.push('Dashboard text wraps and pagination advances with accessible previous button');
   }
   if(name==='categories') {
    const control=page.locator('table.dataTable tbody tr:not(.child) td.dtr-control').first();
    assert((await control.boundingBox()).width>=44,'Detail toggle too small');await control.click();
    await page.waitForSelector('table.dataTable tr.child');
    assert(await page.locator('table.dataTable tr.child').evaluate(e=>e.scrollWidth<=e.clientWidth+2),'Details overflow');
    checks.push('Responsive row toggle opens wrapped details at 320px');
   }
  }
  await page.screenshot({path:`${dir}/${mode}-${name}-320.png`,fullPage:true});
  console.log(JSON.stringify(timings.at(-1)));
 }
 if(mode==='after') {
  for(const width of [390,768,1440]) {
   await page.setViewportSize({width,height:900});
   await page.goto('http://127.0.0.1:8782/admin/payments/methods',{waitUntil:'domcontentloaded'});
   await page.waitForSelector('.bw-payment-settings');
   assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+2),`Payments overflow ${width}`);
   await page.screenshot({path:`${dir}/after-payments-${width}.png`,fullPage:true});checks.push(`Payments fit at ${width}px`);
  }
  assert.deepEqual(errors,[]);checks.push('No JavaScript errors');
 }
 await writeFile(`${dir}/${mode}-results.json`,JSON.stringify({passed:true,checks,timings,errors},null,2));
 console.log(JSON.stringify({passed:true,checks}));
} catch(e) {
 await page.screenshot({path:`${dir}/${mode}-failure.png`,fullPage:true}).catch(()=>{});
 await writeFile(`${dir}/${mode}-results.json`,JSON.stringify({passed:false,error:e.message,checks,timings,errors},null,2));
 console.log(JSON.stringify({passed:false,error:e.message,checks,timings,errors}));process.exitCode=1;
} finally { await browser.close(); }
