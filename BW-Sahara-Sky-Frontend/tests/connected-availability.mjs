import {createRequire} from 'node:module';
import assert from 'node:assert/strict';
const {chromium}=createRequire(import.meta.url)('playwright');
const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
const page=await browser.newPage({serviceWorkers:'block'}),base='http://127.0.0.1:8782';
try {
 await page.goto(base+'/booking/?lang=en');
 await page.locator('#booking-search').waitFor();
 assert(await page.locator('.booking-results').evaluate(n=>n.hidden));
 const arrival=new Date(),departure=new Date();arrival.setDate(arrival.getDate()+60);departure.setDate(departure.getDate()+61);
 const iso=d=>`${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
 await page.locator('#booking-search input[name=arrival]').fill(iso(arrival));
 await page.locator('#booking-search input[name=departure]').fill(iso(departure));
 await page.locator('#booking-search select[name=adults]').selectOption('1');
 const response=page.waitForResponse(r=>r.url().includes('/api/hotel/availability?'));
 await page.locator('#booking-search button[type=submit]').click();
 const result=await response;assert.equal(result.status(),200);const data=await result.json();
 await page.waitForFunction(()=>!document.querySelector('.booking-results').hidden);
 assert.equal(await page.locator('.booking-results [data-backend-room-id]').count(),data.rooms.length);
 assert.equal(await page.locator('.booking-results [data-select-room]').count(),0);
 assert((await page.locator('.booking-results .preview-note').textContent()).includes('No reservation'));
 assert(result.headers()['cache-control'].includes('no-store'));
 console.log(JSON.stringify({passed:true,checks:['live availability rendered','preview room selectors removed','no reservation disclaimer','availability not cached'],rooms:data.rooms.length}));
} finally {await browser.close();}

