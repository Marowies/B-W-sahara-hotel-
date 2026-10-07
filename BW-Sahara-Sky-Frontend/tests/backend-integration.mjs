import {createRequire} from 'node:module';
import {readFile,writeFile} from 'node:fs/promises';
import assert from 'node:assert/strict';
const require=createRequire(import.meta.url),{chromium}=require('playwright');
const base=process.env.HOTEL_TEST_URL||'http://127.0.0.1:8782';
assert.equal(new URL(base).hostname,'127.0.0.1','Integration checks must remain local');
assert(process.env.HOTEL_ADMIN_CONFIG,'Set HOTEL_ADMIN_CONFIG to a private local test-account file');
assert(process.env.HOTEL_LIMITED_CONFIG,'Set HOTEL_LIMITED_CONFIG to a private restricted-account file');
const admin=JSON.parse(await readFile(process.env.HOTEL_ADMIN_CONFIG,'utf8'));
const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
const context=await browser.newContext({viewport:{width:1366,height:900},serviceWorkers:'block'});
const page=await context.newPage(),checks=[],errors=[];
page.on('pageerror',e=>errors.push(e.message));
await page.route('**/*',route=>new URL(route.request().url()).hostname==='127.0.0.1'?route.continue():route.abort());
try {
 const catalogue=await page.request.get(base+'/api/hotel/rooms');assert.equal(catalogue.status(),200);const rows=(await catalogue.json()).rooms;assert(rows.length>0);assert(!JSON.stringify(rows).includes('password'));checks.push('published hotel catalogue');
 const future=new Date(Date.now()+10*86400000).toISOString().slice(0,10);const invalid=await page.request.get(base+'/api/hotel/availability?'+new URLSearchParams({arrival:future,departure:future,adults:'1',children:'0'}),{headers:{Accept:'application/json'}});assert.equal(invalid.status(),422);checks.push('invalid stay rejected');
 const guest=await page.request.get(base+'/admin/hotel/rooms',{maxRedirects:0});assert([302,401].includes(guest.status()));checks.push('admin rooms protected from guests');
 const csrf=await page.request.post(base+'/api/hotel/contact',{headers:{Accept:'application/json'},maxRedirects:0,data:{name:'Probe',email:'probe@example.test',subject:'Test',message:'CSRF rejection probe'}});assert.equal(csrf.status(),419);checks.push('contact CSRF protection');
 await page.goto(base+'/login/admin',{waitUntil:'networkidle'});assert(page.url().includes('/admin/login'));await page.screenshot({path:'test-results/integrated-admin-login.png',timeout:5000}).catch(()=>{});
 await page.locator('input[name=username]').fill(admin.username);await page.locator('input[name=password]').fill(admin.password);await page.locator('button[type=submit]').click({noWaitAfter:true});await page.waitForURL(url=>url.pathname.startsWith('/admin')&&!url.pathname.includes('/login'),{timeout:60000});await page.goto(base+'/admin',{waitUntil:'networkidle',timeout:60000});assert.equal(await page.locator('link[href*=bw-admin]').count(),1);await page.screenshot({path:'test-results/integrated-admin-dashboard.png',timeout:5000}).catch(()=>{});checks.push('real admin authentication and branded dashboard');
 const roomsAdmin=await page.request.get(base+'/admin/hotel/rooms');assert.equal(roomsAdmin.status(),200);const body=await roomsAdmin.text();assert(body.includes('table')||body.includes('Rooms'));checks.push('authenticated rooms administration');
 await page.goto(base+'/rooms/?lang=en',{waitUntil:'networkidle'});await page.waitForFunction(()=>document.querySelectorAll('[data-backend-room-id]').length>0);assert.equal(await page.locator('[data-backend-room-id]').count(),rows.length);checks.push('frontend room cards use backend records');
 await page.goto(base+'/contact-us/?lang=en',{waitUntil:'networkidle'});await page.locator('#contact-form input[name=name]').fill('Local integration test');await page.locator('#contact-form input[name=email]').fill('integration@example.test');await page.locator('#contact-form textarea[name=message]').fill('Local frontend integration verification 2026-10-07.');const saved=page.waitForResponse(r=>r.url().endsWith('/api/hotel/contact')&&r.request().method()==='POST');await page.locator('#contact-form button[type=submit]').click();assert.equal((await saved).status(),201);checks.push('frontend enquiry saved to real CMS inbox');
 for(const path of ['/admin/hotel/bookings','/admin/hotel/rooms/edit/'+rows[0].id,'/admin/pages','/admin/galleries']){const r=await page.request.get(base+path);assert.equal(r.status(),200,path);}checks.push('booking list, room editor, pages and galleries accessible');
 const contactAdmin=await page.request.get(base+'/admin/contacts');assert.equal(contactAdmin.status(),200);checks.push('admin contact inbox accessible');
 await page.goto(base+'/admin',{waitUntil:'networkidle'});await page.setViewportSize({width:390,height:844});await page.screenshot({path:'test-results/integrated-admin-mobile.png'});assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+2));assert(await page.locator('.navbar-vertical .navbar-brand-image').evaluate(n=>n.getBoundingClientRect().height<=60));checks.push('admin mobile layout without page overflow');
 const limited=JSON.parse(await readFile(process.env.HOTEL_LIMITED_CONFIG,'utf8'));const restricted=await browser.newContext({serviceWorkers:'block'});const rp=await restricted.newPage();await rp.goto(base+'/admin/login');await rp.locator('input[name=username]').fill(limited.username);await rp.locator('input[name=password]').fill(limited.password);await rp.locator('button[type=submit]').click({noWaitAfter:true});await rp.waitForURL(u=>!u.pathname.includes('/login'));for(const path of ['/admin/hotel/rooms','/admin/hotel/bookings','/admin/contacts']){const denied=await restricted.request.get(base+path,{maxRedirects:0});assert.equal(denied.status(),302,path);assert.equal(new URL(denied.headers().location,base).pathname,'/admin');const jsonDenied=await restricted.request.get(base+path,{headers:{Accept:'application/json'},maxRedirects:0});assert.equal(jsonDenied.status(),401,path);}await restricted.close();checks.push('staff without permissions blocked from rooms, bookings and contacts');
 assert.deepEqual(errors,[]);const report={passed:true,checks,roomCount:rows.length,pageErrors:errors};await writeFile('test-results/backend-integration.json',JSON.stringify(report,null,2));console.log(JSON.stringify(report));
}catch(error){await page.screenshot({path:'test-results/integration-failure.png',timeout:5000}).catch(()=>{});console.error(JSON.stringify({passed:false,checks,error:error.message,pageErrors:errors}));process.exitCode=1;}finally{await browser.close();}







