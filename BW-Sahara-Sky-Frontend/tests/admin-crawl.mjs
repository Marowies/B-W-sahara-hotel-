// Crawls every admin page linked from the sidebar on desktop, Android and iPhone (local backend on 8782).
import {chromium,webkit,devices} from 'playwright';import {readFile,mkdir,writeFile} from 'node:fs/promises';
const admin=JSON.parse(await readFile('../BW-Sahara-Sky-Hotel/tmp/local-integration-20261007/private-admin-access.json','utf8'));
const B='http://127.0.0.1:8782',out='test-results/admin-crawl';await mkdir(out,{recursive:true});
const targets=[['desktop',chromium,{viewport:{width:1440,height:900}}],['android',chromium,devices['Pixel 7']],['iphone',webkit,devices['iPhone 15']]];
const login=async p=>{await p.goto(B+'/admin/login');await p.fill('[name=username]',admin.username);await p.fill('[name=password]',admin.password);await Promise.all([p.waitForURL(u=>!u.pathname.includes('/login')),p.click('button[type=submit]')]);};
let links=[];const report=[];
for(const [name,engine,dev] of targets){const b=await engine.launch();const ctx=await b.newContext({...dev,serviceWorkers:'block'});const p=await ctx.newPage();p.setDefaultTimeout(45000);
 const errs=[];p.on('pageerror',e=>errs.push('JS: '+e.message.slice(0,140)));p.on('response',r=>{const u=r.url();if(r.status()>=400&&!u.includes('/themes/')&&!u.includes('license'))errs.push(r.status()+' '+u.replace(B,'').slice(0,90));});
 await login(p);
 if(!links.length){links=await p.evaluate(()=>[...new Set([...document.querySelectorAll('#sidebar-menu a[href]')].map(a=>a.href).filter(h=>h.includes('/admin')&&!h.endsWith('#')&&!h.includes('logout')))]);links.unshift(B+'/admin');links=[...new Set(links)];}
 for(const url of links){errs.length=0;const path=url.replace(B,'');let r;
  try{const resp=await p.goto(url,{waitUntil:'load'});await p.waitForLoadState('networkidle',{timeout:8000}).catch(()=>{});await p.waitForTimeout(500);
   r=await p.evaluate(()=>({ov:document.documentElement.scrollWidth-innerWidth,title:document.querySelector('.page-title,h1,h2')?.textContent.trim().slice(0,40),
    broken:[...document.images].filter(i=>i.complete&&i.naturalWidth===0&&i.offsetWidth).map(i=>i.src.split('/').slice(-2).join('/')).slice(0,3),
    zoom:innerWidth<800?[...document.querySelectorAll('input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=file]),select,textarea')].filter(e=>e.offsetWidth&&parseFloat(getComputedStyle(e).fontSize)<16).map(e=>e.name||e.id||e.className.slice(0,20)).slice(0,3):[],
    clipped:[...document.querySelectorAll('.btn,.nav-link,.badge,.form-label,th')].filter(e=>e.offsetWidth&&e.scrollWidth>e.clientWidth+2&&getComputedStyle(e).overflow!=='visible').map(e=>e.textContent.trim().slice(0,20)).slice(0,3)}));r.status=resp.status();}
  catch(e){r={fatal:e.message.slice(0,100)};}
  const prob=[];if(r.fatal)prob.push(r.fatal);else{if(r.status>=400)prob.push('HTTP '+r.status);if(r.ov>2)prob.push('overflow +'+r.ov);if(r.broken.length)prob.push('broken img '+r.broken.join(','));if(r.zoom.length)prob.push('inputs<16px '+r.zoom.join(','));if(r.clipped.length)prob.push('clipped text: '+r.clipped.join(' | '));}
  prob.push(...new Set(errs.slice(0,3)));
  report.push({device:name,path,title:r.title,prob});
  await p.screenshot({path:`${out}/${name}${path.replace(/\W+/g,'_')}.png`,fullPage:name==='desktop'}).catch(()=>{});}
 await b.close();}
await writeFile(out+'/results.json',JSON.stringify(report,null,1));
const bad=report.filter(r=>r.prob.length);console.log(`pages ${links.length}, checks ${report.length}, with problems ${bad.length}`);for(const r of bad)console.log(`[${r.device}] ${r.path}: ${r.prob.join(' | ')}`);
