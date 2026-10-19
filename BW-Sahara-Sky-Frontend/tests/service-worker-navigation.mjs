import assert from 'node:assert/strict';
import vm from 'node:vm';
import {readFileSync} from 'node:fs';

// Evaluate the actual generated worker template; no browser or network is involved.
const build=readFileSync(new URL('../tools/build.mjs',import.meta.url),'utf8');
const start=build.indexOf('const sw=`'),end=build.indexOf('\nawait writeFile',start);
const template=build.slice(start,end)+'\n globalThis.workerSource=sw;';
const factory={version:'test',shell:[]};vm.createContext(factory);vm.runInContext(template,factory);
const handlers={},fetches=[];
const scope={self:{location:{origin:'https://hotel.test'},addEventListener:(type,fn)=>handlers[type]=fn},URL,fetch:(request,options)=>{fetches.push({request,options});return Promise.resolve('network');},caches:{open:()=>{throw Error('Document navigation entered an asset cache');}}};
vm.createContext(scope);vm.runInContext(factory.workerSource,scope);
for(const path of ['/','/booking/','/booking/review/','/booking/0123456789abcdef0123456789abcdef','/customer/overview','/admin','/ar/takhteet-eqama/']) {
 let response;handlers.fetch({request:{url:'https://hotel.test'+path,method:'GET',mode:'navigate',headers:{has:()=>false}},respondWith:value=>response=value});
 assert.equal(await response,'network');assert.equal(fetches.at(-1).options.cache,'no-store');
}
// Laravel 'back' redirects (currency switch) read the referrer; it must stay the page, never /sw.js.
for(const [referrer,expected] of [['https://hotel.test/customer/overview?lang=ar','https://hotel.test/customer/overview?lang=ar'],['https://elsewhere.test/page',''],['','']]){
 let response;handlers.fetch({request:{url:'https://hotel.test/currency/switch/EUR',method:'GET',mode:'navigate',referrer,referrerPolicy:'strict-origin-when-cross-origin',headers:{has:()=>false}},respondWith:value=>response=value});
 await response;assert.equal(fetches.at(-1).options.referrer,expected);assert.equal(fetches.at(-1).options.cache,'no-store');
}
const before=fetches.length;
handlers.fetch({request:{url:'https://hotel.test/api/hotel/session',method:'GET',mode:'cors',headers:{has:()=>false}},respondWith:()=>{throw Error('Private API was intercepted');}});
assert.equal(fetches.length,before);
console.log('PASS: public, booking and account documents use network-only and keep the page referrer; private API remains untouched');
