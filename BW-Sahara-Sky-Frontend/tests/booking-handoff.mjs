import assert from 'node:assert/strict';
import {chromium} from 'playwright';
import {localizedPath} from '../src/shared/locale.mjs';

const base=process.env.HOTEL_TEST_URL||'http://127.0.0.1:8780';
const browser=await chromium.launch({headless:true});
let checks=0;
try {
 for(const lang of ['en','ar','zh']) for(const width of [390,1280]) {
  const context=await browser.newContext({viewport:{width,height:900},serviceWorkers:'block'});
  const page=await context.newPage();let selection;
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.route('**/api/hotel/**',async route=>{
   const path=new URL(route.request().url()).pathname;
   let data={};
   if(path.endsWith('/session'))data={csrf_token:'fixture-csrf'};
   if(path.endsWith('/auth/providers'))data={google:false};
   if(path.endsWith('/auth/refresh'))data={access_token:'fixture-access',expires_in:900,email:'fixture@example.invalid'};
   if(path.endsWith('/availability'))data={rooms:[{id:77,slug:'bw-sahara-sky-hotel-superior-room-double',name:'Fixture room',price:254,currency:'USD',size:25,max_adults:2,number_of_beds:2}]};
   if(path.endsWith('/checkout/eligibility'))data={eligible:true};
   if(path.endsWith('/booking/start')){
    selection=route.request().postDataJSON();
    assert.equal(route.request().headers().authorization,'Bearer fixture-access');
    data={error:false,next_url:base+'/booking/'+'a'.repeat(32)};
   }
   await route.fulfill({status:200,contentType:'application/json',body:JSON.stringify(data)});
  });
  await page.route('**/booking/'+ 'a'.repeat(32),route=>route.fulfill({status:200,contentType:'text/html',body:'<h1>Existing hotel booking form fixture</h1>'}));
  await page.goto(base+localizedPath('/booking/',lang),{waitUntil:'networkidle'});
  await page.locator('#booking-search input[name=arrival]').fill('2026-11-10');
  await page.locator('#booking-search input[name=departure]').fill('2026-11-12');
  await page.locator('#booking-search button[type=submit]').click();
  await page.locator('.booking-results [data-backend-room-id="77"] [data-select-room]').click();
  await page.waitForURL(base+localizedPath('/booking/review/',lang));
  await page.locator('#review-state .booking-aside>button').click();
  await page.waitForURL(base+'/booking/'+'a'.repeat(32));
  assert.deepEqual(selection,{room_id:77,arrival:'2026-11-10',departure:'2026-11-12',adults:2,children:0,rooms:1});
  assert.deepEqual(errors,[]);checks++;
  await context.close();
 }
 console.log(JSON.stringify({checks,passed:true,scope:'Frontend with synthetic API fixtures; real server validation is tested separately'}));
} finally {await browser.close();}
