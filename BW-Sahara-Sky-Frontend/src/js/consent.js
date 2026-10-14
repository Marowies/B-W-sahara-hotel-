(() => {
 'use strict';
 const preference=()=>document.cookie.split('; ').find(x=>x.startsWith('hotel_cookie_consent='))?.split('=')[1];
 const text=(en,ar,zh)=>document.documentElement.lang.startsWith('ar')?ar:document.documentElement.lang.startsWith('zh')?zh:en;
 function dismiss(){document.querySelector('#hotel-cookie-banner')?.remove();}
 function choose(value){
  document.cookie='hotel_cookie_consent='+value+'; Path=/; Max-Age=15552000; SameSite=Lax'+(location.protocol==='https:'?'; Secure':'');
  try {
   if(value==='necessary')localStorage.removeItem('hotel-stay');
   else {const stay=JSON.parse(sessionStorage.getItem('sahara-stay-preview')||'null');if(stay)localStorage.setItem('hotel-stay',JSON.stringify({stay,expires:Date.now()+7*86400000}));}
  }catch{}
  dismiss();document.dispatchEvent(new Event('hotelconsentchange'));
 }
 function show(){
  dismiss();const box=document.createElement('section');box.id='hotel-cookie-banner';box.className='hotel-consent-panel';box.setAttribute('aria-label',text('Cookie preferences','اختيارات الكوكيز','Cookie 偏好'));
  const title=document.createElement('h2');title.textContent=text('Your privacy, your choice','خصوصيتك، اختيارك','您的隐私，由您选择');
  const note=document.createElement('p');note.textContent=text('Necessary cookies keep sign-in and your current booking working. Accept all to remember your stay preferences and allow optional analytics.','الكوكيز الضرورية بتشغّل تسجيل الدخول والحجز الحالي. قبول الكل يسمح بحفظ اختيارات الإقامة وإحصائيات الاستخدام الاختيارية.','必要 Cookie 用于登录和当前预订。接受全部可保存住宿偏好并允许可选统计。');
  const actions=document.createElement('div');actions.className='hotel-consent-actions';
  for(const [value,label] of [['necessary',text('Necessary only','الضرورية فقط','仅必要')],['all',text('Accept all','قبول الكل','接受全部')]]){const button=document.createElement('button');button.type='button';button.textContent=label;button.onclick=()=>choose(value);actions.append(button);}
  if(['necessary','all'].includes(preference())){const close=document.createElement('button');close.type='button';close.textContent=text('Close','إغلاق','关闭');close.onclick=dismiss;actions.append(close);}
  box.append(title,note,actions);document.body.append(box);
 }
 function mount(){
  if(!document.getElementById('hotel-consent-style')){const style=document.createElement('style');style.id='hotel-consent-style';style.textContent='.hotel-consent-panel{position:fixed;inset:auto 16px 16px auto;z-index:10050;width:500px;max-width:calc(100vw - 32px);box-sizing:border-box;padding:22px;border:1px solid #ccb79b;border-radius:16px;background:#fffaf3;color:#382b20;box-shadow:0 12px 36px #30251c33;font:16px/1.5 Arial,sans-serif}.hotel-consent-panel h2{color:#60452f;font:26px/1.2 Georgia,serif;margin:0 0 12px}.hotel-consent-panel p{color:#382b20;margin:0 0 16px}.hotel-consent-actions{display:flex;flex-wrap:wrap;gap:10px}.hotel-consent-actions button{border:1px solid #60452f;border-radius:8px;padding:10px 14px;background:#60452f;color:#fffaf3;cursor:pointer;font:inherit}.hotel-consent-actions button:focus-visible{outline:3px solid #875435;outline-offset:3px}.cookie-settings{cursor:pointer;background:transparent;border:0;color:inherit;font:inherit;text-decoration:underline;padding:10px}@media (max-width:600px){.hotel-consent-panel{inset:auto 0 0 0;width:100%;max-width:none;padding:14px 16px calc(14px + env(safe-area-inset-bottom));border-width:1px 0 0;border-radius:16px 16px 0 0;box-shadow:0 -8px 28px #30251c26;font-size:13px;line-height:1.45}.hotel-consent-panel h2{display:none}.hotel-consent-panel p{margin:0 0 10px}.hotel-consent-actions{flex-wrap:nowrap;gap:8px}.hotel-consent-actions button{flex:1;padding:9px 10px;white-space:nowrap}}';document.head.append(style);}
  // No footer link: a page that wants one adds an element with class cookie-settings (or calls hotelConsent.show()).
  const button=document.querySelector('.cookie-settings');
  if(button){button.textContent=text('Cookie settings','إعدادات الكوكيز','Cookie 设置');button.onclick=show;}
  // Prerendered banners have no live handlers and must not override a saved choice.
  dismiss();
  if(!['necessary','all'].includes(preference()))show();
 }
 window.hotelConsent={mount,preference,show};
 window.addEventListener('pageshow',event=>{if(event.persisted)mount();});
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount,{once:true});else mount();
})();
