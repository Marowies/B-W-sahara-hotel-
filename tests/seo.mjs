import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {createRequire} from 'node:module';
import {seoConfig,sitemap,robots} from '../tools/seo.mjs';
const require=createRequire(import.meta.url),{chromium}=require('playwright');
const base=process.env.HOTEL_TEST_URL||'http://127.0.0.1:8780';
assert.throws(()=>seoConfig({HOTEL_INDEXABLE:'1'}));
assert.throws(()=>seoConfig({HOTEL_SITE_ORIGIN:'http://example.invalid'}));
assert.equal(robots(seoConfig({})),'User-agent: *\nDisallow: /\n');
assert(!sitemap(seoConfig({}),['/index.html']).includes('<url>'));
const production=seoConfig({HOTEL_SITE_ORIGIN:'https://hotel.example.invalid',HOTEL_INDEXABLE:'1'});
const map=sitemap(production,['/index.html','/rooms/index.html','/booking/index.html','/login/index.html','/404.html']);
assert.equal((map.match(/<url>/g)||[]).length,6);assert(!map.includes('/booking/'));assert(map.includes('zh-Hans'));
const response=await fetch(base+'/rooms?lang=ar',{redirect:'manual'});assert.equal(response.status,301);assert.equal(response.headers.get('location'),'/rooms/?lang=ar');
assert.equal((await fetch(base+'/not-a-route/')).status,404);
assert.equal((await fetch(base+'/404.html')).status,404);
assert.equal((await fetch(base+'/localized/en/index.html')).status,404);
assert((await fetch(base+'/sitemap.xml')).headers.get('content-type').includes('xml'));
const manifest=JSON.parse(await readFile(new URL('../dist/build-manifest.json',import.meta.url)));
const browser=await chromium.launch({headless:true,...(process.env.HOTEL_BROWSER_PATH?{executablePath:process.env.HOTEL_BROWSER_PATH}:{}),args:['--use-angle=swiftshader','--enable-unsafe-swiftshader']});
let assertions=12;
try {
 const page=await browser.newPage({serviceWorkers:'block'});
 for(const lang of ['en','ar','zh'])for(const route of ['/','/rooms/','/contact-us/','/booking/']){
  const raw=await (await fetch(base+route+'?lang='+lang)).text();
  assert(raw.includes('<h1'),`Missing rendered h1: ${route}/${lang}`);
  assert(raw.includes(`lang="${lang==='zh'?'zh-Hans':lang}"`));
  assert(raw.includes('property="og:title"'));assertions+=3;
  await page.goto(base+route+'?lang='+lang,{waitUntil:'load'});
  const head=await page.evaluate(()=>({canonical:document.querySelector('link[rel="canonical"]')?.getAttribute('href'),description:document.querySelector('meta[name="description"]').content,robots:document.querySelector('meta[name="robots"]').content,hreflang:[...document.querySelectorAll('link[hreflang]')].map(x=>x.hreflang),h1:document.querySelector('main h1')?.textContent,config:window.__HOTEL_SEO__}));
  assert(head.h1);assert(head.description.length>30);
  assert.equal(head.robots,head.config.indexable&&route!=='/booking/'?'index,follow':'noindex,nofollow');
  if(head.config.origin){assert.equal(head.canonical,head.config.origin+route+'?lang='+lang);assert.equal(head.hreflang.length,route==='/booking/'?0:4);}
  assertions+=5;
 }
 await page.goto(base+'/?lang=ar',{waitUntil:'load'});
 await page.locator('header.nav a[href="/rooms/?lang=ar"]').click();await page.waitForFunction(()=>document.body.dataset.page==='rooms');
 assert((await page.locator('meta[name="description"]').getAttribute('content')).startsWith('الغرف.'));assertions++;
 for(const file of manifest.routes){const lang='zh';const raw=await (await fetch(base+file.replace(/index\.html$/,'')+'?lang='+lang)).text();assert(raw.includes('name="description"'));assertions++;}
 console.log(JSON.stringify({seoAssertions:assertions,status:'PASS'}));
} finally {await browser.close();}
