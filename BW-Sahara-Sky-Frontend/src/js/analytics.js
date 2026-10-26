(() => {
 const consented=()=>document.cookie.split('; ').includes('hotel_cookie_consent=all');
 // Booking-funnel events share the page-view channel: nothing is sent without "Accept all"; no personal data is included.
 window.hotelTrack=(event,extra={})=>{
  if(!consented())return;
  const route=window.hotelLocale?.resolveRoute(location.pathname);
  const payload={page_id:route?.id||'',ui_locale:route?.locale||'',...extra};
  window.dispatchEvent(new CustomEvent('hotel:analytics',{detail:{event,properties:payload}}));
  window.hotelAnalyticsAdapter?.(event,payload);
 };
 // Optional Google Analytics 4: loads only when a measurement ID was set at build time and the visitor accepted analytics.
 const gaId=window.__HOTEL_GA_ID;
 if(gaId){
  const loadGa=()=>{
   if(window.__hotelGaLoaded||!consented())return;
   window.__hotelGaLoaded=true;window.dataLayer=window.dataLayer||[];window.gtag=function(){window.dataLayer.push(arguments);};
   window.gtag('js',new Date());window.gtag('config',gaId,{anonymize_ip:true,send_page_view:false});
   const script=document.createElement('script');script.async=true;script.src='https://www.googletagmanager.com/gtag/js?id='+encodeURIComponent(gaId);document.head.append(script);
   window.hotelAnalyticsAdapter=(name,props)=>window.gtag('event',name,props);
  };
  loadGa();document.addEventListener('hotelconsentchange',loadGa);
 }
 const record=()=>{
  if(!document.cookie.split('; ').includes('hotel_cookie_consent=all'))return;
  const route=window.hotelLocale.resolveRoute(location.pathname);if(!route)return;
  let host='';try{host=new URL(document.referrer).hostname.toLowerCase();}catch{}
  const source=new URLSearchParams(location.search).get('utm_source')?.toLowerCase();
  const ai=['chatgpt.com','chat.openai.com','perplexity.ai','claude.ai','gemini.google.com','copilot.microsoft.com'];
  const aiReferral=ai.some(h=>host===h||host.endsWith('.'+h))||['chatgpt','perplexity','claude','gemini','copilot'].includes(source);
  const payload={page_id:route.id,ui_locale:route.locale,traffic_channel:aiReferral?'ai_referral':host===location.hostname?'internal':host==='www.google.com'||host==='www.bing.com'?'organic_search':host?'referral':'direct'};
  window.dispatchEvent(new CustomEvent('hotel:analytics',{detail:{event:'hotel_page_view',properties:payload}}));
  window.hotelAnalyticsAdapter?.('hotel_page_view',payload);
 };
 const previous=window.__hotelViewScope;window.__hotelViewScope=null;
 document.addEventListener('hotelroutechange',record);document.addEventListener('hotelconsentchange',record);window.__hotelViewScope=previous;record();
})();
