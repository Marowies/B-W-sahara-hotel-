// Mobile audit: Android (Chromium) + iOS (WebKit) across every route, EN/AR, plus admin previews.
import {chromium,webkit,devices} from 'playwright';import {writeFile,mkdir} from 'node:fs/promises';
const SITE=process.env.SITE||'http://127.0.0.1:8780',ADMIN=process.env.ADMIN||'http://127.0.0.1:8790';
const routes='/ /about-us/ /blog/ /blog/a-desert-made-for-discovery/ /blog/a-night-beneath-the-stars/ /blog/the-art-of-a-slower-stay/ /booking/ /booking/confirmation/ /booking/review/ /contact-us/ /experiences/ /faq/ /forgot-password/ /galleries/ /guides/planning-a-desert-stay/ /login/ /privacy/ /register/ /rooms/ /rooms/bw-sahara-sky-hotel-deluxe-room-double/ /rooms/bw-sahara-sky-hotel-deluxe-room-single/ /rooms/bw-sahara-sky-hotel-standard-room-double/ /rooms/bw-sahara-sky-hotel-standard-room-triple/ /rooms/bw-sahara-sky-hotel-superior-room-double/ /rooms/bw-sahara-sky-hotel-superior-room-single/ /rooms/copper-glass-domes/ /rooms/standard-room-single/ /services/ /term-and-conditions/'.split(' ');
const adminPages=['/preview/login.html','/preview/dashboard.html','/preview/dashboard.html?theme=dark','/preview/dashboard.html?dir=rtl','/preview/login.html?dir=rtl'];
const targets=[
 {name:'android-pixel7',engine:chromium,dev:devices['Pixel 7']},
 {name:'android-small-360',engine:chromium,dev:{...devices['Galaxy S9+'],viewport:{width:360,height:740}}},
 {name:'ios-iphone15',engine:webkit,dev:devices['iPhone 15']},
 {name:'ios-iphoneSE',engine:webkit,dev:devices['iPhone SE']},
];
const out='test-results/mobile-audit';await mkdir(out,{recursive:true});
const issues=[];let count=0;
const probe=()=>{const vw=innerWidth,o=[];
 for(const el of document.querySelectorAll('body *')){const cs=getComputedStyle(el);if(cs.display==='none'||cs.visibility==='hidden'||cs.position==='fixed')continue;const r=el.getBoundingClientRect();if(r.width&&r.right>vw+2&&!el.closest('[style*="overflow"],.table-responsive,[class*="scroll"],[class*="carousel"],[class*="slider"],[class*="track"]')){o.push((el.tagName+'.'+[...el.classList].join('.')).slice(0,70)+' r='+Math.round(r.right));if(o.length>4)break;}}
 const small=[];for(const el of document.querySelectorAll('a[href],button,input:not([type=hidden]),select,[role=button]')){const r=el.getBoundingClientRect(),cs=getComputedStyle(el);if(!r.width||cs.visibility==='hidden'||r.bottom<0)continue;if((r.width<24||r.height<24)&&!el.closest('p,li p,.prose'))small.push(((el.textContent||el.getAttribute('aria-label')||el.name||'').trim().slice(0,25)||el.tagName)+` ${Math.round(r.width)}x${Math.round(r.height)}`);}
 const inputs=[...document.querySelectorAll('input:not([type=hidden]):not([type=checkbox]):not([type=radio]),select,textarea')].filter(e=>e.getBoundingClientRect().width&&parseFloat(getComputedStyle(e).fontSize)<16).map(e=>(e.name||e.id||e.type)+':'+getComputedStyle(e).fontSize);
 const brokenImg=[...document.images].filter(i=>i.complete&&i.naturalWidth===0&&i.currentSrc).map(i=>i.currentSrc.split('/').pop());
 return {docOverflow:document.documentElement.scrollWidth-vw,overflow:o,small:small.slice(0,6),smallCount:small.length,inputs:inputs.slice(0,5),brokenImg};};
for(const t of targets){const b=await t.engine.launch();const ctx=await b.newContext({...t.dev,serviceWorkers:'block'});const p=await ctx.newPage();
 const errs=[];p.on('pageerror',e=>errs.push(e.message.slice(0,140)));p.on('response',r=>{if(r.status()>=400&&!r.url().includes('favicon'))errs.push(r.status()+' '+r.url().replace(/^https?:\/\/[^/]+/,''));});
 const list=[...routes.flatMap(r=>[[SITE,r+'?lang=en'],[SITE,r+'?lang=ar']]),...adminPages.map(r=>[ADMIN,r])];
 for(const [base,path] of list){errs.length=0;let res;
  try{await p.goto(base+path,{waitUntil:'load',timeout:45000});await p.waitForTimeout(600);
   await p.evaluate(async()=>{for(let y=0;y<document.body.scrollHeight;y+=innerHeight){scrollTo(0,y);await new Promise(r=>setTimeout(r,60));}scrollTo(0,0);});await p.waitForTimeout(300);res=await p.evaluate(probe);}
  catch(e){res={fatal:e.message.slice(0,150)};}
  const key=(base===ADMIN?'admin':'site')+path;const prob=[];
  if(res.fatal)prob.push('LOAD FAIL '+res.fatal);
  else{if(res.docOverflow>2)prob.push(`horizontal scroll +${res.docOverflow}px: ${res.overflow.join(', ')}`);
   if(res.inputs.length)prob.push('inputs <16px (iOS zoom): '+res.inputs.join(', '));
   if(res.brokenImg.length)prob.push('broken img: '+res.brokenImg.join(','));
   if(res.smallCount)prob.push(`${res.smallCount} tap targets <24px: ${res.small.join(' | ')}`);}
  if(errs.length)prob.push('errors: '+[...new Set(errs)].slice(0,4).join(' | '));
  if(prob.length)issues.push({device:t.name,page:key,prob});
  if(base===ADMIN||['/?','/rooms/?','/booking/?','/contact-us/?'].some(s=>path.startsWith(s)&&path.includes('lang=')))await p.screenshot({path:`${out}/${t.name}${key.replace(/[^a-z0-9]+/gi,'_')}.png`,fullPage:base===ADMIN}).catch(()=>{});
  count++;}
 // Admin interactions: mobile menu toggle
 try{await p.goto(ADMIN+'/preview/dashboard.html');const tg=p.locator('.navbar-toggler:visible').first();const bx=await tg.boundingBox();await tg.click();await p.waitForTimeout(400);const shown=await p.evaluate(()=>{const m=document.getElementById('sidebar-menu');const r=m.getBoundingClientRect();return m.classList.contains('show')&&r.height>50;});
  if(!shown)issues.push({device:t.name,page:'admin/dashboard menu',prob:['mobile menu did not open']});if(bx&&bx.height<44)issues.push({device:t.name,page:'admin/dashboard menu',prob:[`menu toggle only ${Math.round(bx.width)}x${Math.round(bx.height)}px (<44)`]});
  await p.screenshot({path:`${out}/${t.name}_admin_menu_open.png`});}catch(e){issues.push({device:t.name,page:'admin menu',prob:['menu test failed: '+e.message.slice(0,120)]});}
 // Site interaction: mobile nav on home
 try{await p.goto(SITE+'/?lang=en');await p.waitForTimeout(500);const btn=p.locator('button[aria-controls]:visible, .menu-toggle:visible, .nav-toggle:visible, button[aria-label*="enu" i]:visible').first();if(await btn.count()){await btn.click();await p.waitForTimeout(500);await p.screenshot({path:`${out}/${t.name}_site_menu_open.png`});}else issues.push({device:t.name,page:'site home',prob:['no visible mobile menu button found']});}catch(e){issues.push({device:t.name,page:'site menu',prob:['menu test failed: '+e.message.slice(0,120)]});}
 await b.close();}
await writeFile(`${out}/results.json`,JSON.stringify({checked:count,issues},null,1));
console.log('checked',count,'page loads; issues',issues.length);for(const i of issues)console.log(`[${i.device}] ${i.page}\n   - `+i.prob.join('\n   - '));
