import {chromium} from 'playwright';
import {readFile,mkdir,writeFile} from 'node:fs/promises';
 const out=process.env.PALETTE_SETTINGS_REVIEW?'test-results/palette-settings':process.env.PALETTE_REVIEW_PATHS?'test-results/palette-recheck':'test-results/palette-admin';await mkdir(out,{recursive:true});
const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
const page=await browser.newPage({viewport:{width:390,height:844},serviceWorkers:'block'});page.setDefaultTimeout(60000);
const results=[];
try{
 const admin=JSON.parse(await readFile('../BW-Sahara-Sky-Hotel/tmp/local-integration-20261007/private-admin-access.json','utf8'));
 await page.goto('http://127.0.0.1:8782/admin/login');await page.locator('[name=username]').fill(admin.username);await page.locator('[name=password]').fill(admin.password);await page.locator('button[type=submit]').click();await page.waitForURL(url=>!url.pathname.includes('/login'));
 const links=await page.locator('.navbar-vertical a[href]').evaluateAll(anchors=>anchors.map(a=>({name:a.textContent.trim().replace(/\s+/g,' '),url:a.href})).filter(a=>new URL(a.url).origin===location.origin&&new URL(a.url).pathname.startsWith('/admin')&&!new URL(a.url).hash&&!/logout|clear|delete|remove|install|activate|publish|backup|maintenance/i.test(a.url)));
 const paths=process.env.PALETTE_REVIEW_PATHS?.split(',');
 const unique=[...new Map(links.filter(a=>!a.url.endsWith('#')&&(!paths||paths.includes(new URL(a.url).pathname))&&(!process.env.PALETTE_SETTINGS_REVIEW||new URL(a.url).pathname==='/admin/settings')).map(a=>[a.url,a])).values()];await writeFile(`${out}/inventory.json`,JSON.stringify(unique,null,2));
 for(const link of unique){
  const response=await page.goto(link.url,{waitUntil:'load',timeout:60000});
  if(process.env.PALETTE_SETTINGS_REVIEW&&new URL(link.url).pathname==='/admin/settings'){
   const children=await page.locator('a[href]').evaluateAll(anchors=>anchors.map(a=>({name:a.textContent.trim().replace(/\s+/g,' '),url:a.href})).filter(a=>new URL(a.url).origin===location.origin&&new URL(a.url).pathname.startsWith('/admin/settings/')&&!/test|send|clear|reset|delete|remove/i.test(a.url)));
   unique.push(...[...new Map(children.map(a=>[a.url,a])).values()]);
  }
  if(await page.locator('table.dataTable').count())await page.waitForFunction(()=>[...document.querySelectorAll('.dataTables_processing')].every(e=>getComputedStyle(e).display==='none'),{timeout:60000});
  const appearance=await page.evaluate(()=>({title:document.querySelector('.page-title,h1')?.textContent.trim(),primary:getComputedStyle(document.documentElement).getPropertyValue('--bb-primary').trim(),background:getComputedStyle(document.body).backgroundColor,card:document.querySelector('.card')?getComputedStyle(document.querySelector('.card')).backgroundColor:null,overflow:document.documentElement.scrollWidth>innerWidth+2}));
  const result={page:link.name,path:new URL(link.url).pathname,status:response.status(),...appearance};results.push(result);console.log(JSON.stringify(result));
  if(results.length<=3||/room-categories|menus$|settings\/email$|media$/.test(result.path))await page.screenshot({path:`${out}/${results.length}.png`,fullPage:false});
 }
 await writeFile(`${out}/results.json`,JSON.stringify({pages:results.length,results},null,2));
 if(results.some(r=>r.status!==200||r.primary!=='#60452f'))throw new Error('An admin page did not load with the reference palette; see results.json.');
}finally{await browser.close();}
