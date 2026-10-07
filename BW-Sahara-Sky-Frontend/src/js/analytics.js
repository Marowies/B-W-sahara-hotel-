(() => {
 const record=()=>{
  const route=window.hotelLocale.resolveRoute(location.pathname);if(!route)return;
  let host='';try{host=new URL(document.referrer).hostname.toLowerCase();}catch{}
  const source=new URLSearchParams(location.search).get('utm_source')?.toLowerCase();
  const ai=['chatgpt.com','chat.openai.com','perplexity.ai','claude.ai','gemini.google.com','copilot.microsoft.com'];
  const aiReferral=ai.some(h=>host===h||host.endsWith('.'+h))||['chatgpt','perplexity','claude','gemini','copilot'].includes(source);
  const payload={page_id:route.id,ui_locale:route.locale,traffic_channel:aiReferral?'ai_referral':host==='www.google.com'||host==='www.bing.com'?'organic_search':host?'referral':'direct'};
  window.dispatchEvent(new CustomEvent('hotel:analytics',{detail:{event:'hotel_page_view',properties:payload}}));
  window.hotelAnalyticsAdapter?.('hotel_page_view',payload);
 };
 const previous=window.__hotelViewScope;window.__hotelViewScope=null;
 document.addEventListener('hotelroutechange',record);window.__hotelViewScope=previous;record();
})();
