import assert from 'node:assert/strict';
import {chromium} from 'playwright';
import {localizedPath} from '../src/shared/locale.mjs';

// Run against a connected preview (HOTEL_BACKEND_ORIGIN set on tools/serve.mjs) so the account code is active.
// Every /api/hotel/ response below is synthetic; no backend request is made.
// The email-code form is rendered by the connected frontend; API calls are mocked below.
const base=process.env.HOTEL_TEST_URL||'http://127.0.0.1:8782';
const browser=await chromium.launch({headless:true,...(process.env.HOTEL_BROWSER_PATH?{executablePath:process.env.HOTEL_BROWSER_PATH}:{})});let checks=0;
try {
 for(const lang of ['en','ar','zh'])for(const width of [390,1280]) {
  const context=await browser.newContext({viewport:{width,height:900},serviceWorkers:'block'}),page=await context.newPage();
  await context.addCookies([{name:'hotel_cookie_consent',value:'necessary',url:base}]);
  let signedIn=false;
  await page.route('**/api/hotel/**',async route=>{
   const path=new URL(route.request().url()).pathname;
   if(path.endsWith('/auth/logout'))signedIn=false;
   if(path.endsWith('/auth/otp/verify'))signedIn=true;
   const data=path.endsWith('/session')?{csrf_token:'fixture'}:path.endsWith('/auth/providers')?{google:true}:path.endsWith('/auth/otp/request')?{resend_in:60}:signedIn?{access_token:'fixture',expires_in:900,email:'fixture@example.invalid'}:{};
   await route.fulfill({status:path.endsWith('/auth/refresh')&&!signedIn?401:200,json:data});
  });
  // One account menu at every width: a toggle button opening a panel with the sign-in/account and sign-up links.
  const toggle=page.locator('header.nav .account-toggle'),panel=page.locator('#account-links');
  const account=panel.locator('.nav-signin'),signup=panel.locator('.nav-signup');
  const openMenu=async()=>{if(await panel.isHidden())await toggle.click();assert.equal(await toggle.getAttribute('aria-expanded'),'true');assert.equal(await panel.isVisible(),true);};
  const closeMenu=async()=>{await page.keyboard.press('Escape');assert.equal(await panel.isHidden(),true);assert.equal(await toggle.getAttribute('aria-expanded'),'false');};
  const accountHref='/customer/overview?lang='+lang,accountLabel={en:'My account',ar:'حسابي',zh:'我的账户'}[lang];

  // Guest
  await page.goto(base+localizedPath('/login/',lang),{waitUntil:'networkidle'});
  assert.match(await page.getAttribute('html','lang'),new RegExp('^'+lang+'(?:-|$)')); // Chinese pages use zh-Hans
  assert.equal(await toggle.isVisible(),true);
  await openMenu();
  const guestLabel=(await account.textContent()).trim();
  assert.equal(await account.getAttribute('href'),localizedPath('/login/',lang));
  assert.equal(await account.getAttribute('aria-current'),'page');
  assert.equal(await signup.getAttribute('href'),localizedPath('/register/',lang));
  assert.equal(await signup.isVisible(),true);
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),true);
  await closeMenu();

  // Signed in through the real client OTP code
  await page.locator('.welcome-form input[name=email]').fill('fixture@example.invalid');
  await page.locator('.welcome-form>button[type=submit]').click();
  await page.locator('input[name=code]').fill('123456'); // six digits submit the code form
  await page.locator('.welcome-done').waitFor();
  await openMenu();
  assert.equal((await account.textContent()).trim(),accountLabel);
  assert.equal(await account.getAttribute('href'),accountHref);
  assert.equal(await signup.isHidden(),true);
  await closeMenu();

  // Reload restores the session through the refresh call
  await page.reload({waitUntil:'networkidle'});
  await page.locator('.welcome-done').waitFor();
  await openMenu();
  assert.equal((await account.textContent()).trim(),accountLabel);
  assert.equal(await account.getAttribute('href'),accountHref);
  await closeMenu();

  // Logout returns the menu to the guest state
  await page.locator('.done-signout').click();
  await page.locator('.welcome-form input[name=email]').waitFor();
  await openMenu();
  assert.equal((await account.textContent()).trim(),guestLabel);
  assert.equal(await account.getAttribute('href'),localizedPath('/login/',lang));
  assert.equal(await signup.isVisible(),true);
  await closeMenu();

  // Another page with an existing session
  signedIn=true;
  await page.goto(base+localizedPath('/about-us/',lang),{waitUntil:'networkidle'});
  await openMenu();
  assert.equal(await account.getAttribute('href'),accountHref);
  assert.equal(await account.getAttribute('aria-current'),null);
  checks++;await context.close();
 }
 console.log(JSON.stringify({checks,passed:true,scope:'Real client auth code with synthetic OTP/refresh/logout responses; guest, signed-in, reload and logout at 390/1280 in en/ar/zh'}));
}finally{await browser.close();}
