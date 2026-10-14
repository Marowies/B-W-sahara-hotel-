// My account <-> customer session regression (Staging: "My account" -> /login/ -> "You're signed in").
// Real stack only: frontend gateway (tools/serve.mjs with HOTEL_BACKEND_ORIGIN) -> local Laravel fixture
// (MAIL_MAILER=smtp to this test's loopback sink, seeded by tests/Fixtures/seed-account-fixture.php).
// Nothing is mocked: OTP, refresh rotation, the customer guard and logout are the real endpoints.
// Fixture .env (local only): MAIL_MAILER=smtp, MAIL_HOST=127.0.0.1, MAIL_PORT=2525, MAIL_ENCRYPTION=null and
// CMS_ENABLE_EMAIL_CONFIGURATION_FROM_ADMIN_PANEL=false (otherwise the CMS mail setting overrides .env).
// The OTP endpoint refuses log/array mailers by design, hence the loopback sink.
//   HOTEL_TEST_URL=http://127.0.0.1:8782 HOTEL_SMTP_SINK_PORT=2525 node tests/account-session.mjs
import assert from 'node:assert/strict';
import {mkdirSync} from 'node:fs';
import {chromium} from 'playwright';
import {localizedPath} from '../src/shared/locale.mjs';
import {startSmtpSink} from './support/smtp-sink.mjs';

const base = process.env.HOTEL_TEST_URL || 'http://127.0.0.1:8782';
const shots = new URL('../test-results/account-session/', import.meta.url);
mkdirSync(shots, {recursive: true});
const sink = await startSmtpSink(Number(process.env.HOTEL_SMTP_SINK_PORT || 2525));
const fixture = {
 a: {email: 'fixture.guest.a@example.invalid', name: 'Fixture', booking: 'FIXTURE-1001'},
 b: {email: 'fixture.guest.b@example.invalid', name: 'Sample', booking: null},
 c: {email: 'fixture.guest.c@example.invalid', name: 'Fixture', booking: 'FIXTURE-C-CANCELLED'},
 d: {email: 'fixture.guest.d@example.invalid', name: 'Fixture', booking: 'FIXTURE-D-TODAY'},
 e: {email: 'fixture.guest.e@example.invalid', name: 'Fixture', booking: 'FIXTURE-E-NOROOM'},
};
// Optional subset, e.g. HOTEL_SESSION_SECTIONS=isolation (default: matrix,invalid,isolation).
const sections = new Set((process.env.HOTEL_SESSION_SECTIONS || 'matrix,invalid,isolation').split(','));
const results = [];
const check = (name, ok, detail = '') => {results.push({name, ok: !!ok, detail: ok ? '' : String(detail)});if (!ok) console.error('FAIL', name, detail);};
// Keep under the gateway page limit (40/min) and the refresh throttle (30/min per client).
let last = 0;
const pace = async (ms = 3000) => {const wait = ms - (Date.now() - last);if (wait > 0) await new Promise(r => setTimeout(r, wait));last = Date.now();};
const go = async (page, path) => {await pace();return page.goto(base + path, {waitUntil: 'networkidle'});};
// The OTP endpoint allows one code per address per minute.
const lastCode = {};
async function signIn(page, who, lang) {
 const wait = 62000 - (Date.now() - (lastCode[who.email] || 0));
 if (wait > 0) await new Promise(r => setTimeout(r, wait));
 await go(page, localizedPath('/login/', lang));
 await page.locator('.welcome-form input[name=email]').fill(who.email);
 const since = Date.now();lastCode[who.email] = since;
 await page.locator('.welcome-form>button[type=submit]').click();
 const code = await sink.codeFor(who.email, since);
 await page.locator('input[name=code]').fill(code);
 await page.locator('.welcome-done').waitFor({timeout: 15000});
}
// Simulates the Laravel session expiring (lifetime 120 min) while the 30-day refresh cookie remains.
async function expirePageSession(context) {
 const cookies = await context.cookies();
 const session = cookies.find(c => !['hotel_refresh', 'hotel_locale', 'hotel_cookie_consent', 'XSRF-TOKEN'].includes(c.name) && c.httpOnly);
 assert.ok(session, 'Laravel session cookie not found');
 await context.clearCookies({name: session.name});
 return session.name;
}
const accountLink = page => page.locator('#account-links .nav-signin');
async function openAccountFromHeader(page, lang) {
 let refresh = 'none';
 const seen = r => {if (r.url().endsWith('/api/hotel/auth/refresh')) refresh = r.status();};
 page.on('response', seen);
 await go(page, localizedPath('/rooms/', lang));
 const signedIn = await page.waitForFunction(() => document.querySelector('#account-links .nav-signin')?.getAttribute('href')?.startsWith('/customer/'), null, {timeout: 15000}).then(() => true, () => false);
 page.off('response', seen);
 if (!signedIn) throw new Error('header not signed in after loading the page; refresh status ' + refresh);
 await page.locator('header.nav .account-toggle').click();
 await pace();
 await Promise.all([page.waitForURL(/\/customer\/overview/, {timeout: 20000}), accountLink(page).click()]);
 await page.waitForLoadState('networkidle');
}
async function dashboardShows(page, who, lang, tag) {
 const h1 = (await page.locator('main h1').textContent().catch(() => '')) || '';
 check(`${tag} dashboard opened`, new URL(page.url()).pathname === '/customer/overview' && h1.includes(who.name), `${page.url()} ${h1}`);
 check(`${tag} dashboard language`, await page.getAttribute('html', 'lang') === lang && await page.getAttribute('.sky-guest', 'dir') === (lang === 'ar' ? 'rtl' : 'ltr'));
 const text = await page.locator('main').innerText().catch(() => '');
 if (who.booking) check(`${tag} own booking shown`, text.includes(who.booking), text.slice(0, 200));
 else check(`${tag} empty state shown`, await page.locator('.sky-card-booking a.sky-button').getAttribute('href') === localizedPath('/booking/', lang) && !/FIXTURE-/.test(text));
 for (const other of Object.values(fixture)) if (other !== who && other.booking && other.booking !== who.booking) check(`${tag} no ${other.booking}`, !text.includes(other.booking));
}

const browser = await chromium.launch({headless: true, ...(process.env.HOTEL_BROWSER_PATH ? {executablePath: process.env.HOTEL_BROWSER_PATH} : {})});
const throttled = [];
const newContext = async width => {const context = await browser.newContext({viewport: {width, height: 900}, serviceWorkers: 'block'});context.on('response', r => {if (r.url().endsWith('/api/hotel/auth/refresh') && r.status() === 429) throttled.push(r.url());});await context.addCookies([{name: 'hotel_cookie_consent', value: 'necessary', url: base}]);return context;};
try {
 const rotation = ['a', 'c', 'd', 'e', 'a', 'c'];
 let run = 0;
 for (const lang of sections.has('matrix') ? ['en', 'ar', 'zh'] : []) for (const width of [390, 1280]) {
  const tag = `${lang}-${width}`, who = fixture[rotation[run++]];
  const context = await newContext(width), page = await context.newPage();
  const errors = [];page.on('pageerror', e => errors.push(e.message));
  await signIn(page, who, lang);

  // 1. Header "My account" with an expired Laravel session and a valid refresh cookie.
  await expirePageSession(context);
  await openAccountFromHeader(page, lang);
  await dashboardShows(page, who, lang, `${tag} header`);
  await page.screenshot({path: new URL(`dashboard-${tag}.png`, shots).pathname.slice(1), fullPage: true});

  // 2. Bookmarked account page after the session expired: /login/?next= restores and returns.
  await expirePageSession(context);
  await pace();
  await page.goto(base + '/customer/bookings?lang=' + lang, {waitUntil: 'networkidle'});
  await page.waitForURL(u => u.pathname === '/customer/bookings', {timeout: 20000}).catch(() => {});
  check(`${tag} bookmarked account page restored`, new URL(page.url()).pathname === '/customer/bookings' && await page.locator('main h1').count() === 1, page.url());

  // 3. Reload, back and forward stay on the real pages.
  await pace();await page.reload({waitUntil: 'networkidle'});
  check(`${tag} reload keeps account page`, new URL(page.url()).pathname === '/customer/bookings');
  await pace();await page.goBack({waitUntil: 'networkidle'});
  await pace();await page.goForward({waitUntil: 'networkidle'});
  check(`${tag} back/forward keeps account page`, new URL(page.url()).pathname === '/customer/bookings', page.url());

  if (width === 1280) {
   // 4. Reopening the site (new window from saved cookies) and two tabs refreshing at the same time.
   const state = await context.storageState();
   const reopened = await browser.newContext({viewport: {width, height: 900}, serviceWorkers: 'block', storageState: state});
   const [one, two] = [await reopened.newPage(), await reopened.newPage()];
   await pace();
   await Promise.all([one.goto(base + localizedPath('/rooms/', lang), {waitUntil: 'networkidle'}), two.goto(base + localizedPath('/about-us/', lang), {waitUntil: 'networkidle'})]);
   for (const [i, tab] of [one, two].entries()) {
    await tab.waitForFunction(() => document.querySelector('#account-links .nav-signin')?.getAttribute('href')?.startsWith('/customer/'), null, {timeout: 15000}).then(() => {}, () => {});
    check(`${tag} tab ${i + 1} signed in after concurrent refresh`, (await accountLink(tab).getAttribute('href'))?.startsWith('/customer/overview'));
   }
   await pace();await two.goto(base + '/customer/overview?lang=' + lang, {waitUntil: 'networkidle'});
   await dashboardShows(two, who, lang, `${tag} second tab`);
   await reopened.close();
  }

  // 5. Logout from the dashboard closes both sessions; back does not reveal the account.
  await go(page, '/customer/overview?lang=' + lang);
  await pace();
  await Promise.all([page.waitForURL(u => u.pathname === localizedPath('/login/', lang), {timeout: 20000}), page.locator('.sky-account-nav .sky-account-signout').click()]);
  await page.locator('.welcome-form input[name=email]').waitFor({timeout: 15000});
  check(`${tag} logout shows guest sign-in`, await accountLink(page).getAttribute('href') === localizedPath('/login/', lang));
  await pace();await page.goBack({waitUntil: 'networkidle'}).catch(() => {});
  await page.waitForLoadState('networkidle');
  const afterBack = await page.locator('body').innerText();
  check(`${tag} back after logout hides account data`, !afterBack.includes(who.email) && !(who.booking && afterBack.includes(who.booking)), page.url());
  await go(page, '/customer/overview?lang=' + lang);
  await page.locator('.welcome-form input[name=email]').waitFor({timeout: 15000}).then(() => {}, () => {});
  check(`${tag} account closed after logout (no redirect loop)`, new URL(page.url()).pathname === localizedPath('/login/', lang) && await page.locator('.welcome-form').count() === 1, page.url());
  check(`${tag} no page errors`, errors.length === 0, errors.join(' | '));
  await context.close();
 }

 // 6. Invalid refresh cookie: no session, no loop.
 if (sections.has('invalid')) {
  const context = await newContext(390), page = await context.newPage();
  await context.addCookies([{name: 'hotel_refresh', value: 'not-a-valid-token', url: base + '/api/hotel/auth', httpOnly: true}]);
  await go(page, '/customer/overview?lang=ar');
  await page.locator('.welcome-form input[name=email]').waitFor({timeout: 15000}).then(() => {}, () => {});
  check('invalid token stays signed out without a loop', new URL(page.url()).pathname === localizedPath('/login/', 'ar') && await page.locator('.welcome-form').count() === 1, page.url());
  await context.close();
 }

 // 7. Two customers: separate browsers stay isolated; switching account in one browser shows only the new customer.
 if (sections.has('isolation')) {
  const first = await newContext(1280), second = await newContext(390);
  const p1 = await first.newPage(), p2 = await second.newPage();
  await signIn(p1, fixture.d, 'en');
  await signIn(p2, fixture.b, 'zh');
  await go(p1, '/customer/overview?lang=en');await dashboardShows(p1, fixture.d, 'en', 'isolation customer D');
  await go(p2, '/customer/overview?lang=zh');await dashboardShows(p2, fixture.b, 'zh', 'isolation customer B (empty)');
  await p2.screenshot({path: new URL('dashboard-empty-zh-390.png', shots).pathname.slice(1), fullPage: true});
  await pace();
  await Promise.all([p1.waitForURL(u => u.pathname === '/login/'), p1.locator('.sky-account-nav .sky-account-signout').click()]);
  await signIn(p1, fixture.e, 'en');
  await expirePageSession(first);
  await openAccountFromHeader(p1, 'en');
  await dashboardShows(p1, fixture.e, 'en', 'switched customer E');
  await first.close();await second.close();
 }
} finally {
 await browser.close();await sink.close();
 check('refresh never throttled during the run', throttled.length === 0, throttled.length + ' refresh responses were 429');
 const failed = results.filter(r => !r.ok);
 console.log(JSON.stringify({checks: results.length, passed: results.length - failed.length, failed: failed.length, failures: failed}, null, 1));
 if (failed.length) process.exitCode = 1;
}
