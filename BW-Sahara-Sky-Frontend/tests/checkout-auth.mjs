import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {chromium} from 'playwright';
const backend=new URL('../../BW-Sahara-Sky-Hotel/public_html/',import.meta.url);
const script=await readFile(new URL('platform/themes/riorelax/assets/js/hotel-checkout-auth.js',backend),'utf8');
const jquery=await readFile(new URL('public/themes/riorelax/plugins/jquery.min.js',backend),'utf8');
const base='http://127.0.0.1:8780';
const browser=await chromium.launch({headless:true});let checks=0;
try {
 for(const mode of ['native','jquery','expired']) {
  const context=await browser.newContext({serviceWorkers:'block'}),page=await context.newPage();let submits=0;
  await page.route('**/*',async route=>{
   const path=new URL(route.request().url()).pathname;
   if(path==='/booking/fixture')return route.fulfill({contentType:'text/html',body:`<html lang="en"><body><form class="booking-form-main" action="/checkout" method="POST"><input name="token" value="booking-selection"><button type="submit">Checkout</button></form>${mode==='native'?'':`<script>${jquery}</script>`}<script>${script}</script></body></html>`});
   if(path==='/api/hotel/session')return route.fulfill({json:{csrf_token:'fresh-csrf'}});
   if(path==='/api/hotel/auth/refresh')return route.fulfill({status:mode==='expired'?401:200,json:mode==='expired'?{}:{access_token:'fixture-access'}});
   if(path==='/checkout'){
    submits++;
    assert.equal(route.request().headers().authorization,'Bearer fixture-access');
    assert.equal(route.request().headers()['x-csrf-token'],'fresh-csrf');
    assert(!route.request().postData().includes('fixture-access'));
    return route.fulfill({json:{error:false,data:{next_url:'/checkout/fixture-confirmation'}}});
   }
   return route.fulfill({contentType:'text/html',body:'<h1>Synthetic confirmation</h1>'});
  });
  await page.goto(base+'/booking/fixture');
  if(mode==='native')await page.locator('button').click();
  else await page.evaluate(()=>{window.jQuery('form').submit();window.jQuery('form').submit();});
  if(mode==='expired'){
   await page.getByRole('status').getByText('Your sign-in expired.',{exact:false}).waitFor();
   assert.equal(submits,0);assert.equal(await page.locator('button').isEnabled(),true);
  }else{await page.waitForURL(base+'/checkout/fixture-confirmation');assert.equal(submits,1);}
  checks++;await context.close();
 }
 console.log(JSON.stringify({checks,passed:true,scope:'Existing native/jQuery form submissions with synthetic responses; no payment provider'}));
} finally {await browser.close();}
