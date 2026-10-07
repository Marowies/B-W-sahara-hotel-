(() => {
 'use strict';
 const config=window.__HOTEL_SEO__,route=window.hotelLocale.resolveRoute(location.pathname),main=document.querySelector('main');
 if(!route||!main)return;
 const content=window.hotelMetadata.pageContent(config,route),labels=config.messages[route.locale].app;
 if(!content)return;
 const escape=value=>String(value).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 const table=content.comparison?`<div class="answer-comparison"><h2>${escape(content.comparison.heading)}</h2><div class="answer-table-wrap" tabindex="0"><table><thead><tr>${content.comparison.columns.map(v=>`<th scope="col">${escape(v)}</th>`).join('')}</tr></thead><tbody>${content.comparison.rows.map(row=>`<tr>${row.map((v,i)=>i===0?`<th scope="row">${escape(v)}</th>`:`<td>${escape(v)}</td>`).join('')}</tr>`).join('')}</tbody></table></div></div>`:'';
 const answer=`<section class="answer-section page-body" data-hotel-answer><span class="eyebrow">${escape(labels.quickAnswers)}</span><h2>${escape(content.heading)}</h2><p class="answer-lead">${escape(content.answer)}</p>${content.facts?`<p>${escape(content.facts)}</p>`:''}<p class="answer-reviewed">${escape(labels.updated)}: <time datetime="${config.entity.reviewed}">${config.entity.reviewed}</time></p><a class="text-link" href="${window.hotelLocale.localizedPath('guide',route.locale)}">${escape(labels.guideLink)}</a></section>`;
 main.querySelectorAll('[data-hotel-answer],[data-hotel-questions],[data-hotel-comparison]').forEach(n=>n.remove());
 if(route.id==='guide'){
  main.innerHTML=`<section class="page-hero"><img src="${window.hotelAssetUrl('desert.jpg')}" alt="" class="hero-image" fetchpriority="high" decoding="async"><div class="page-hero-copy"><span class="eyebrow">${escape(config.entity.name)}</span><h1>${escape(config.messages[route.locale].seo.guide.title.split(' | ')[0])}</h1></div></section>${answer}<article class="page-body guide-article"><p>${escape(content.byline)}</p>${content.steps.map(s=>`<h2>${escape(s.heading)}</h2><p>${escape(s.text)}</p>`).join('')}<h2>${escape(content.suitability.heading)}</h2><p>${escape(content.suitability.text)}</p></article>`;
  main.querySelector('[data-hotel-answer] .text-link')?.remove();
 }else{const hero=main.querySelector('.page-hero,.hero');if(hero)hero.insertAdjacentHTML('afterend',answer);else main.insertAdjacentHTML('afterbegin',answer);}
 if(table)main.insertAdjacentHTML('beforeend',`<section class="page-body" data-hotel-comparison>${table}</section>`);
 main.insertAdjacentHTML('beforeend',`<section class="page-body answer-faq" data-hotel-questions id="questions"><h2>${escape(labels.faqTitle)}</h2>${content.faqs.map(f=>`<details><summary>${escape(f.question)}</summary><p>${escape(f.answer)}</p></details>`).join('')}</section>`);
})();
