import assert from 'node:assert/strict';import vm from 'node:vm';import {readFileSync} from 'node:fs';
const source=readFileSync(new URL('../src/js/runtime.js',import.meta.url),'utf8');const start=source.indexOf('    let edited = false, refreshed = false, checkingRelease = false;'),end=source.indexOf("    if ('serviceWorker' in navigator",start);assert(start>=0&&end>start);const code=source.slice(start,end);
async function fixture({dirty=false,latest='runtime.new.js',offline=false,marker=null,event='pageshow',visibility='visible'}={}){const handlers={},buttons=[],stored={'hotel-release':marker};let reloads=0,reloadsBeforeFetch=null,fetches=0;const document={documentElement:{lang:'en'},visibilityState:visibility,addEventListener:(name,fn)=>handlers[name]=fn,createElement:()=>({style:{}}),body:{append:b=>buttons.push(b)}};const context={document,window:{},nativeAdd:function(name,fn){handlers[name]=fn},runtimePath:'/runtime.old.js',location:{origin:'https://hotel.test',reload:()=>reloads++},URL,localStorage:{getItem:k=>stored[k]??null,setItem:(k,v)=>{stored[k]=v;}},fetch:async()=>{fetches++;if(offline)throw Error('offline');return {ok:true,json:async()=>({files:{'runtime.js':latest}})}}};
const before=stored['hotel-release'];vm.runInNewContext(code,context);const recorded=stored['hotel-release'];if(before!==null)stored['hotel-release']=before;
if(dirty)handlers.input();if(event==='pageshow')handlers.pageshow({persisted:true});else handlers.storage({key:'hotel-release',newValue:'/runtime.new.js'});reloadsBeforeFetch=reloads;await new Promise(r=>setTimeout(r,0));return {reloads,reloadsBeforeFetch,fetches,buttons,recorded};}
// Existing behaviour: back/forward return checks the manifest.
assert.equal((await fixture()).reloads,1);assert.equal((await fixture({latest:'runtime.old.js'})).reloads,0);const dirty=await fixture({dirty:true});assert.equal(dirty.reloads,0);assert.equal(dirty.buttons.length,1);assert.match(dirty.buttons[0].textContent,/saving your details/);assert.equal((await fixture({offline:true})).reloads,0);
// Each page records its release.
assert.equal((await fixture()).recorded,'/runtime.old.js');
// A newer release already seen in this browser: a restored old page reloads at once, without waiting for (or needing) the network.
const known=await fixture({marker:'/runtime.new.js',offline:true});assert.equal(known.reloadsBeforeFetch,1);assert.equal(known.fetches,0);
const knownDirty=await fixture({marker:'/runtime.new.js',dirty:true});assert.equal(knownDirty.reloads,0);assert.equal(knownDirty.buttons.length,1);
// Same release recorded: falls back to the manifest check (no reload when current).
assert.equal((await fixture({marker:'/runtime.old.js',latest:'runtime.old.js'})).reloads,0);
// Another tab opened a newer release: a hidden tab checks and updates in the background; a visible tab is left alone until it is shown.
assert.equal((await fixture({event:'storage',visibility:'hidden'})).reloads,1);assert.equal((await fixture({event:'storage',visibility:'visible'})).fetches,0);
assert.equal((await fixture({event:'storage',visibility:'hidden',dirty:true})).reloads,0);
console.log('PASS: stale back-page refresh, current release unchanged, unsaved input protected, offline return preserved, recorded release reloads restored pages without the network, hidden tabs update in the background');
