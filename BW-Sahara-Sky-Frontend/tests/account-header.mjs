import assert from 'node:assert/strict';
import {chromium} from 'playwright';
import {localizedPath} from '../src/shared/locale.mjs';

const base=process.env.HOTEL_TEST_URL||'http://127.0.0.1:8780';
const browser=await chromium.launch({headless:true});let checks=0;
try {
 for(const lang of ['en','ar','zh'])for(const width of [390,1280]) {
  const context=await browser.newContext({viewport:{width,height:900},serviceWorkers:'block'}),page=await context.newPage();
  let signedIn=false;
  await page.route('**/api/hotel/**',async route=>{
   const path=new URL(route.request().url()).pathname;
   if(path.endsWith('/auth/logout'))signedIn=false;
   if(path.endsWith('/auth/otp/verify'))signedIn=true;
   const data=path.endsWith('/session')?{csrf_token:'fixture'}:path.endsWith('/auth/providers')?{google:true}:path.endsWith('/auth/otp/request')?{resend_in:60}:signedIn?{access_token:'fixture',expires_in:900,email:'fixture@example.invalid'}:{};
   await route.fulfill({status:path.endsWith('/auth/refresh')&&!signedIn?401:200,json:data});
  });
  const header=page.locator(width<900?'.nav-mobile-signin':'.nav-signin');
  await page.goto(base+localizedPath('/login/',lang),{waitUntil:'networkidle'});
  const guestLabel=await header.textContent();
  assert.equal(await header.getAttribute('href'),localizedPath('/login/',lang));
  await page.locator('input[name=email]').fill('fixture@example.invalid');
  await page.locator('.welcome-form>button[type=submit]').click();
  await page.locator('input[name=code]').fill('123456');
  await page.locator('.welcome-done').waitFor();
  assert.equal(await header.textContent(),{en:'My account',ar:'حسابي',zh:'我的账户'}[lang]);
  assert.equal(await header.getAttribute('href'),'/customer/overview');
  await page.reload({waitUntil:'networkidle'});
  await page.locator('.welcome-done').waitFor();
  assert.equal(await header.getAttribute('href'),'/customer/overview');
  await page.locator('.done-signout').click();
  await page.locator('.welcome-form input[name=email]').waitFor();
  assert.equal(await header.textContent(),guestLabel);
  assert.equal(await header.getAttribute('href'),localizedPath('/login/',lang));
  signedIn=true;
  await page.goto(base+localizedPath('/about-us/',lang),{waitUntil:'networkidle'});
  assert.equal(await header.getAttribute('href'),'/customer/overview');
  checks++;await context.close();
 }
 console.log(JSON.stringify({checks,passed:true,scope:'Real client auth code with synthetic OTP/refresh/logout responses'}));
}finally{await browser.close();}
