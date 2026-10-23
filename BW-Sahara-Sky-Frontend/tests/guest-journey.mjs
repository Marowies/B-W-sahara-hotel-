import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';

const source=readFileSync(new URL('../src/js/approval.js',import.meta.url),'utf8');
const context={};
vm.createContext(context);

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
// A preferred room is promoted without dropping alternatives or mutating API results.
const preferredStart=source.indexOf(' function preferredRoomFirst(');
const preferredSource=source.slice(preferredStart,source.indexOf('\n const page =',preferredStart));
vm.runInContext(preferredSource,context);
const available=[{slug:'double',id:101},{slug:'triple',id:205}];
assert.equal(context.preferredRoomFirst(available,'triple')[0].id,205);
assert.equal(available[0].id,101);
assert.equal(context.preferredRoomFirst(available,'unavailable').length,2);
assert.equal(context.preferredRoomFirst([], 'triple').length,0);

// Detail buttons without a room attribute still carry the current room, not an old draft.
const openStart=source.indexOf(' function openBooking(');
const openSource=source.slice(openStart,source.indexOf('\n document.addEventListener',openStart));
let saved,notice;
const bookingContext={rooms:[{id:3,slug:'triple',name:'Triple'},{id:4,slug:'double',name:'Double'}],page:'room',path:'/rooms/triple',Number,
 getStay:()=>({roomId:4,backendRoomId:999,arrival:'2026-11-20'}),saveStay:s=>saved=s,fillForm(){},t:s=>s,
 document:{querySelector:s=>s==='#stay-form'?{querySelector:()=>null,prepend:n=>notice=n}:{showModal(){}},createElement:()=>({})}};
vm.createContext(bookingContext);vm.runInContext(openSource,bookingContext);
bookingContext.openBooking();assert.equal(saved.roomId,3);assert.equal(saved.backendRoomId,null);assert.match(notice.textContent,/Triple/);
bookingContext.openBooking('4');assert.equal(saved.roomId,4);
bookingContext.page='rooms';bookingContext.openBooking();assert.equal(saved.roomId,null);assert.equal(saved.backendRoomId,null);
console.log('PASS: selected filters, room-detail handoff, stale backend IDs cleared, preferred availability ordering');
