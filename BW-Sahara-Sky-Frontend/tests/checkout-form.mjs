// Final booking form UX on the real stack (same setup as tests/account-session.mjs). No booking is
// submitted: the form's own controls are exercised and only Laravel's checkout URL is asserted.
//   HOTEL_TEST_URL=http://127.0.0.1:8782 HOTEL_SMTP_SINK_PORT=2525 node tests/checkout-form.mjs
import assert from 'node:assert/strict';
import {mkdirSync} from 'node:fs';
import {chromium} from 'playwright';
import {localizedPath} from '../src/shared/locale.mjs';
import {startSmtpSink} from './support/smtp-sink.mjs';

const base = process.env.HOTEL_TEST_URL || 'http://127.0.0.1:8782';
const shots = new URL('../test-results/checkout-form/', import.meta.url);
mkdirSync(shots, {recursive: true});
const sink = await startSmtpSink(Number(process.env.HOTEL_SMTP_SINK_PORT || 2525));
const results = [];
const check = (name, ok, detail = '') => {results.push({name, ok: !!ok});if (!ok) console.error('FAIL', name, detail);};
let last = 0;
const pace = async () => {const wait = 3000 - (Date.now() - last);if (wait > 0) await new Promise(r => setTimeout(r, wait));last = Date.now();};
const day = n => new Date(Date.now() + n * 864e5).toISOString().slice(0, 10);

const browser = await chromium.launch({headless: true, ...(process.env.HOTEL_BROWSER_PATH ? {executablePath: process.env.HOTEL_BROWSER_PATH} : {})});
try {
 for (const [lang, width, who] of [['en', 1280, 'c'], ['ar', 390, 'd'], ['zh', 390, 'e']]) {
  const tag = `${lang}-${width}`, email = `fixture.guest.${who}@example.invalid`;
  const context = await browser.newContext({viewport: {width, height: 900}, serviceWorkers: 'block'});
  await context.addCookies([{name: 'hotel_cookie_consent', value: 'necessary', url: base}]);
  const page = await context.newPage();
  const errors = [];page.on('pageerror', e => errors.push(e.message));
  // Real OTP sign-in.
  await pace();await page.goto(base + localizedPath('/login/', lang), {waitUntil: 'networkidle'});
  await page.locator('.welcome-form input[name=email]').fill(email);
  const since = Date.now();
  await page.locator('.welcome-form>button[type=submit]').click();
  await page.locator('input[name=code]').fill(await sink.codeFor(email, since));
  await page.locator('.welcome-done').waitFor({timeout: 15000});
  // Room review -> "Open booking form" -> /booking/{token}.
  await pace();await page.goto(base + localizedPath('/booking/', lang), {waitUntil: 'networkidle'});
  await page.evaluate(([a, d]) => sessionStorage.setItem('sahara-stay-preview', JSON.stringify({arrival: a, departure: d, adults: '2', children: '0', experience: 'room', roomId: 2, backendRoomId: 1})), [day(45), day(47)]);
  await pace();await page.goto(base + localizedPath('/booking/review/', lang), {waitUntil: 'networkidle'});
  await pace();await Promise.all([page.waitForURL(/\/booking\/[a-f0-9]{32}\?lang=/, {timeout: 20000}), page.locator('.booking-aside button.button').click()]);
  await page.waitForLoadState('networkidle');
  const form = page.locator('form.payment-checkout-form');
  check(`${tag} booking form posts to Laravel checkout`, (await form.getAttribute('action')).endsWith('/checkout'));
  const button = page.locator('.payment-checkout-btn');
  const services = page.locator('.service-item');
  check(`${tag} extra services listed`, await services.count() > 0);

  // Recalculation in progress: the booking button waits; on success it is enabled and the total changes.
  const total = async () => (await page.locator('.total-amount-text').textContent()).trim();
  const before = await total();
  let release;
  await page.route('**/ajax/calculate-amount**', async route => {await new Promise(r => (release = r));await route.continue();});
  await services.first().check();
  await page.waitForTimeout(300);
  check(`${tag} booking button disabled while the total is recalculated`, await button.isDisabled());
  release();
  await page.waitForFunction(() => !document.querySelector('.payment-checkout-btn').disabled, null, {timeout: 15000});
  await page.unroute('**/ajax/calculate-amount**');
  check(`${tag} total updated after adding a service`, (await total()) !== before, `${before} -> ${await total()}`);

  // Recalculation fails: the button must not stay disabled, and the guest is told.
  await page.route('**/ajax/calculate-amount**', route => route.fulfill({status: 500, json: {message: 'Synthetic failure'}}));
  await services.first().uncheck();
  await page.waitForTimeout(1200);
  check(`${tag} booking button re-enabled after a failed recalculation`, !(await button.isDisabled()));
  check(`${tag} failed recalculation is reported`, await page.locator('#toast-container .toast, .toast-error').count() > 0);
  await page.unroute('**/ajax/calculate-amount**');
  await page.locator('#toast-container .toast').evaluateAll(t => t.forEach(x => x.remove())).catch(() => {});

  // Coupon: Enter applies instead of submitting the booking; an unknown code gets visible feedback.
  await page.locator('.toggle-coupon-form').click();
  const coupon = page.locator('#coupon_code');
  await coupon.waitFor();
  await coupon.fill('FIXTURE-NOT-A-COUPON');
  const url = page.url();
  let checkoutPosted = false;
  page.on('request', r => {if (r.method() === 'POST' && new URL(r.url()).pathname === '/checkout') checkoutPosted = true;});
  const applied = page.waitForResponse(r => r.url().endsWith('/coupon/apply'), {timeout: 10000}).then(() => true, () => false);
  await coupon.press('Enter');
  check(`${tag} Enter in the coupon field applies the coupon`, await applied);
  await page.waitForTimeout(800);
  check(`${tag} Enter in the coupon field does not submit the booking`, !checkoutPosted && page.url() === url);
  check(`${tag} unknown coupon gets visible feedback`, await page.locator('#toast-container .toast').count() > 0);
  check(`${tag} no horizontal overflow`, await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
  check(`${tag} no page errors`, errors.length === 0, errors.join(' | '));
  await page.screenshot({path: new URL(`booking-form-${tag}.png`, shots).pathname.slice(1), fullPage: true});
  await context.close();
 }
} finally {
 await browser.close();await sink.close();
 const failed = results.filter(r => !r.ok);
 console.log(JSON.stringify({checks: results.length, passed: results.length - failed.length, failed: failed.length}));
 if (failed.length) process.exitCode = 1;
}
