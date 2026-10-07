(() => {
 'use strict';
 window.hotelUpdateSeo=()=>{
  const config=window.__HOTEL_SEO__,route=window.hotelLocale.resolveRoute(location.pathname);
  if(!config||!route)return;
  const m=window.hotelMetadata.pageMetadata(config,route,name=>new URL(window.hotelAssetUrl(name),config.origin).href);
  document.title=m.title;document.documentElement.lang=route.locale==='zh'?'zh-Hans':route.locale;document.documentElement.dir=route.locale==='ar'?'rtl':'ltr';
  const meta=(key,value,property=false)=>{const attr=property?'property':'name';let node=document.head.querySelector(`meta[${attr}="${key}"]`);if(!node){node=document.createElement('meta');node.setAttribute(attr,key);document.head.append(node);}node.content=value;};
  meta('description',m.description);meta('robots',m.robots);meta('og:title',m.title,true);meta('og:description',m.description,true);meta('og:type',route.id==='guide'?'article':'website',true);meta('og:site_name',config.entity.name,true);meta('og:url',m.canonical,true);meta('og:image',m.image,true);meta('og:locale',{en:'en_GB',ar:'ar_EG',zh:'zh_CN'}[route.locale],true);meta('twitter:card','summary_large_image');meta('twitter:title',m.title);meta('twitter:description',m.description);meta('twitter:image',m.image);
  document.head.querySelectorAll('link[rel="canonical"],link[hreflang],link[data-hotel-markdown],script[data-hotel-schema]').forEach(n=>n.remove());
  const canonical=document.createElement('link');canonical.rel='canonical';canonical.href=m.canonical;document.head.append(canonical);
  for(const alt of m.alternates){const node=document.createElement('link');node.rel='alternate';node.hreflang=alt.lang;node.href=alt.href;document.head.append(node);}
  if(window.hotelMetadata.pageContent(config,route)){const node=document.createElement('link');node.rel='alternate';node.type='text/markdown';node.href=m.canonical+'index.md';node.dataset.hotelMarkdown='';document.head.append(node);}
  if(m.schema['@graph'].length){const schema=document.createElement('script');schema.type='application/ld+json';schema.dataset.hotelSchema='';schema.textContent=JSON.stringify(m.schema).replaceAll('<','\\u003c');document.head.append(schema);}
 };
 window.hotelUpdateSeo();
})();
