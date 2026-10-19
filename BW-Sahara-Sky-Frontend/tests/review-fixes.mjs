import {createRequire} from 'node:module';
import assert from 'node:assert/strict';
import {paceDocuments} from './support/pace-documents.mjs';
const require=createRequire(import.meta.url),{chromium}=require('playwright');
const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',args:['--use-angle=swiftshader','--enable-unsafe-swiftshader']});
const page=await browser.newPage({serviceWorkers:'block'}),errors=[];page.on('pageerror',e=>errors.push(e.message));await paceDocuments(page.context());
try {
 for(const lang of ['en','ar','zh'])for(const width of [1280,1024,390]){
  await page.setViewportSize({width,height:900});await page.goto(`http://127.0.0.1:8780/contact-us/?lang=${lang}`,{waitUntil:'networkidle'});
  // The contact page also has the stay form's themed selects; this checks the enquiry subject.
  const button=page.locator('#contact-form .enquiry-select-button');await button.click();await page.keyboard.press('End');await page.keyboard.press('Enter');
  assert.equal(await page.locator('select[name=subject]').evaluate(s=>s.selectedIndex),3);assert.equal(await button.getAttribute('aria-expanded'),'false');
  await button.click();await page.keyboard.press('Escape');assert.equal(await button.getAttribute('aria-expanded'),'false');
  const layout=await page.evaluate(()=>{const story=document.querySelector('.footer-story').getBoundingClientRect(),brand=document.querySelector('.footer-story .brand').getBoundingClientRect(),discover=document.querySelector('.footer-links').getBoundingClientRect();return {overflow:document.documentElement.scrollWidth>innerWidth+1,brandFits:brand.right<=story.right+1&&brand.left>=story.left-1,overlap:brand.left<discover.right&&brand.right>discover.left&&brand.top<discover.bottom&&brand.bottom>discover.top};});
  assert(!layout.overflow,`${lang}/${width}: horizontal overflow`);assert(layout.brandFits,`${lang}/${width}: brand overflow`);assert(!layout.overlap,`${lang}/${width}: footer overlap`);
  if(lang==='en'&&width===1280){await button.click();await page.locator('#contact-form').screenshot({path:'test-results/fixed-contact.png'});await page.keyboard.press('Escape');await page.locator('footer').screenshot({path:'test-results/fixed-footer.png'});}
 }
 await page.setViewportSize({width:1280,height:900});await page.goto('http://127.0.0.1:8780/?lang=en',{waitUntil:'networkidle'});
 assert.equal(await page.locator('.intro-seal img').count(),1);// The seal is lazy-loaded below the hero; bring it into view before checking it.
 await page.locator('.intro-seal img').scrollIntoViewIfNeeded();await page.waitForFunction(()=>{const i=document.querySelector('.intro-seal img');return i.complete&&i.naturalWidth>0;});await page.locator('#intro').screenshot({path:'test-results/fixed-intro.png'});
 await page.locator('#model-stage').scrollIntoViewIfNeeded();await page.waitForFunction(()=>document.querySelector('#model-stage canvas'));await page.waitForTimeout(1500);await page.locator('#model-stage').screenshot({path:'test-results/fixed-model.png'});
 assert.deepEqual(errors,[]);console.log(JSON.stringify({passed:true,layouts:9,themedSelectKeyboard:true,logo:true,modelLoaded:true,pageErrors:errors}));
} finally {await browser.close();}
