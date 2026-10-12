// Real (local) admin on Android Chromium + iOS WebKit: overflow, JS errors, failed requests, iOS input zoom.
import {chromium,webkit,devices} from 'playwright';import {readFile,mkdir,writeFile} from 'node:fs/promises';
const admin=JSON.parse(await readFile('../BW-Sahara-Sky-Hotel/tmp/local-integration-20261007/private-admin-access.json','utf8'));
const B='http://127.0.0.1:8782',out='test-results/admin-devices';await mkdir(out,{recursive:true});
const pages=['/admin','/admin/hotel/bookings','/admin/hotel/rooms','/admin/hotel/rooms/edit/1','/admin/hotel/rooms/create','/admin/hotel/room-categories','/admin/hotel/customers','/admin/hotel/coupons','/admin/hotel/booking-reports','/admin/media','/admin/pages','/admin/blog/posts','/admin/settings/general','/admin/system/users','/admin/contacts','/admin/menus'];
const targets=[['android-pixel7',chromium,devices['Pixel 7']],['ios-iphone15',webkit,devices['iPhone 15']],['ios-iphoneSE',webkit,devices['iPhone SE']]];
const report=[];
for(const [name,engine,dev] of targets){const b=await engine.launch();const ctx=await b.newContext({...dev,serviceWorkers:'block'});const p=await ctx.newPage();p.setDefaultTimeout(45000);
 const errs=[];p.on('pageerror',e=>errs.push('JS: '+e.message.slice(0,120)));p.on('response',r=>{if(r.status()>=400)errs.push(r.status()+' '+r.url().replace(B,''));});
 await p.goto(B+'/admin/login');await p.fill('[name=username]',admin.username);await p.fill('[name=password]',admin.password);await Promise.all([p.waitForURL(u=>!u.pathname.includes('/login')),p.click('button[type=submit]')]);
 for(const path of pages){errs.length=0;let r;
  try{const resp=await p.goto(B+path,{waitUntil:'load'});await p.waitForTimeout(1200);
   r=await p.evaluate(()=>({status:0,ov:document.documentElement.scrollWidth-innerWidth,title:document.title,
    zoom:[...document.querySelectorAll('input:not([type=hidden]):not([type=checkbox]):not([type=radio]),select,textarea')].filter(e=>e.getBoundingClientRect().width&&parseFloat(getComputedStyle(e).fontSize)<16).length}));r.status=resp.status();}
  catch(e){r={fatal:e.message.slice(0,100)};}
  const prob=[];if(r.fatal)prob.push(r.fatal);else{if(r.status>=400)prob.push('HTTP '+r.status);if(r.ov>2)prob.push('horizontal overflow +'+r.ov+'px');if(r.zoom)prob.push(r.zoom+' inputs <16px (iOS zoom on focus)');}
  if(errs.length)prob.push(...new Set(errs.slice(0,4)));
  report.push({device:name,page:path,ok:!prob.length,prob});
  await p.screenshot({path:`${out}/${name}${path.replace(/\W+/g,'_')}.png`}).catch(()=>{});}
 await b.close();}
await writeFile(out+'/results.json',JSON.stringify(report,null,1));
const bad=report.filter(r=>!r.ok);console.log(`checked ${report.length}, problems ${bad.length}`);for(const r of bad)console.log(`[${r.device}] ${r.page}: ${r.prob.join(' | ')}`);
