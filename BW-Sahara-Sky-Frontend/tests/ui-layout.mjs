import {chromium} from 'playwright';
import assert from 'node:assert/strict';
import {readFile,mkdir,writeFile} from 'node:fs/promises';

const output='test-results/ui-layout';await mkdir(output,{recursive:true});
const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
const context=await browser.newContext({viewport:{width:320,height:844},serviceWorkers:'block'});
const page=await context.newPage();page.setDefaultTimeout(60000);page.setDefaultNavigationTimeout(60000);
const checks=[],errors=[];page.on('pageerror',e=>errors.push(e.message));
const visit=async path=>{const r=await page.goto('http://127.0.0.1:8782'+path,{waitUntil:'load'});assert.equal(r.status(),200,path);};
const fits=async selector=>{const bad=await page.locator(selector).evaluateAll(es=>es.filter(e=>!e.closest('.sr-only,.visually-hidden')&&e.getClientRects().length&&e.clientWidth&&e.scrollWidth>e.clientWidth+2).map(e=>({tag:e.tagName,class:e.className,width:e.clientWidth,scroll:e.scrollWidth})));if(bad.length)console.log(JSON.stringify({path:new URL(page.url()).pathname,overflow:bad}));return !bad.length;};
const widths=[320,390,768,1440];
try{
 for(const path of ['/rooms/','/contact-us/','/ar/ghoraf/']){
  await visit(path);
  if(path.includes('rooms')||path.includes('ghoraf'))await page.locator('.room-grid[aria-busy=true]').waitFor({state:'detached'});
  for(const width of widths){
   await page.setViewportSize({width,height:900});
   assert(await fits('.stay-card,.stay-card h3,.stay-card .card-actions,.contact-layout>*,.footer-top>*,.footer-bottom'),'Public component overflow: '+path+' '+width);
   assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+2),'Public page overflow');
   checks.push(`Public cards, forms and footer fit ${path} at ${width}px`);
  }
 }
 // Real responsive-image mutation: a new source must not display an old srcset.
 await visit('/rooms/');await page.locator('.room-grid[aria-busy=true]').waitFor({state:'detached'});
 const photo=page.locator('.stay-card img').first();await photo.scrollIntoViewIfNeeded();
 await page.waitForFunction(()=>document.querySelector('.stay-card img')?.dataset.hotelResponsive==='true');
 await photo.evaluate(img=>img.src='data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10" fill="red"/></svg>');
 await page.waitForFunction(()=>!document.querySelector('.stay-card img').hasAttribute('srcset'));
 checks.push('Changing a room image clears the previous responsive source set');
 const admin=JSON.parse(await readFile('../BW-Sahara-Sky-Hotel/tmp/local-integration-20261007/private-admin-access.json','utf8'));
 await visit('/admin/login');await page.locator('[name=username]').fill(admin.username);await page.locator('[name=password]').fill(admin.password);await page.locator('button[type=submit]').click();await page.waitForURL(u=>!u.pathname.includes('/login'));
 await visit('/admin/hotel/room-categories');await page.locator('table.dataTable tbody .bw-record-name').first().waitFor();
 for(const width of widths){
  await page.setViewportSize({width,height:900});await page.waitForTimeout(400);
  const name=page.locator('table.dataTable tbody tr:not(.child) .bw-record-name').first();
  assert(await name.isVisible(),`Record name hidden at ${width}px`);
  assert(await fits('table.dataTable .bw-record-name'),'Record names overflow');
  assert(await fits('.card,.card-header,.card-body'),'Table card overflow');
  checks.push(`Record names stay visible and fit at ${width}px`);
 }
 await page.setViewportSize({width:320,height:844});await page.waitForTimeout(400);
 await page.locator('td.dtr-control').first().click();await page.locator('tr.child').first().waitFor();
 assert(await fits('tr.child ul.dtr-details'),'Expanded details overflow');
 assert(await page.locator('tr.child .language-column a').count()>0,'Translations must remain accessible in details');
 checks.push('Hidden translations and operations remain accessible through row details');
 await page.screenshot({path:`${output}/categories-320.png`,fullPage:true});
 for(const path of ['/admin/hotel/rooms/create','/admin/hotel/settings/general']){
  await visit(path);
  for(const width of widths){
   await page.setViewportSize({width,height:900});
   assert(await fits('.card,.card-header,.card-body,.card-title,.form-label'),'Admin form/card overflow: '+path+' '+width);
   checks.push(`Admin form cards fit ${path} at ${width}px`);
  }
  if(path.endsWith('create')){
   await page.setViewportSize({width:320,height:844});
   assert((await page.locator('.slug-field-wrapper input[name=slug][type=text]').boundingBox()).width>=120,'Slug editor too narrow');
   await page.locator('.card-header .card-title').first().evaluate(e=>e.textContent='LongTranslatedHeadingWithoutSpaces'.repeat(4));
   assert(await fits('.card-header,.card-title'),'Long translated card heading escapes card');
   checks.push('Long headings wrap and permalink editor remains usable at 320px');
   await page.screenshot({path:`${output}/room-form-320.png`,fullPage:true});
  }
 }
 assert.deepEqual(errors,[]);checks.push('No JavaScript errors');
 await writeFile(`${output}/results.json`,JSON.stringify({passed:true,checks},null,2));console.log(JSON.stringify({passed:true,checks}));
}finally{await browser.close();}
