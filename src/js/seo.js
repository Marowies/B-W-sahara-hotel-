(() => {
 'use strict';
 const config = window.__HOTEL_SEO__;
 window.hotelUpdateSeo = () => {
  if (!config) return;
  const code = new URLSearchParams(location.search).get('lang');
  const lang = ['en','ar','zh'].includes(code) ? code : 'en';
  const index = {en:0,ar:1,zh:2}[lang];
  const path = location.pathname.replace(/\/index\.html$/, '/');
  const page = document.body.dataset.page;
  const publicPage = !/^(booking|login|register|forgot|approval|review|confirmation|404)$/.test(page) && !/^\/(booking|login|register|forgot-password|design-review)(\/|$)/.test(path);
  const title = (config.translations[page]?.[index] || document.querySelector('main h1')?.textContent.trim() || document.title.split(' — ')[0]) + ' — B&W Sahara Sky';
  const description = (config.translations[page] ? config.translations[page][index] + '. ' : title.split(' — ')[0] + '. ') + config.intros[index];
  const url = language => config.origin + path + '?lang=' + language;
  const meta = (key,value,property=false) => {
   const attr = property ? 'property' : 'name';
   let el = document.head.querySelector(`meta[${attr}="${key}"]`);
   if (!el) { el = document.createElement('meta'); el.setAttribute(attr,key); document.head.append(el); }
   el.content = value;
  };
  document.title = title;
  document.documentElement.lang = lang === 'zh' ? 'zh-Hans' : lang;
  meta('description',description);
  meta('robots',config.indexable && publicPage ? 'index,follow' : 'noindex,nofollow');
  for (const node of document.head.querySelectorAll('link[rel="canonical"],link[hreflang],script[data-hotel-schema]')) node.remove();
  meta('og:title',title,true); meta('og:description',description,true); meta('og:type','website',true);
  meta('og:site_name','B&W Sahara Sky',true); meta('og:locale',{en:'en_GB',ar:'ar_EG',zh:'zh_CN'}[lang],true);
  meta('twitter:card','summary_large_image'); meta('twitter:title',title); meta('twitter:description',description);
  if (!config.origin) { for (const node of document.head.querySelectorAll('meta[property="og:url"],meta[property="og:image"],meta[name="twitter:image"]')) node.remove(); return; }
  const canonical = document.createElement('link'); canonical.rel='canonical'; canonical.href=url(lang); document.head.append(canonical);
  meta('og:url',url(lang),true);
  const image = new URL(window.hotelAssetUrl('exterior.jpg'),config.origin).href;
  meta('og:image',image,true); meta('twitter:image',image);
  if (!publicPage) return;
  for (const code of ['en','ar','zh','x-default']) {
   const link=document.createElement('link'); link.rel='alternate'; link.hreflang=code==='zh'?'zh-Hans':code; link.href=url(code==='x-default'?'en':code); document.head.append(link);
  }
  const schema=document.createElement('script'); schema.type='application/ld+json'; schema.dataset.hotelSchema='';
  const graph=[{'@type':'Hotel','@id':config.origin+'/#hotel',name:'B&W Sahara Sky',url:config.origin+'/?lang=en',image}];
  if(path!=='/')graph.push({'@type':'BreadcrumbList',itemListElement:[{'@type':'ListItem',position:1,name:config.translations.home[index],item:url('en').replace(path,'/')},{'@type':'ListItem',position:2,name:title.split(' — ')[0],item:url(lang)}]});
  schema.textContent=JSON.stringify({'@context':'https://schema.org','@graph':graph}); document.head.append(schema);
 };
 window.hotelUpdateSeo();
})();
