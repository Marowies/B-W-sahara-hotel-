import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';

// Execute the production destination resolver without a browser or authentication mocks.
const source=readFileSync(new URL('../src/js/approval.js',import.meta.url),'utf8');
const resolver=source.slice(source.indexOf(' function signInDestination('),source.indexOf('\n const page ='));
const context={URL,location:{origin:'https://hotel.example'}};
vm.createContext(context);vm.runInContext(resolver,context);
const choose=context.signInDestination;
for(const lang of ['en','ar','zh']) {
 const paths={en:['/booking/','/booking/review/'],ar:['/ar/takhteet-eqama/','/ar/takhteet-eqama/moragaa/'],zh:['/zh/zhusu-jihua/','/zh/zhusu-jihua/queren/']}[lang];
 assert.equal(choose(null,lang,paths),`/customer/overview?lang=${lang}`);
 assert.equal(choose('/customer/bookings?lang=en',lang,paths),`/customer/bookings?lang=${lang}`);
 for(const path of paths)assert.equal(choose(path,lang,paths),path);
 for(const path of ['https://evil.example/','//evil.example/customer/overview','/admin','/customer/../../admin','/customer/overview?next=https://evil.example/']) {
  assert.equal(choose(path,lang,paths),`/customer/overview?lang=${lang}`);
 }
}

// Exercise card replacement with a filter selected before the API result arrives.
const start=source.indexOf('  function backendCards(');
const render=source.slice(start,source.indexOf('\n',start));
const rooms=[{slug:'deluxe',category:'deluxe'},{slug:'standard',category:'standard'}];
const cards=rooms.map(()=>({dataset:{},append(){},querySelectorAll(){return [];}}));
let applied=0;
const grid={innerHTML:'',querySelectorAll:()=>cards,closest:()=>({querySelector:()=>({click(){applied++;cards.forEach((card,i)=>card.hidden=rooms[i].category!=='deluxe');}})}),append(){}};
const renderContext={rooms,lang:'en',page:'rooms',esc:String,roomName:String,t:k=>k,roomCard:()=>'',document:{createElement:()=>({})},Intl,url:x=>x};
vm.createContext(renderContext);vm.runInContext(render,renderContext);
renderContext.backendCards(rooms.map((r,i)=>({...r,id:i+1,name:r.slug,price:100,number_of_beds:1})),grid);
assert.equal(applied,1);assert.equal(cards[0].hidden,false);assert.equal(cards[1].hidden,true);
console.log('PASS: default account, localized booking/account returns, unsafe destinations rejected, selected filter survives card hydration');
