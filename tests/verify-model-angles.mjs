import {createRequire} from 'node:module';
import {fileURLToPath} from 'node:url';
import {mkdir,writeFile} from 'node:fs/promises';
import assert from 'node:assert/strict';
const require=createRequire(import.meta.url);
let playwright;try{playwright=require('playwright');}catch{playwright=require('C:/Users/marwa/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');}
const browser=await playwright.chromium.launch({executablePath:process.env.HOTEL_BROWSER_PATH||'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true,args:['--use-angle=swiftshader','--enable-unsafe-swiftshader']});
const context=await browser.newContext({serviceWorkers:'block',reducedMotion:'reduce',viewport:{width:1440,height:1000}});
const page=await context.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));
await mkdir(new URL('../test-results/',import.meta.url),{recursive:true});
await page.goto((process.env.HOTEL_TEST_URL||'http://127.0.0.1:8780')+'/?lang=en',{waitUntil:'networkidle'});
await page.locator('#model-stage').scrollIntoViewIfNeeded();await page.waitForFunction(()=>window.hotel3D?.getStats().modelBuilds===1);
await page.locator('[data-view="inside"]').click();await page.waitForTimeout(600);
const stage=page.locator('#model-stage');await stage.focus();
for(let i=0;i<10;i++)await page.keyboard.press('ArrowDown');
const views=[];
for(let angle=0;angle<4;angle++){
 if(angle)for(let i=0;i<10;i++)await page.keyboard.press('ArrowRight');
 await page.waitForTimeout(500);
 const filename=`model-angle-${angle}.png`;
 await stage.screenshot({path:fileURLToPath(new URL('../test-results/'+filename,import.meta.url))});
 views.push(filename);
}
await stage.focus();for(let i=0;i<10;i++)await page.keyboard.press('ArrowUp');await page.waitForTimeout(400);
await stage.screenshot({path:fileURLToPath(new URL('../test-results/model-flat-top.png',import.meta.url))});
await page.locator('[data-view="inside"]').click();await stage.focus();
for(let i=0;i<13;i++)await page.keyboard.press('ArrowDown');
await page.waitForTimeout(500);
await stage.screenshot({path:fileURLToPath(new URL('../test-results/model-bed-level.png',import.meta.url))});
assert.deepEqual(errors,[]);assert.equal(await page.evaluate(()=>window.hotel3D.getStats().modelBuilds),1);
await writeFile(new URL('../test-results/model-angles.json',import.meta.url),JSON.stringify({status:'PASS',views,errors,stats:await page.evaluate(()=>window.hotel3D.getStats())},null,2));
await browser.close();console.log(JSON.stringify({status:'PASS',angles:4,errors}));
