(() => {
 'use strict';
 const locale=()=>window.hotelLocale.resolveRoute(location.pathname)?.locale||'en';
 const at=(object,key)=>key.split('.').reduce((node,part)=>node?.[part],object);
 window.hotelI18n={
  locale,
  t(key,ar,zh){
   if(ar!==undefined)return [key,ar,zh][window.hotelLocale.languages.indexOf(locale())];
   const value=at(window.__HOTEL_SEO__.messages[locale()],key);
   if(typeof value!=='string')throw new Error('Missing message: '+key);
   return value;
  },
  homePairs(){const messages=window.__HOTEL_SEO__.messages;return Object.keys(messages.en.home).map(key=>window.hotelLocale.languages.map(c=>messages[c].home[key]));},
  remember(code){
   if(!window.hotelLocale.languages.includes(code))return;
   document.cookie='hotel_locale='+code+'; Path=/; Max-Age=31536000; SameSite=Lax'+(location.protocol==='https:'?'; Secure':'');
   if(document.querySelector('meta[name="hotel-backend"]'))return fetch('/api/hotel/session',{credentials:'same-origin',cache:'no-store',signal:AbortSignal.timeout(3000)}).then(r=>r.json()).then(s=>fetch('/api/hotel/locale',{method:'POST',credentials:'same-origin',signal:AbortSignal.timeout(3000),headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':s.csrf_token,...(window.hotelAuth?.token?{Authorization:'Bearer '+window.hotelAuth.token}:{})},body:JSON.stringify({ui_locale:code})})).catch(()=>{});
  },
 };
})();
