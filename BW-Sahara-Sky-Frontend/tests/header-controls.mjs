import {chromium} from 'playwright';
import assert from 'node:assert/strict';
const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
const context=await browser.newContext({serviceWorkers:'block'}),page=await context.newPage();
const errors=[];page.on('pageerror',error=>errors.push(error.message));
const checks=[];
try{
 for(const path of ['/','/contact-us/','/rooms/','/ar/ghoraf/']){
  await page.goto('http://127.0.0.1:8782'+path,{waitUntil:'load'});
  for(const width of [320,390,1440]){
   await page.setViewportSize({width,height:844});
   const account=page.locator('.account-toggle'),panel=page.locator('.account-panel'),menu=page.locator('.menu-toggle');
   assert(await account.isVisible());await account.click();assert(await panel.isVisible());
   assert.equal(await panel.locator('a').count(),2);
   assert(await panel.locator('.nav-signin').getAttribute('href'));
   await page.keyboard.press('Escape');assert(!(await panel.isVisible()));
   if(width<900){
    assert(await menu.locator('span').evaluateAll(lines=>lines.every(line=>line.getBoundingClientRect().height>=2)),'Burger lines must remain visible');
    await menu.click();assert.equal(await menu.getAttribute('aria-expanded'),'true');
    assert(await page.locator('#nav-links').isVisible());
    await account.click();assert.equal(await menu.getAttribute('aria-expanded'),'false');
    assert(await panel.isVisible());await page.locator('.lang-menu .lang-btn').click();assert(!(await panel.isVisible()));
    await page.keyboard.press('Escape');
   }
   assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+2),'Header overflow');
   assert.equal(await page.evaluate(()=>getComputedStyle(document.documentElement).scrollbarWidth),'none');
   checks.push(`${path} at ${width}px: account links, dismiss, menu and page width`);
  }
 }
 assert.deepEqual(errors,[]);console.log(JSON.stringify({passed:true,checks}));
}finally{await browser.close();}
