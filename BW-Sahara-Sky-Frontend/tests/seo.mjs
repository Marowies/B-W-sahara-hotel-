import assert from 'node:assert/strict';
import {readFile,writeFile,mkdir} from 'node:fs/promises';
import {createRequire} from 'node:module';
import {seoConfig,sitemap,robots,llms} from '../tools/seo.mjs';
import {routeDefinitions,languages,resolveRoute,localizedPath} from '../src/shared/locale.mjs';
import {localeRedirect} from '../src/shared/locale-resolver.mjs';
import {pageMetadata,pageContent} from '../src/shared/metadata.mjs';
const require=createRequire(import.meta.url),{chromium}=require('playwright');
const base=process.env.HOTEL_TEST_URL||'http://127.0.0.1:8782';
let checks=0;const check=(condition,message)=>{assert(condition,message);checks++;};
const config=seoConfig({}),production=seoConfig({HOTEL_INDEXABLE:'1'});
assert.throws(()=>seoConfig({HOTEL_SITE_ORIGIN:'http://example.invalid'}));checks++;
const leaves=(object,prefix='')=>Object.entries(object).flatMap(([key,value])=>typeof value==='object'&&value!==null?leaves(value,prefix+key+'.'):[prefix+key]);
for(const lang of languages){assert.deepEqual(leaves(config.messages[lang]).sort(),leaves(config.messages.en).sort());checks++;}
check(robots(config)==='User-agent: *\nDisallow: /\n','Preview must not be indexed');
const map=sitemap(production),publicCount=routeDefinitions.filter(r=>r[4]!==false).length*3;
check((map.match(/<url>/g)||[]).length===publicCount,'Sitemap coverage');
check(!map.includes('?lang=')&&!map.includes('copper-glass-domes'),'Only canonical, verified pages');
check(llms(production).startsWith('# B&W Sahara Sky Hotel\n\n> '),'llms format');
check(llms(production).includes('/ar/dalil/takhteet-eqama-sahraweya/index.md'),'Localized markdown links');
for(const row of routeDefinitions)for(const [i,lang] of languages.entries()){
 const route=resolveRoute(row[i+1]);check(route?.id===row[0]&&route.locale===lang,'Unique route mapping');
 check(localizedPath(row[1],lang)===route.path,'Pathname helper');
 const meta=pageMetadata(production,route,x=>production.origin+'/assets/'+x);
 check(meta.canonical===production.origin+route.path,'Self canonical');
 check(meta.alternates.length===(route.indexable?4:0),'Hreflang excludes drafts');
 check(meta.robots===(route.indexable?'index,follow':'noindex,nofollow'),'Draft indexing');
 const raw=await readFile(new URL('../dist'+route.path+(route.path.endsWith('.html')?'':'index.html'),import.meta.url),'utf8');
 check(raw.includes(`lang="${lang==='zh'?'zh-Hans':lang}"`),'Raw language');
 check(raw.includes(`dir="${lang==='ar'?'rtl':'ltr'}"`),'Raw direction');
 check(raw.includes('<h1')&&raw.includes(meta.canonical),'Prerendered heading and canonical');
 const json=raw.match(/<script[^>]+type="application\/ld\+json"[^>]*>([\s\S]*?)<\/script>/)?.[1];
 check(Boolean(json)===route.indexable,'Raw JSON-LD only on public pages');
 const graph=json?JSON.parse(json)['@graph']:[];
 check(!graph.some(x=>['SoftwareApplication','Review','AggregateRating','Offer'].includes(x['@type'])),'No invented schema');
 const faq=graph.find(x=>x['@type']==='FAQPage'),content=pageContent(config,route);
 if(faq){check(faq.mainEntity.length>=3&&faq.mainEntity.length<=7,'FAQ size');
  for(const f of faq.mainEntity){check(raw.includes(f.name.replaceAll('&','&amp;')),'Visible question');check(raw.includes(f.acceptedAnswer.text.replaceAll('&','&amp;')),'Visible answer');}
 }
 if(content)check(raw.includes(`datetime="${config.entity.reviewed}"`),'Honest visible date');
}
const redirect=(path,headers={},trusted=true)=>localeRedirect(new URL('https://hotel.test'+path),new Headers(headers),{trustedCountryHeader:trusted});
for(const ua of ['Googlebot','Bingbot','GPTBot','OAI-SearchBot','ChatGPT-User','ClaudeBot','PerplexityBot','Applebot','facebookexternalhit'])check(redirect('/',{'user-agent':ua,'x-vercel-ip-country':'EG'})===null,'Crawler stays on requested URL');
check(redirect('/',{'x-vercel-ip-country':'EG'})?.location==='/ar/','Trusted Egyptian visitor');
check(redirect('/',{'x-vercel-ip-country':'CN'})?.location==='/zh/','Trusted Chinese visitor');
check(redirect('/',{'x-vercel-ip-country':'EG',cookie:'hotel_locale=en'})===null,'Explicit English overrides country');
check(redirect('/rooms/',{'x-vercel-ip-country':'CN',cookie:'hotel_locale=ar'})?.location==='/ar/ghoraf/','Cookie preference overrides country');
check(redirect('/',{'x-vercel-ip-country':'EG'},false)===null,'Untrusted country ignored');
for(const path of ['/admin/','/api/hotel/rooms','/webhooks/','/assets/x.webp','/ar/ghoraf/','/booking/'])check(redirect(path,{'x-vercel-ip-country':'EG'})===null,'Excluded redirect path');
check(redirect('/rooms/?lang=ar')?.status===301&&redirect('/rooms/?lang=ar')?.location==='/ar/ghoraf/','Legacy migration');
const response=await fetch(base+'/rooms/?lang=ar',{redirect:'manual'});check(response.status===301&&response.headers.get('location')==='/ar/ghoraf/','Live legacy redirect');
check((await fetch(base+'/',{headers:{'x-vercel-ip-country':'EG'},redirect:'manual'})).status===200,'Local spoof protection');
for(const path of ['/llms.txt','/robots.txt','/sitemap.xml','/ar/ghoraf/index.md'])check((await fetch(base+path)).status===200,'Live crawl file');
check((await fetch(base+'/localized/en/index.html')).status===404,'Legacy tree removed');
check((await fetch(base+'/not-a-route/')).status===404,'404 status');
const browser=await chromium.launch({headless:true,executablePath:process.env.HOTEL_BROWSER_PATH||'C:/Program Files/Google/Chrome/Application/chrome.exe',args:['--use-angle=swiftshader','--enable-unsafe-swiftshader']});
const screenshots=[];
try{
 const page=await browser.newPage({viewport:{width:390,height:844},serviceWorkers:'block'}),errors=[];
 page.on('pageerror',error=>errors.push(error.message));
 await page.route('**/*',route=>new URL(route.request().url()).origin===base?route.continue():route.abort());
 for(const lang of languages){
  await page.goto(base+localizedPath('guide',lang),{waitUntil:'load'});
  check((await page.locator('main h1').count())===1,'Guide has one heading');
  check(await page.locator('.page-hero-copy').evaluate(n=>getComputedStyle(n).zIndex==='1'),'Guide heading above image overlay');
  check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Guide mobile overflow');
  await page.goto(base+localizedPath('rooms',lang),{waitUntil:'load'});
  if(lang!=='en'&&await page.locator('meta[name="hotel-backend"]').count()){
   await page.waitForFunction(()=>document.querySelectorAll('[data-backend-room-id]').length>0);
   const titles=await page.locator('[data-backend-room-id] h3').allTextContents();
   check(titles.length===7&&titles.every(text=>(lang==='ar'?/\p{Script=Arabic}/u:/\p{Script=Han}/u).test(text)),'Backend room titles preserve URL language');
  }
  check((await page.locator('[data-hotel-answer]').count())===1,'No duplicate answer during hydration');
  check(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Rooms mobile overflow');
  const contact=localizedPath('contact',lang);
  await page.locator(`footer a[href="${contact}"]`).first().click();
  await page.waitForFunction(()=>document.body.dataset.page==='contact');
  check(await page.locator('link[rel="canonical"]').getAttribute('href')===config.origin+contact,'Client metadata updated');
  check((await page.locator('[data-hotel-answer]').count())===1,'Client GEO block');
  await mkdir(new URL('../test-results/seo/',import.meta.url),{recursive:true});
  const path=new URL(`../test-results/seo/contact-${lang}.png`,import.meta.url).pathname;await page.screenshot({path:decodeURIComponent(path.replace(/^\/(\w:)/,'$1')),fullPage:true});screenshots.push(lang);
 }
 check(errors.length===0,'Browser errors: '+errors.join('; '));
}finally{await browser.close();}
const result={date:'2026-10-07',status:'PASS',checks,rawPages:93,publicSitemapUrls:publicCount,localCountryRouting:'untrusted-header-ignored',screenshots};
await mkdir(new URL('../test-results/seo/',import.meta.url),{recursive:true});await writeFile(new URL('../test-results/seo/results.json',import.meta.url),JSON.stringify(result,null,2));console.log(JSON.stringify(result));
