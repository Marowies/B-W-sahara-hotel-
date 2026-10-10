import {chromium} from 'playwright';
import assert from 'node:assert/strict';
import {readFile,mkdir,writeFile} from 'node:fs/promises';

const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
const context=await browser.newContext({viewport:{width:320,height:900},serviceWorkers:'block'});
const page=await context.newPage(),checks=[],errors=[];
page.setDefaultTimeout(45000);page.setDefaultNavigationTimeout(60000);
page.on('pageerror',e=>errors.push(e.message));
const output='test-results/mobile-regressions';await mkdir(output,{recursive:true});
const visit=async path=>{const r=await page.goto('http://127.0.0.1:8782'+path,{waitUntil:'load'});assert.equal(r.status(),200,path);};
try{
 const admin=JSON.parse(await readFile('../BW-Sahara-Sky-Hotel/tmp/local-integration-20261007/private-admin-access.json','utf8'));
 await visit('/admin/login');await page.locator('[name=username]').fill(admin.username);await page.locator('[name=password]').fill(admin.password);await page.locator('button[type=submit]').click();await page.waitForURL(u=>!u.pathname.includes('/login'));
 for(const path of ['/admin','/admin/hotel/room-categories','/admin/hotel/rooms','/admin/hotel/amenities','/admin/hotel/foods','/admin/pages','/admin/hotel/bookings','/admin/hotel/booking-calendar','/admin/hotel/booking-reports','/admin/hotel/sync-calendars']){
  await visit(path);
  if(path==='/admin'){
   await page.locator('#widget_audit_logs tbody tr').first().waitFor();
   await page.locator('#widget_posts_recent tbody tr').first().waitFor();
  }
  if(path.endsWith('booking-calendar'))await page.locator('.card:has(.fc) > .loading-spinner').waitFor({state:'hidden'});
  const table=page.locator('table.dataTable');if(await table.count())await table.first().locator('tbody tr').first().waitFor();
  assert(await page.locator('link[href*="bw-admin.css?v=9"]').count(),`Brand CSS missing: ${path}`);
  assert.equal(await page.evaluate(()=>getComputedStyle(document.documentElement).getPropertyValue('--bw-burgundy').trim()),'#571c35',`Brand styles unavailable: ${path}`);
  for(const width of [320,390,768,1440]){
   await page.setViewportSize({width,height:900});
   const contained=await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+2);
   if(!contained){await page.screenshot({path:`${output}/overflow.png`,fullPage:true});console.log(await page.evaluate(()=>[...document.querySelectorAll('.page-wrapper *')].filter(e=>!e.closest('.table-responsive')&&e.getBoundingClientRect().right>innerWidth+2).map(e=>({tag:e.tagName,class:e.className,width:e.clientWidth,scroll:e.scrollWidth})).slice(0,20)));}
   assert(contained,`Page overflow: ${path} ${width}`);
  }
  await page.setViewportSize({width:320,height:900});
  const details=page.locator('td.dtr-control .bw-row-details').first();
  if(await details.isVisible()){
   await details.focus();await page.keyboard.press('Enter');await page.locator('tr.child').first().waitFor();
   await page.waitForFunction(()=>document.querySelector('.bw-row-details')?.getAttribute('aria-expanded')==='true');
   await page.keyboard.press('Enter');await page.locator('tr.child').first().waitFor({state:'hidden'});
  }
  if(path.endsWith('booking-reports'))assert(await page.locator('.bw-chart-empty').count()>0,'Zero revenue should show an empty state');
  if(path.endsWith('sync-calendars')){
   assert(await page.locator('.bw-calendar-rooms td').first().evaluate(e=>e.clientWidth>=260),'Calendar room name is squeezed');
   assert(await page.locator('.bw-calendar-rooms tbody tr').evaluateAll(es=>es.every(e=>e.clientHeight<160)),'Missing image must not squeeze name into a tall row');
  }
  await page.screenshot({path:`${output}/${path.split('/').at(-1)||'dashboard'}-320.png`,fullPage:true});
  checks.push(`${path}: consistent brand, contained layout at 4 widths, keyboard details where available`);
 }
 for(const path of ['/','/contact-us/','/rooms/','/ar/tawasul/']){
  await visit(path);
  for(const width of [320,390,768,1440]){
   await page.setViewportSize({width,height:900});
   assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+2),`Public overflow: ${path} ${width}`);
   if(width<=390)assert(await page.locator('.answer-comparison tbody td').evaluateAll(es=>es.every(e=>e.scrollWidth<=e.clientWidth+2)),'Comparison cells clipped');
  }
  if(path==='/'){
   await page.setViewportSize({width:320,height:900});
   assert((await page.locator('#quick-stay input[type=date]').first().boundingBox()).width>=200,'Date field too narrow');
   const guests=page.locator('#quick-stay [role=combobox]');await guests.click();await page.keyboard.press('End');await page.keyboard.press('Enter');
   assert.equal(await page.locator('#quick-stay select').inputValue(),'4','Keyboard selection must update form value');
   assert.equal(await guests.getAttribute('aria-expanded'),'false');
   await page.reload({waitUntil:'load'});assert.equal(await page.locator('#quick-stay [role=combobox]').count(),1,'Prerender must not duplicate guest control');
  }
  await page.screenshot({path:`${output}/public-${path.replaceAll('/','-')||'home'}.png`,fullPage:true});checks.push(`${path}: public controls and comparison fit`);
 }
 assert.deepEqual(errors,[],'Browser errors');
 await writeFile(`${output}/results.json`,JSON.stringify({passed:true,checks},null,2));console.log(JSON.stringify({passed:true,checks}));
}finally{await browser.close();}
