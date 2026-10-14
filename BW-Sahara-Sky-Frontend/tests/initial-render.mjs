import {fileURLToPath} from 'node:url';
// Initial render and release transitions: the brown/beige design from the first paint, and old
// release pages replaced without showing the old design or losing unsaved input.
//   npm run build && node tools/serve.mjs   (or the connected gateway)
//   HOTEL_TEST_URL=http://127.0.0.1:8782 node tests/initial-render.mjs
import assert from 'node:assert/strict';
import {readdirSync, readFileSync, statSync} from 'node:fs';
import {join} from 'node:path';
import {chromium} from 'playwright';
import {localizedPath} from '../src/shared/locale.mjs';

const base = process.env.HOTEL_TEST_URL || 'http://127.0.0.1:8780';
// fileURLToPath decodes non-ASCII folder names (a URL pathname keeps them percent-encoded).
const dist = fileURLToPath(new URL('../dist/', import.meta.url));
const results = [];
const check = (name, ok, detail = '') => {results.push({name, ok: !!ok});if (!ok) console.error('FAIL', name, detail);};
let last = 0;
const pace = async () => {const wait = 1700 - (Date.now() - last);if (wait > 0) await new Promise(r => setTimeout(r, wait));last = Date.now();};

// Burgundy: red-purple hue, clearly saturated, not light. Brown/beige/copper sit at 20-40 degrees.
const burgundy = (r, g, b) => {
 [r, g, b] = [r, g, b].map(v => v / 255);
 const max = Math.max(r, g, b), min = Math.min(r, g, b), d = max - min;if (!d) return false;
 let h = max === r ? ((g - b) / d) % 6 : max === g ? (b - r) / d + 2 : (r - g) / d + 4;h = (h * 60 + 360) % 360;
 const l = (max + min) / 2, s = d / (1 - Math.abs(2 * l - 1));
 return (h >= 315 || h < 5) && s > 0.25 && l < 0.6;
};
const hex = value => {const v = value.replace('#', '');return [0, 2, 4].map(i => parseInt(v.slice(i, i + 2), 16));};

// 1. Built output: theme-color and every stylesheet colour, including URL-encoded SVG strokes.
const files = [];
const walk = dir => {for (const name of readdirSync(dir)) {const path = join(dir, name);if (statSync(path).isDirectory()) walk(path);else if (/\.(html|css)$/.test(name)) files.push(path);}};
walk(dist);
assert.ok(files.length > 50, 'Build the site first (dist/ is empty)');
let themeColors = 0;
for (const file of files) {
 const text = readFileSync(file, 'utf8');
 for (const m of text.matchAll(/<meta name="theme-color" content="(#[0-9a-f]{6})"/gi)) {themeColors++;check(`theme-color brown ${file.slice(dist.length)}`, m[1].toLowerCase() === '#60452f', m[1]);}
 for (const m of text.matchAll(/(?:#|%23)([0-9a-f]{6})\b/gi)) if (burgundy(...hex(m[1]))) check(`no burgundy colour in ${file.slice(dist.length)}`, false, m[0]);
}
check('every page declares the brown theme-color', themeColors >= 90, String(themeColors));

const browser = await chromium.launch({headless: true, ...(process.env.HOTEL_BROWSER_PATH ? {executablePath: process.env.HOTEL_BROWSER_PATH} : {})});
// Colours of the main surfaces when the DOM is first ready versus after everything (including JS) ran.
const sample = () => {
 const pick = sel => {const el = document.querySelector(sel);if (!el) return null;const s = getComputedStyle(el);return [s.backgroundColor, s.color].join('|');};
 return ['body', 'header.nav', 'main', '.button', 'footer', 'h1'].map(pick).join(' ; ');
};
const visibleBurgundy = () => {
 const bad = [];
 const rgb = v => v.match(/\d+(\.\d+)?/g)?.slice(0, 4).map(Number);
 const isBurgundy = ([r, g, b, a = 1]) => {
  if (a < 0.3) return false;[r, g, b] = [r, g, b].map(v => v / 255);
  const max = Math.max(r, g, b), min = Math.min(r, g, b), d = max - min;if (!d) return false;
  let h = max === r ? ((g - b) / d) % 6 : max === g ? (b - r) / d + 2 : (r - g) / d + 4;h = (h * 60 + 360) % 360;
  const l = (max + min) / 2, s = d / (1 - Math.abs(2 * l - 1));return (h >= 315 || h < 5) && s > 0.25 && l < 0.6;
 };
 for (const el of document.querySelectorAll('body *')) {
  const box = el.getBoundingClientRect();if (!box.width || !box.height || box.top > innerHeight) continue;
  const s = getComputedStyle(el);
  for (const prop of ['backgroundColor', 'color', 'borderTopColor']) {const v = rgb(s[prop]);if (v && isBurgundy(v)) bad.push(el.tagName + '.' + el.className + ' ' + prop + ' ' + s[prop]);}
 }
 return bad.slice(0, 5);
};
try {
 // 2. Cold load, reload and two tabs at once: identical colours before and after scripts, no burgundy.
 for (const lang of ['en', 'ar', 'zh']) for (const width of [390, 1280]) {
  const tag = `${lang}-${width}`;
  const context = await browser.newContext({viewport: {width, height: 900}, serviceWorkers: 'block'});
  await context.addInitScript(`document.addEventListener('DOMContentLoaded',()=>{window.__firstStyles=(${sample})();},{once:true});`);
  const page = await context.newPage();
  for (const path of [localizedPath('/', lang), localizedPath('/rooms/', lang)]) {
   for (const step of ['cold', 'reload']) {
    await pace();
    if (step === 'cold') await page.goto(base + path, {waitUntil: 'networkidle'});else await page.reload({waitUntil: 'networkidle'});
    const [first, settled] = [await page.evaluate(() => window.__firstStyles), await page.evaluate(sample)];
    check(`${tag} ${path} ${step}: same colours at first render and after load`, first === settled, `${first}\n   vs ${settled}`);
    const bad = await page.evaluate(visibleBurgundy);
    check(`${tag} ${path} ${step}: no burgundy on screen`, !bad.length, bad.join(', '));
   }
  }
  if (width === 1280) {
   await pace();
   const [one, two] = [page, await context.newPage()];
   await Promise.all([one.goto(base + localizedPath('/about-us/', lang), {waitUntil: 'networkidle'}), two.goto(base + localizedPath('/contact-us/', lang), {waitUntil: 'networkidle'})]);
   for (const [i, tab] of [one, two].entries()) check(`${tag} concurrent tab ${i + 1}: no burgundy`, !(await tab.evaluate(visibleBurgundy)).length);
  }
  await context.close();
 }

 // 3. Release transitions. The newest release is simulated by recording a different runtime path,
 //    exactly as a page of the newer release does, and by the manifest naming that runtime.
 const newer = '/runtime.ffffffffffff.js';
 {
  const context = await browser.newContext({viewport: {width: 390, height: 900}, serviceWorkers: 'block'});
  const page = await context.newPage();
  await pace();await page.goto(base + '/about-us/', {waitUntil: 'networkidle'});
  await page.evaluate(() => {window.__stale = true;});
  await pace();await page.goto(base + '/contact-us/', {waitUntil: 'networkidle'});
  await page.evaluate(v => localStorage.setItem('hotel-release', v), newer);
  await pace();await page.goBack({waitUntil: 'networkidle'});
  await page.waitForTimeout(800);
  const type = await page.evaluate(() => performance.getEntriesByType('navigation')[0]?.type);
  check('back/forward: an old-release page is not left on screen', !(await page.evaluate(() => window.__stale)), type);
  console.log('back/forward restore type:', type);
  await context.close();
 }
 for (const edited of [false, true]) {
  const context = await browser.newContext({viewport: {width: 1280, height: 900}, serviceWorkers: 'block'});
  const tab = await context.newPage(), other = await context.newPage();
  await pace();await tab.goto(base + localizedPath('/contact-us/', 'ar'), {waitUntil: 'networkidle'});
  await tab.evaluate(() => {window.__stale = true;});
  if (edited) await tab.locator('#contact-form textarea, #contact-form input[name=name]').first().fill('رسالة لم تُرسل بعد');
  // The tab is in the background while another tab of the new release opens.
  await tab.evaluate(() => {Object.defineProperty(document, 'visibilityState', {configurable: true, get: () => 'hidden'});});
  await context.route('**/build-manifest.json', route => route.fulfill({json: {files: {'runtime.js': newer}}}));
  await pace();await other.goto(base + '/', {waitUntil: 'networkidle'});
  await other.evaluate(v => localStorage.setItem('hotel-release', v), newer);
  await tab.waitForTimeout(2500);
  await context.unroute('**/build-manifest.json');
  if (!edited) check('hidden tab: updated in the background before it is shown', !(await tab.evaluate(() => window.__stale).catch(() => false)));
  else {
   const value = await tab.locator('#contact-form textarea, #contact-form input[name=name]').first().inputValue();
   check('unsaved input: kept, and an update notice offered instead of a reload', await tab.evaluate(() => window.__stale) === true && value === 'رسالة لم تُرسل بعد' && await tab.locator('button', {hasText: 'تحديث الموقع'}).count() === 1, value);
  }
  await context.close();
 }
} finally {
 await browser.close();
 const failed = results.filter(r => !r.ok);
 console.log(JSON.stringify({checks: results.length, passed: results.length - failed.length, failed: failed.length}));
 if (failed.length) process.exitCode = 1;
}
