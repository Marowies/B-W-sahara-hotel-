import {chromium} from 'playwright';
import assert from 'node:assert/strict';
const browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
const context=await browser.newContext({viewport:{width:390,height:844},serviceWorkers:'block'}),page=await context.newPage();
let pending=false,requestEmail=null,verified=null;
await page.route('**/api/hotel/**',async route=>{
 const path=new URL(route.request().url()).pathname;let status=200,data={};
 if(path.endsWith('/session'))data={csrf_token:'synthetic-csrf'};
 else if(path.endsWith('/providers'))data={google:false};
 else if(path.endsWith('/google/pending'))data=pending?{pending:true,email:'google@example.invalid',resend_in:60}:{pending:false};
 else if(path.endsWith('/auth/refresh')){status=401;data={};}
 else if(path.endsWith('/otp/request')){requestEmail=route.request().postDataJSON().email;data={sent:true,resend_in:60};}
 else if(path.endsWith('/otp/verify')){verified=route.request().postDataJSON();data={authenticated:true,email:verified.email,access_token:'synthetic-token',expires_in:900};}
 await route.fulfill({status,contentType:'application/json',body:JSON.stringify(data)});
});
try{
 await page.goto('http://127.0.0.1:8782/login/');
 const google=page.getByRole('link',{name:'Continue with Google',exact:true});await google.waitFor();
 await page.getByRole('button',{name:'Sign in',exact:true}).waitFor();
 await google.click();await page.getByRole('status').getByText('Google sign-in is not configured yet.',{exact:false}).waitFor();
 assert(new URL(page.url()).pathname==='/login/','Disabled Google provider navigated to a dead endpoint');
 await page.getByLabel('Email address',{exact:true}).fill('email@example.invalid');await page.getByRole('button',{name:'Sign in',exact:true}).click();
 await page.locator('input[name=code]').waitFor();assert.equal(requestEmail,'email@example.invalid');
 pending=true;await page.reload();await page.locator('input[name=code]').waitFor();
 assert.equal(verified,null,'Google authenticated before the OTP was entered');
 await page.locator('input[name=code]').fill('123456');await page.getByRole('heading',{name:'You’re signed in',exact:true}).waitFor();
 assert.deepEqual(verified,{email:'google@example.invalid',code:'123456'});
 console.log('PASS: Sign in label; Google remains visible; missing provider explains setup; email and Google both require OTP verification. No real emails sent.');
}finally{await browser.close();}
