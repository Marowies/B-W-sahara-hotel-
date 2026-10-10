(() => {
 'use strict';
 // Pages always open at the top unless the address points to a section.
 if('scrollRestoration' in history)history.scrollRestoration='manual';
 if(!location.hash)scrollTo(0,0);
 // Prerendered snapshots already contain the parts injected below; drop them so they are not added twice.
 document.querySelectorAll('body>.skip-link,body>.mobile-book,body>#lightbox,#open-immersive+.preview-note,main>.stay-strip,main>.home-note,#rooms>.all-rooms,#experiences>.all-rooms,#moments>.all-rooms').forEach(node=>node.remove());
 document.querySelectorAll('.enquiry-select').forEach(node=>node.remove());
 const params = new URLSearchParams(location.search);
 const lang = window.hotelI18n.locale();
 const index = {en:0,ar:1,zh:2}[lang];
 const t = window.hotelI18n.t;
 const page = document.body.dataset.page;
 const path = (window.hotelLocale.resolveRoute(location.pathname)?.logical||location.pathname).replace(/\/$/,'') || '/';
 const url = route => {const u=new URL(route,location.origin);u.pathname=window.hotelLocale.localizedPath(u.pathname,lang);u.searchParams.delete('lang');return u.pathname+u.search+u.hash;};
 const esc = value => String(value).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 const labels = {
 home:t('marketing.m_9fa0c1ff3ba5'),rooms:t('marketing.m_80fc78a8f306'),about:t('marketing.m_b5c415fc1b3f'),services:t('marketing.m_33f11820f192'),experiences:t('marketing.m_5ee8ff93818a'),gallery:t('marketing.m_d643ccaadc20'),blog:t('marketing.m_5078f5c9535d'),contact:t('marketing.m_35c06b9ead7d'),faq:t('marketing.m_5d97de1f0cfc'),privacy:t('marketing.m_3304b9f66f37'),terms:t('marketing.m_65456de410c2'),login:t('auth.m_3e9a278fd967'),register:t('auth.m_5a56f27d5730'),forgot:t('auth.m_9fd3d96bd508'),booking:t('marketing.m_4b18ebe3cd9a'),review:t('marketing.m_0b4b3e73a2ec'),confirmation:t('marketing.m_f01929883375'),approval:t('marketing.m_a0c8b5a66dd8'),availability:t('marketing.m_2c1aab0c852b'),viewRooms:t('marketing.m_ab4a160ecce8'),allRooms:t('marketing.m_3a60d739f96a'),details:t('marketing.m_e5ec34ad8366'),more:t('marketing.m_5f665f99ae59'),all:t('marketing.m_fb3f8139920a'),standard:t('marketing.m_e1f1849d4985'),superior:t('marketing.m_8a475aa54521'),deluxe:t('marketing.m_24727f508fce'),domes:t('marketing.m_8980cde0c43e'),visualisation:t('marketing.m_50c725b7e371'),close:t('marketing.m_9ec112bec064'),arrival:t('marketing.m_eb910f3759fd'),departure:t('marketing.m_56c278b6493a'),adults:t('marketing.m_319b550e6c1b'),children:t('marketing.m_54e662211d8b'),guests:t('marketing.m_1114d6da8e61'),preview:t('marketing.m_9bbd7dcb6397'),name:t('marketing.m_95ba15e92afb'),email:t('marketing.m_654a3e34016c'),phone:t('marketing.m_a37e2fb93265'),next:t('marketing.m_044376d091fc'),back:t('marketing.m_e5fdce9733c3'),password:t('auth.m_979f58a0761a'),demo:t('marketing.m_ec2ed4f4b213'),demoNote:t('marketing.m_4019c650a009'),menu:t('marketing.m_d43bb6c39daf'),stay:t('marketing.m_a55c8e0d9715'),language:t('marketing.m_a46c9e42ca4e'),photo:t('marketing.m_f9c880858e6c'),read:t('marketing.m_116d56f49b79'),request:t('marketing.m_9aab408c385a'),reviewNote:t('marketing.m_cc0683e3a319')
 };
 document.documentElement.lang=lang;
 document.documentElement.dir=lang==='ar'?'rtl':'ltr';
 const routes={home:'/',about:'/about-us/',rooms:'/rooms/',services:'/services/',experiences:'/experiences/',gallery:'/galleries/',blog:'/blog/',contact:'/contact-us/',faq:'/faq/',privacy:'/privacy/',terms:'/term-and-conditions/',login:'/login/',register:'/register/',forgot:'/forgot-password/',booking:'/booking/',approval:'/design-review/'};
 const link = (key,extra='') => `<a href="${url(routes[key])}" ${extra}>${labels[key]}</a>`;
 const brand = `<a class="brand" href="${url('/')}"><img src="assets/logo.png" alt="B&W Sahara Sky"><span>B&W SAHARA SKY<small>${t('marketing.m_756d2e378ba6')}</small></span></a>`;
 const header = document.querySelector('header.nav');
 header.innerHTML=brand+`<nav id="nav-links">${['home','rooms','experiences','gallery','about','contact'].map(key=>link(key, page===key?'aria-current="page"':'')).join('')}${page==='home'?`<button class="motion-button mobile-motion" aria-pressed="false">${t('marketing.m_a03a59d6a6ef')}</button>`:''}</nav><div class="nav-actions"><label class="sr-only" for="language">${labels.language}</label><select id="language" class="language-select"><option value="en">EN</option><option value="ar">العربية</option><option value="zh">中文</option></select>${page==='home'?`<button class="motion-button" aria-pressed="false">${t('marketing.m_a03a59d6a6ef')}</button>`:''}<button class="button small" data-book>${labels.availability}</button><button class="menu-toggle" aria-expanded="false" aria-controls="nav-links">${labels.menu}</button></div>`;
 document.querySelector('#language').value=lang;
 document.querySelector('#language').addEventListener('change',async event=>{const code=event.target.value;await window.hotelI18n.remember(code);location.href=window.hotelLocale.localizedPath(path==='/'?'/':path+'/',code);});
 // Styled language menu; the native select stays the source of truth.
 {const sel=document.querySelector('#language');const names={en:'English',ar:'العربية',zh:'中文'};const short={en:'EN',ar:'AR',zh:'中文'};
 const wrap=document.createElement('div');wrap.className='lang-menu';
 const globe='<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z"/></svg>';
 wrap.innerHTML='<button type="button" class="lang-btn" aria-haspopup="listbox" aria-expanded="false" aria-label="'+labels.language+'">'+globe+'<span>'+short[lang]+'</span><svg class="lang-chev" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg></button><ul class="lang-list" role="listbox" tabindex="-1">'+[...sel.options].map(o=>'<li role="option" data-v="'+o.value+'" aria-selected="'+(o.value===lang)+'" tabindex="-1"><span>'+names[o.value]+'</span><small>'+short[o.value]+'</small></li>').join('')+'</ul>';
 sel.after(wrap);sel.hidden=true;
 const btn=wrap.querySelector('.lang-btn'),items=[...wrap.querySelectorAll('li')];
 const open=v=>{wrap.classList.toggle('open',v);btn.setAttribute('aria-expanded',v);if(v)(items.find(i=>i.dataset.v===lang)||items[0]).focus();};
 const choose=v=>{open(false);if(v===lang)return;sel.value=v;sel.dispatchEvent(new Event('change'));};
 btn.addEventListener('click',e=>{e.stopPropagation();open(!wrap.classList.contains('open'));});
 items.forEach((li,i)=>{li.addEventListener('click',()=>choose(li.dataset.v));li.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();choose(li.dataset.v)}else if(e.key==='ArrowDown'){e.preventDefault();items[(i+1)%items.length].focus()}else if(e.key==='ArrowUp'){e.preventDefault();items[(i-1+items.length)%items.length].focus()}else if(e.key==='Escape'||e.key==='Tab'){open(false);btn.focus()}});});
 document.addEventListener('click',e=>{if(!wrap.contains(e.target))open(false);});}
 const icons={
 instagram:'<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".7" fill="currentColor" stroke="none"/>',
 facebook:'<path fill="currentColor" stroke="none" d="M14 22v-9h3l.5-4H14V7c0-1.1.3-2 2-2h2V1.4A26 26 0 0 0 15 1c-3 0-5 1.8-5 5v3H7v4h3v9z"/>',
 tiktok:'<path d="M14 3v12a4.5 4.5 0 1 1-4-4.47M14 3c.6 3 2.6 5 6 5V5c-1.9-.2-3-1-3.5-2z"/>',
 whatsapp:'<path d="M20.5 11.6a8.5 8.5 0 0 1-12.7 7.5L3 20.5l1.4-4.8A8.5 8.5 0 1 1 20.5 11.6z"/><path d="M8 7.5c-.5.5-.6 1.5-.1 2.6 1 2.3 2.7 4 5 5 .9.4 1.9.4 2.5-.1l.7-1.2-2.7-1.3-.8.9c-1.4-.6-2.5-1.7-3.1-3.1l.8-.8L9 7z"/>',
 email:'<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>'
 };
 const channels=[['instagram','Instagram','https://www.instagram.com/bw_sahara_sky_hotel/'],['facebook','Facebook','https://www.facebook.com/Sahara.Starry.Sky.Camp'],['tiktok','TikTok','https://www.tiktok.com/@bwsaharaskyhotel'],['whatsapp','WhatsApp','https://wa.me/201098255777'],['email',t('marketing.m_7886375dcc0e'),'mailto:'+window.__HOTEL_SEO__.entity.email]];
 const socials=`<div class="socials icon-channels" aria-label="${t('marketing.m_f98c8a0e4ddb')}">${channels.map(([id,label,href])=>`<a class="channel-icon" href="${href}" aria-label="${label}" title="${label}" ${id==='email'?'':'target="_blank" rel="noopener noreferrer"'}><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">${icons[id]}</svg></a>`).join('')}</div>`;
 document.querySelector('footer').innerHTML=`<div class="footer-cta"><div><span class="eyebrow">${t('marketing.m_82fdd4f92c5d')}</span><h2>${t('marketing.m_1c23f2f15fe0')}</h2></div></div><div class="footer-top"><div class="footer-story">${brand}<p>${t('marketing.m_4c99029d5087')}</p>${socials}</div><nav class="footer-links" aria-label="${t('marketing.m_86ff922b52e2')}"><h3>${t('marketing.m_86ff922b52e2')}</h3>${['rooms','experiences','services','gallery','about','blog'].map(k=>link(k)).join('')}</nav><nav class="footer-links" aria-label="${t('marketing.m_ec581797163b')}"><h3>${t('marketing.m_ec581797163b')}</h3>${['contact','faq','login','privacy','terms','approval'].map(k=>link(k)).join('')}</nav><div class="footer-contact"><h3>${t('marketing.m_0dfba4ab40ac')}</h3><p><a class="map-link" href="https://maps.app.goo.gl/EVzKGmchofL5DgaFA" target="_blank" rel="noopener" aria-label="${t('marketing.m_475c890946bf')}">${t('marketing.m_43363af16409')}</a></p><a class="footer-phone" href="tel:+201098255777" dir="ltr">+20 109 825 5777</a><a class="footer-mail" href="mailto:${window.__HOTEL_SEO__.entity.email}">${window.__HOTEL_SEO__.entity.email}</a></div></div><div class="footer-bottom"><p class="footer-line"><span>${t('app.copyright').replace('{year}',new Date().getFullYear())}</span> <span class="footer-credit">${t('marketing.m_01674baf5a0b')} <a href="https://wa.me/201557541184" target="_blank" rel="noopener" dir="ltr">Cube Of Code</a></span></p></div>`;
 document.body.insertAdjacentHTML('afterbegin',`<a class="skip-link" href="${url(location.pathname)}#main">${t('marketing.m_9c0e8db7d797')}</a>`);
 const main=document.querySelector('main');main.id='main';
 document.body.insertAdjacentHTML('beforeend',`<div class="mobile-book"><span>${labels.stay}</span><button class="button" data-book>${labels.availability}</button></div><dialog id="lightbox"><button class="close" aria-label="${labels.close}">×</button><img alt=""><p></p></dialog>`);
 const rooms=[
 {id:2,slug:'bw-sahara-sky-hotel-deluxe-room-double',name:t('marketing.m_3b026444d81d'),category:'deluxe',size:36,beds:t('marketing.m_e841ca349cfb'),people:2},
 {id:1,slug:'bw-sahara-sky-hotel-deluxe-room-single',name:t('marketing.m_908633f7d1e1'),category:'deluxe',size:36,beds:t('marketing.m_d7a667f97a51'),people:2},
 {id:7,slug:'bw-sahara-sky-hotel-superior-room-double',name:t('marketing.m_31f3d65dfef7'),category:'superior',size:25,beds:t('marketing.m_e841ca349cfb'),people:2},
 {id:6,slug:'bw-sahara-sky-hotel-superior-room-single',name:t('marketing.m_c97c72e75c2e'),category:'superior',size:25,beds:t('marketing.m_d7a667f97a51'),people:2},
 {id:5,slug:'bw-sahara-sky-hotel-standard-room-triple',name:t('marketing.m_977e99751f62'),category:'standard',size:28,beds:t('marketing.m_029a1af842f8'),people:3},
 {id:4,slug:'bw-sahara-sky-hotel-standard-room-double',name:t('marketing.m_42afa1fef99d'),category:'standard',size:22,beds:t('marketing.m_e841ca349cfb'),people:2},
 {id:3,slug:'standard-room-single',name:t('marketing.m_1f5832aa350e'),category:'standard',size:22,beds:t('marketing.m_d7a667f97a51'),people:2}
 ];
 const roomIntro=category=>category==='deluxe'?t('marketing.m_058c94671a29'):category==='superior'?t('marketing.m_8a93c8918714'):t('marketing.m_0dc8507f1b81');
 const roomCard=(r,i=0,select=false)=>`<article class="stay-card" data-category="${r.category}"><a class="card-photo" href="${url('/rooms/'+r.slug+'/')}"><img src="assets/room-${r.id}-0.jpg" alt="${r.name}" loading="lazy" width="850" height="460"><span class="card-tag">${labels[r.category]}</span></a><span class="card-index">${String(i+1).padStart(2,'0')} / ${r.size} m² · ${r.beds}</span><h3>${r.name}</h3><p>${roomIntro(r.category)}</p><div class="card-actions"><a class="text-link" href="${url('/rooms/'+r.slug+'/')}">${labels.details}</a>${select===true?`<button class="button" data-select-room="${r.id}">${t('marketing.m_6cc2e64458b9')}</button>`:`<button class="button" data-book data-room-id="${r.id}">${labels.availability}</button>`}</div></article>`;
 const hero=(title,subtitle,image='exterior.jpg',key=page)=>`<section class="page-hero"><img src="assets/${image}" alt="${t('marketing.m_36035c464ab0')}" fetchpriority="high"><div class="page-hero-copy"><div class="crumbs"><a href="${url('/')}">${labels.home}</a><span>/</span><span>${labels[key]||title}</span></div><h1>${title}</h1><p>${subtitle}</p></div></section>`;
 const heading=(over,title,description='')=>`<div class="section-title"><div><span class="eyebrow">${over}</span><h2>${title}</h2></div>${description?`<p>${description}</p>`:''}</div>`;
 const banner=()=>`<div class="approval-banner">${labels.reviewNote}</div>`;
 const amenityNames=[t('marketing.m_1f495cde7a2b'),t('marketing.m_e60e74ff9875'),t('marketing.m_70170041c708'),t('marketing.m_d5c8b6a5e496')];
 const stayFields=()=>`<div class="form-grid"><label>${labels.arrival}<input type="date" name="arrival" required></label><label>${labels.departure}<input type="date" name="departure" required></label><label>${labels.adults}<select name="adults">${[1,2,3,4].map(v=>`<option ${v===2?'selected':''}>${v}</option>`).join('')}</select></label><label>${labels.children}<select name="children">${[0,1,2,3].map(v=>`<option>${v}</option>`).join('')}</select></label><label class="full">${t('marketing.m_b9cca73f1de3')}<select name="experience"><option value="room">${t('marketing.m_beb49b954657')}</option><option value="safari">${t('marketing.m_f535602b4671')}</option><option value="stars">${t('marketing.m_7e468a765311')}</option></select></label></div>`;
 if(page!=='home')document.body.insertAdjacentHTML('beforeend',`<dialog id="booking"><button class="close" aria-label="${labels.close}">×</button><span class="eyebrow">B&W SAHARA SKY</span><h2>${labels.booking}</h2><form id="stay-form" class="approval-form">${stayFields()}<button class="button" type="submit">${labels.preview}</button><p id="form-result" role="status"></p></form><p class="preview-note">${labels.demoNote}</p></dialog>`);
 const stories=[
 {slug:'a-desert-made-for-discovery',image:'exterior.jpg',topic:t('marketing.m_e4a9f1494487'),title:t('marketing.m_69e3e4fc7f32'),intro:t('marketing.m_50794c3642ce')},
 {slug:'a-night-beneath-the-stars',image:'04-1.jpg',topic:t('marketing.m_f035b4dc5597'),title:t('marketing.m_904bc5f697d6'),intro:t('marketing.m_2bcaff7b18e1')},
 {slug:'the-art-of-a-slower-stay',image:'desert.jpg',topic:t('marketing.m_7a569c0cc1a3'),title:t('marketing.m_201b1526d00b'),intro:t('marketing.m_a459678f6ab4')}
 ];
 const storyCard=s=>`<article class="article-card"><a href="${url('/blog/'+s.slug+'/')}"><img src="assets/${s.image}" alt="${s.title}" loading="lazy"><div class="article-meta">${s.topic} · ${t('marketing.m_4653e3af739b')}</div><h2>${s.title}</h2><p>${s.intro}</p><span class="text-link">${labels.read}</span></a></article>`;
 const faqItems=[
 [t('marketing.m_4e1c8c872c29'),t('marketing.m_831f8306d796')],
 [t('marketing.m_155913713baf'),labels.demoNote],
 [t('marketing.m_234d951a7696'),t('marketing.m_79f6dbaf6b12')],
 [t('marketing.m_05630fcfb7b2'),t('marketing.m_631870bcf4e4')],
 [t('marketing.m_2e9fc242135b'),t('marketing.m_a18d13d9bbe4')],
 [t('marketing.m_025944213d70'),t('marketing.m_c4045731688e')],
 [t('marketing.m_82b639ed7c3e'),t('marketing.m_dbe00a900fdf')]
 ];
 const faqs=()=>faqItems.map(([q,a])=>`<details><summary>${q}</summary><p>${a}</p></details>`).join('');
 const requestLink=`<a class="button" href="${url('/contact-us/')}">${labels.request}</a>`;
 if(page!=='home')main.innerHTML=window.hotelRenderPage({page, path, t, labels, rooms, roomCard, roomIntro, hero, heading, banner, link, url, amenityNames, stories, storyCard, faqs, requestLink, socials, stayFields, routes});

 // Section intros so every inner page opens with the same centred heading.
 const intros={experiences:[t('marketing.m_305cc64de92b'),t('marketing.m_4ceaf7cc3c69'),t('marketing.m_fb273b5bf83c')],gallery:[t('marketing.m_b29ca632f85a'),t('marketing.m_714e5fc85619'),t('marketing.m_02dc5484dced')]};
 if(intros[page])main.querySelector('.page-body')?.insertAdjacentHTML('afterbegin',heading(...intros[page]));
 const showPhoto=(image,caption)=>{const box=document.querySelector('#lightbox');box.querySelector('img').src=window.hotelAssetUrl(image);box.querySelector('img').alt=caption;box.querySelector('p').textContent=caption;box.showModal();};
 const getStay=()=>{try{return JSON.parse(sessionStorage.getItem('sahara-stay-preview')||'null');}catch{return null;}};
 const saveStay=stay=>{try{sessionStorage.setItem('sahara-stay-preview',JSON.stringify(stay));}catch{}};
 const dateText=value=>{if(!/^\d{4}-\d{2}-\d{2}$/.test(value||''))return '';const d=new Date(value+'T12:00:00');return d.toLocaleDateString(lang==='ar'?'ar-EG':lang==='zh'?'zh-CN':'en-GB',{day:'numeric',month:'short',year:'numeric'});};
 const summary=stay=>`${esc(dateText(stay.arrival))} · ${esc(dateText(stay.departure))}<br>${esc(stay.adults)} ${labels.adults} · ${esc(stay.children)} ${labels.children}`;
 const today=(()=>{const d=new Date();return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;})();
 const fillForm=(form,stay)=>{if(!stay)return;for(const key of ['arrival','departure','adults','children','experience']){const field=form.elements[key];if(!field||stay[key]===undefined)continue;if(key==='experience'){const options=Array.from(field.options);const saved=stay[key];let option=options.find(o=>o.value===saved);if(!option){const type=saved.includes('safari')?'safari':saved.includes('star')?'stars':'room';option=options.find(o=>type==='safari'?o.value.includes('safari'):type==='stars'?o.value.includes('star'):o.value==='room'||o.value==='A room in the desert');}field.value=option?.value||options[0].value;}else field.value=stay[key];}};
 document.querySelectorAll('input[type=date][name=arrival]').forEach(input=>input.min=today);
 document.querySelectorAll('form').forEach(form=>{
 const arrival=form.elements.arrival,departure=form.elements.departure;
 if(arrival&&departure){fillForm(form,getStay());departure.min=arrival.value||today;arrival.addEventListener('change',()=>{departure.min=arrival.value||today;if(departure.value<=arrival.value)departure.value='';});}
 });
 const collectStay=form=>{
 const data=Object.fromEntries(new FormData(form));
 if(!data.arrival||!data.departure||data.arrival<today||data.departure<=data.arrival){form.querySelector('[role=status]')?.replaceChildren(document.createTextNode(t('errors.m_02470b2b6c2c')));return null;}
 const previous=getStay();let experience=data.experience||'room';if(!['room','safari','stars'].includes(experience))experience=experience.includes('safari')?'safari':experience.includes('stargazing')?'stars':'room';return {arrival:data.arrival,departure:data.departure,adults:String(data.adults||2),children:String(data.children||0),experience,roomId:previous?.roomId||null};
 };
 function openBooking(roomId){const form=document.querySelector('#stay-form');if(roomId){const stay=getStay()||{};stay.roomId=Number(roomId);saveStay(stay);}fillForm(form,getStay());document.querySelector('#booking').showModal();}
 document.addEventListener('click',event=>{
 const book=event.target.closest('[data-book]');if(book&&page!=='home')openBooking(book.dataset.roomId);
 if(book&&page==='home'&&book.dataset.roomId){const s=getStay()||{};s.roomId=Number(book.dataset.roomId);saveStay(s);}
 const close=event.target.closest('dialog .close');if(close&&page!=='home')close.closest('dialog').close();if(close&&close.closest('#lightbox'))close.closest('dialog').close();
 const photo=event.target.closest('[data-lightbox]');if(photo)showPhoto(photo.dataset.lightbox,photo.dataset.caption||'B&W Sahara Sky');
 const filter=event.target.closest('[data-filter]');if(filter){const container=filter.closest('.page-body');container.querySelectorAll('[data-filter]').forEach(b=>b.setAttribute('aria-pressed',String(b===filter)));container.querySelectorAll('[data-category]').forEach(card=>card.hidden=filter.dataset.filter!=='all'&&card.dataset.category!==filter.dataset.filter);}
 if(event.target.closest('#nav-links a')){document.querySelector('#nav-links')?.classList.remove('open');document.querySelector('.menu-toggle')?.setAttribute('aria-expanded','false');}const menu=event.target.closest('.menu-toggle');if(menu&&page!=='home'){const nav=document.querySelector('#nav-links');const open=nav.classList.toggle('open');menu.setAttribute('aria-expanded',String(open));}
 const choose=event.target.closest('[data-select-room]');if(choose){const s=getStay();if(!s?.arrival){window.hotelNavigate(url('/booking/'));return;}s.roomId=Number(choose.dataset.selectRoom);saveStay(s);window.hotelNavigate(url('/booking/review/'));}
 });
 document.querySelectorAll('dialog').forEach(dialog=>dialog.addEventListener('click',event=>{if(event.target===dialog){const r=dialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)dialog.close();}}));
 if(page!=='home'){
 const scrolled=()=>header.classList.toggle('scrolled',scrollY>80);window.addEventListener('scroll',scrolled,{passive:true});scrolled();
 }
 const stayForm=document.querySelector('#stay-form');stayForm.classList.add('approval-form');
 stayForm.onsubmit=event=>{event.preventDefault();const s=collectStay(stayForm);if(s){saveStay(s);window.hotelNavigate(url('/booking/'));}};
 const bookingSearch=document.querySelector('#booking-search');
 const showResults=stay=>{document.querySelector('.booking-results').hidden=false;document.querySelector('#search-summary').innerHTML=`<strong>${t('marketing.m_66fb8f5cc402')}</strong><br>${summary(stay)}`;};
 if(bookingSearch){if(getStay()?.arrival&&getStay()?.departure)showResults(getStay());bookingSearch.onsubmit=event=>{event.preventDefault();const s=collectStay(bookingSearch);if(s){saveStay(s);showResults(s);document.querySelector('.booking-results').scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'start'});}};}
 const stay=getStay();
 if(page==='review'){
 const selected=rooms.find(r=>r.id===stay?.roomId);
 document.querySelector('#review-state').innerHTML=selected&&stay?.arrival?`<div class="detail-layout"><div><img src="assets/room-${selected.id}-0.jpg" alt="${selected.name}" style="width:100%;max-height:400px;object-fit:cover"><div class="review-summary"><h3>${selected.name}</h3>${summary(stay)}<br>${selected.size} m² · ${selected.beds}</div><a class="text-link" href="${url('/booking/')}">${t('marketing.m_2ff504b58aae')}</a></div><aside class="booking-aside"><h3>${t('marketing.m_29e0cfe7a1f8')}</h3><p>${labels.demoNote}</p><p>${t('marketing.m_05aea58faae6')}</p><a class="button" href="${url('/booking/confirmation/')}">${t('marketing.m_da2c4f6e84da')}</a></aside></div>`:`<div class="complete-state"><h2>${t('marketing.m_91dca2aecd0a')}</h2><a class="button" href="${url('/booking/')}">${labels.booking}</a></div>`;
 }
 if(page==='confirmation'&&stay?.arrival){const selected=rooms.find(r=>r.id===stay.roomId);document.querySelector('#confirmation-summary').innerHTML=`<div class="review-summary">${selected?`<strong>${selected.name}</strong><br>`:''}${summary(stay)}</div>`;}
 ['contact-form','auth-form'].forEach(id=>{const form=document.getElementById(id);if(form)form.onsubmit=event=>{event.preventDefault();form.querySelector('.form-status').textContent=t('marketing.m_00d7fb1e8626');};});
 if(page==='home'){
 document.querySelectorAll('.motion-button').forEach(button=>{button.setAttribute('aria-pressed',String(window.motionPaused));button.textContent=window.motionPaused?t('marketing.m_81f9cfa86339'):t('marketing.m_a03a59d6a6ef');button.onclick=()=>{window.motionPaused=!window.motionPaused;document.body.classList.toggle('motion-paused',window.motionPaused);document.querySelectorAll('.motion-button').forEach(b=>{b.setAttribute('aria-pressed',String(window.motionPaused));b.textContent=window.motionPaused?t('marketing.m_81f9cfa86339'):t('marketing.m_a03a59d6a6ef');});document.dispatchEvent(new Event('motionchange'));};});
 document.querySelector('#room-image').src='assets/room-2-0.jpg';
 document.querySelector('#rooms [data-book]').textContent=labels.availability;
 document.querySelector('#rooms .section-mark>span:first-child').textContent='02';
 document.querySelector('#architecture .cinema-copy>.eyebrow').textContent=t('marketing.m_db061c55da76');
 document.querySelector('#open-immersive').insertAdjacentHTML('afterend',`<p class="preview-note">${t('marketing.m_8de6cecd6008')}</p>`);
 main.querySelector('.hero').insertAdjacentHTML('afterend',`<section class="stay-strip"><div><h2>${labels.booking}</h2><p>${t('marketing.m_bbcf4605e560')}</p></div><form id="quick-stay"><label>${labels.arrival}<input type="date" name="arrival" required min="${today}"></label><label>${labels.departure}<input type="date" name="departure" required min="${today}"></label><label>${labels.guests}<select name="adults"><option>1</option><option selected>2</option><option>3</option><option>4</option></select></label><button class="button" type="submit">${labels.availability}</button><p class="sr-only" role="status"></p></form></section>`);
 const quick=document.querySelector('#quick-stay');fillForm(quick,getStay());
 // iPhone shows empty date fields as blank boxes: give them a visible placeholder.
 quick.querySelectorAll('input[type=date]').forEach(input=>{const mark=()=>input.parentElement.classList.toggle('date-empty',!input.value);input.parentElement.dataset.placeholder=t('marketing.m_ce0af6f888f0');input.addEventListener('input',window.hotelDebounce(mark));input.addEventListener('change',mark);mark();});quick.elements.arrival.onchange=()=>{quick.elements.departure.min=quick.elements.arrival.value;};quick.onsubmit=event=>{event.preventDefault();const s=collectStay(quick);if(s){saveStay(s);window.hotelNavigate(url('/booking/'));}};
 document.querySelector('#rooms').insertAdjacentHTML('beforeend',`<div class="all-rooms"><a class="text-link" href="${url('/rooms/')}">${labels.allRooms}</a></div>`);
 document.querySelector('#experiences').insertAdjacentHTML('beforeend',`<div class="all-rooms"><a class="text-link" href="${url('/experiences/')}">${t('marketing.m_609bd844a08d')}</a></div>`);
 document.querySelector('#moments').insertAdjacentHTML('beforeend',`<div class="all-rooms"><a class="text-link" href="${url('/galleries/')}">${t('marketing.m_f640325fbc00')}</a></div>`);
 main.querySelector('.final-call').insertAdjacentHTML('beforebegin',`<section class="home-note"><div><span class="eyebrow">${t('marketing.m_1f0f8f166b2e')}</span><h2>${t('marketing.m_7a9f988506bc')}</h2><p>${stories[0].intro}</p></div><a class="text-link" href="${url('/blog/')}">${labels.blog}</a></section>`);
 // Preserve the existing home and its 3D implementation; localise rendered text as it changes.
 const translations = new Map();
 const pairs=window.hotelI18n.homePairs();
 // Photo captions include an index; keep the number and translate the caption.
 pairs.filter(p=>['A place of your own','Slow afternoons, wide horizons','Under a sky full of possibility','A different kind of neighbourhood','When the world turns quiet'].includes(p[0])).forEach((p,i)=>translations.set(`0${i+1} / ${p[0]}`,`0${i+1} / ${lang==='ar'?p[1]:p[2]}`));
 pairs.forEach(([en,ar,zh])=>translations.set(en,lang==='ar'?ar:zh));
 if(lang!=='en'){
 let pending=false;const translate=()=>{pending=false;const walker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT);let node;while((node=walker.nextNode())){if(node.parentElement.closest('script,style'))continue;const key=node.textContent.trim();if(translations.has(key))node.textContent=node.textContent.replace(key,translations.get(key));}};translate();new MutationObserver(()=>{if(!pending){pending=true;queueMicrotask(translate);}}).observe(document.body,{childList:true,subtree:true,characterData:true});
 }
 document.querySelectorAll('[data-i18n]').forEach(e=>{if(labels[e.dataset.i18n])e.textContent=labels[e.dataset.i18n];});
 document.querySelectorAll('a[href^="/"]').forEach(a=>{if(!a.href.includes('lang=')){a.setAttribute('href',url(a.getAttribute('href')));}});
 }
 if(document.querySelector('meta[name="hotel-backend"]')){
  const api=async(path,options={})=>{const response=await fetch('/api/hotel/'+path,{credentials:'same-origin',cache:'no-store',...options,headers:{Accept:'application/json',...options.headers}});const data=await response.json();if(!response.ok)throw new Error((lang==='en'&&data.message)||t('errors.m_0ec1af01aea1'));return data;};
  const cataloguePath=page==='rooms'?'rooms?per_page=20':'rooms';
  const catalogue=['rooms','room'].includes(page)?window.hotelCatalogue(cataloguePath,()=>api(cataloguePath)):Promise.resolve(null);
  const busyGrid=main.querySelector('.room-grid');
  if(page==='rooms')busyGrid?.setAttribute('aria-busy','true');
  function backendCards(rows,grid){if(!grid)return;grid.innerHTML=rows.map((row,i)=>{const original=rooms.find(r=>r.slug===row.slug);const card={...original,id:original?.id||2,slug:original?.slug||'copper-glass-domes',category:original?.category||'standard',name:lang==='en'?esc(row.name):(original?.name||labels.rooms),size:row.size,people:row.max_adults,beds:esc(String(row.number_of_beds))+' '+t('marketing.m_58799a0f9b97')};return roomCard(card,i);}).join('');grid.querySelectorAll('.stay-card').forEach((card,i)=>{const row=rows[i];card.dataset.backendRoomId=row.id;const rate=document.createElement('p');rate.className='backend-room-rate';rate.textContent=t('marketing.m_9cb4df045594')+new Intl.NumberFormat(lang,{maximumFractionDigits:2}).format(row.price)+' '+(row.currency||'');card.append(rate);if(!rooms.some(r=>r.slug===row.slug)){card.querySelectorAll('a').forEach(a=>a.href=url('/contact-us/')+'#contact-form');}});if(!rows.length){const empty=document.createElement('p');empty.textContent=t('marketing.m_6436a2b10eba');grid.append(empty);}}
  function paginate(data,grid,load){
   if(!grid)return;
   grid.removeAttribute('aria-busy');
   grid.parentElement.querySelector('.catalogue-pagination')?.remove();
   const paging=data.pagination;if(!paging||paging.last_page<=1)return;
   const nav=document.createElement('nav');nav.className='catalogue-pagination';nav.setAttribute('aria-label',labels.rooms);
   nav.innerHTML=`<button class="button small" type="button" data-page="${paging.page-1}" ${paging.page<=1?'disabled':''}>${labels.back}</button><span>${paging.page} / ${paging.last_page}</span><button class="button small" type="button" data-page="${paging.page+1}" ${paging.page>=paging.last_page?'disabled':''}>${labels.next}</button>`;
   grid.after(nav);nav.querySelectorAll('button').forEach(button=>button.onclick=async()=>{nav.querySelectorAll('button').forEach(b=>b.disabled=true);grid.setAttribute('aria-busy','true');try{await load(Number(button.dataset.page));}catch(error){grid.removeAttribute('aria-busy');nav.querySelector('span').textContent=error.message;nav.querySelectorAll('button').forEach(b=>b.disabled=Number(b.dataset.page)<1||Number(b.dataset.page)>paging.last_page);}});
  }
  async function loadCatalogue(pageNumber=1){const key='rooms?'+new URLSearchParams({per_page:20,page:pageNumber});const data=await window.hotelCatalogue(key,()=>api(key));if(!main.isConnected)return;const grid=main.querySelector('.room-grid');backendCards(data.rooms,grid);paginate(data,grid,loadCatalogue);main.querySelector('[data-filter="all"]')?.click();}
  catalogue.finally(()=>busyGrid?.removeAttribute('aria-busy')).then(data=>{if(!data||!main.isConnected)return;if(page==='rooms'){backendCards(data.rooms,busyGrid);paginate(data,busyGrid,loadCatalogue);}if(page==='room'&&lang==='en'){const row=data.rooms.find(r=>path==='/rooms/'+r.slug);if(row){main.querySelector('h1').textContent=row.name;const description=main.querySelector('.detail-layout>div>p');if(description)description.textContent=row.description;}}}).catch(()=>{if(!main.isConnected)return;const notice=document.createElement('p');notice.className='form-status';notice.textContent=t('errors.m_a4e9a6b424d4');main.querySelector('.page-body')?.prepend(notice);});
  if(bookingSearch){main.querySelector('.booking-results').hidden=true;main.querySelector('.booking-results .eyebrow').textContent=t('marketing.m_d6f2ad9b46c6');bookingSearch.querySelector('button[type=submit]').textContent=labels.availability;bookingSearch.onsubmit=async event=>{event.preventDefault();const stay=collectStay(bookingSearch);if(!stay)return;saveStay(stay);const status=bookingSearch.querySelector('.form-status'),button=bookingSearch.querySelector('button[type=submit]');button.disabled=true;status.textContent=t('marketing.m_613b4731c1c5');try{const result=await api('availability?'+new URLSearchParams({...stay,per_page:20}));if(!main.isConnected)return;showResults(stay);const results=main.querySelector('.booking-results');backendCards(result.rooms,results.querySelector('.room-grid'));paginate(result,results.querySelector('.room-grid'),async function nextAvailability(pageNumber){const next=await api('availability?'+new URLSearchParams({...stay,per_page:20,page:pageNumber}));if(!main.isConnected)return;backendCards(next.rooms,results.querySelector('.room-grid'));paginate(next,results.querySelector('.room-grid'),nextAvailability);});results.querySelector('.preview-note').textContent=t('marketing.m_1cc550df65ff');status.textContent='';}catch(error){status.textContent=error.message;main.querySelector('.booking-results').hidden=true;}finally{button.disabled=false;}};}
  const contact=document.querySelector('#contact-form');if(contact){contact.querySelector('button[type=submit]').textContent=t('marketing.m_71ffefe08618');contact.querySelector('.preview-note').textContent=t('marketing.m_549e4c6fa72f');contact.onsubmit=async event=>{event.preventDefault();const status=contact.querySelector('.form-status'),button=contact.querySelector('button[type=submit]');button.disabled=true;try{const session=await api('session');await api('contact',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':session.csrf_token},body:JSON.stringify(Object.fromEntries(new FormData(contact)))});status.textContent=t('marketing.m_aa47a94c0e44');contact.reset();}catch(error){status.textContent=error.message;}finally{button.disabled=false;}};}
  // Payment step is closed until the guest signs in with a one-time code emailed to them. The server enforces it too (checkout/eligibility).
  // Access token (15 min) lives only in memory; the rotating refresh token is an HttpOnly cookie the page cannot read.
  const auth=window.hotelAuth||(window.hotelAuth={token:null,exp:0,pending:null});
  const send=async(path,body,bearer)=>{try{const headers={Accept:'application/json'};if(body){const s=await(await fetch('/api/hotel/session',{credentials:'same-origin',cache:'no-store'})).json();Object.assign(headers,{'Content-Type':'application/json','X-CSRF-TOKEN':s.csrf_token});}if(bearer)headers.Authorization='Bearer '+bearer;const r=await fetch('/api/hotel/'+path,{method:body?'POST':'GET',credentials:'same-origin',cache:'no-store',headers,body:body?JSON.stringify(body):undefined});let data={};try{data=await r.json();}catch{}return {ok:r.ok,status:r.status,data};}catch{return {ok:false,status:0,data:{}};}};
  const keep=r=>{if(r.ok&&r.data.access_token){auth.token=r.data.access_token;auth.exp=Date.now()+r.data.expires_in*1000;auth.email=r.data.email||auth.email;}return r;};
  const ensureToken=()=>{if(auth.token&&auth.exp-Date.now()>30000)return Promise.resolve(true);return auth.pending||(auth.pending=send('auth/refresh',{}).then(r=>{auth.pending=null;if(!keep(r).ok){auth.token=null;return false;}return true;}));};
  const gateCall=async(path,body)=>{if(path==='auth/otp/verify'||path==='auth/otp/request')return keep(await send(path,body));if(path==='auth/logout'){const r=await send(path,{},auth.token);if(r.ok){auth.token=null;auth.exp=0;auth.email=null;}return r;}if(!await ensureToken())return {ok:false,status:401,data:{code:'login_required'}};return send(path,body,auth.token);};
  // One-time-code sign-in UI shared by the payment gate and the login pages.
  const otpFlow=(gate,status,onShow,onDone,introKey='otp.intro')=>{
  const fail=r=>{const d=r.data||{};if(r.status===429&&d.retry_after)return t('otp.errWait').replace('{s}',d.retry_after);const map={invalid:'otp.errInvalid',expired:'otp.errExpired',locked:'otp.errLocked'};if(map[d.code])return t(map[d.code]);if(r.status===422)return t('otp.errEmail');if(r.status===503)return t('otp.errSend');return t('otp.errGeneric');};
  const field=(label,attrs)=>{const l=document.createElement('label');l.append(label);const i=document.createElement('input');Object.entries(attrs).forEach(([k,v])=>i.setAttribute(k,v));l.append(i);return [l,i];};
  const intro=text=>{const p=document.createElement('p');p.textContent=text;return p;};
  const mk=(cls,text,type='button')=>{const b=document.createElement('button');b.type=type;b.className=cls;b.textContent=text;return b;};
  function showEmail(email=''){
  status.textContent='';gate.hidden=false;onShow();
  const form=document.createElement('form');form.className='approval-form';form.noValidate=false;
  const [l,i]=field(t('marketing.m_654a3e34016c'),{type:'email',name:'email',required:'',autocomplete:'email',maxlength:'255',inputmode:'email'});i.value=email;
  const send=mk('button',t('otp.send'),'submit');
  form.append(intro(t(introKey)),l,send);gate.replaceChildren(form);i.focus();
  form.onsubmit=async e=>{e.preventDefault();send.disabled=true;status.textContent=t('otp.sending');const r=await gateCall('auth/otp/request',{email:i.value.trim()});send.disabled=false;if(r.ok){status.textContent='';showCode(i.value.trim().toLowerCase(),r.data.resend_in||60);}else status.textContent=fail(r);};
  }
  function showCode(email,wait){
  const form=document.createElement('form');form.className='approval-form';
  const [l,i]=field(t('otp.codeLabel'),{type:'text',name:'code',required:'',inputmode:'numeric',pattern:'[0-9]{6}',maxlength:'6',autocomplete:'one-time-code',dir:'ltr'});
  const verify=mk('button',t('otp.verify'),'submit'),resend=mk('text-link',''),change=mk('text-link',t('otp.change'));
  const row=document.createElement('p');row.append(resend,' · ',change);
  form.append(intro(t('otp.codeHelp').replace('{email}',email)),l,verify,row);gate.replaceChildren(form);i.focus();
  let left=wait;const tick=()=>{resend.disabled=left>0;resend.textContent=left>0?t('otp.resendIn').replace('{s}',left):t('otp.resend');};tick();
  const timer=setInterval(()=>{if(!form.isConnected){clearInterval(timer);return;}if(left>0){left--;tick();}},1000);
  change.onclick=()=>{clearInterval(timer);showEmail(email);};
  resend.onclick=async()=>{if(left>0)return;resend.disabled=true;const r=await gateCall('auth/otp/request',{email});if(r.ok){left=r.data.resend_in||60;status.textContent=t('otp.resent');}else{status.textContent=fail(r);left=r.data?.retry_after||0;}tick();};
  form.onsubmit=async e=>{e.preventDefault();verify.disabled=true;status.textContent=t('otp.verifying');const r=await gateCall('auth/otp/verify',{email,code:i.value.trim()});if(r.ok){clearInterval(timer);onDone(email);}else{verify.disabled=false;status.textContent=fail(r);i.select();}};
  }
  return {showEmail,fail};
  };
  if(page==='confirmation'){gateCall('checkout/eligibility').then(r=>{if(r.status===401&&main.isConnected)window.hotelNavigate(url('/booking/review/'));});}
  if(page==='login'||page==='register'){
   const form=document.querySelector('#auth-form'),panel=form?.parentElement;
   if(panel){
    const status=document.createElement('p');status.className='form-status';status.setAttribute('role','status');
    const gate=document.createElement('div');gate.className='otp-gate';
    form.replaceWith(gate,status);
    const done=email=>{const who=document.createElement('p'),out=document.createElement('button');out.type='button';out.className='text-link';out.textContent=t('otp.signOut');who.append(t('otp.signedIn').replace('{email}',email||'')+' ',out);gate.replaceChildren(who);status.textContent='';out.onclick=async()=>{out.disabled=true;const result=await gateCall('auth/logout');if(result.ok)flow.showEmail();else{status.textContent=flow.fail(result);out.disabled=false;}};};
    const flow=otpFlow(gate,status,()=>{},done,'otp.introLogin');
    ensureToken().then(ok=>{if(!main.isConnected)return;if(ok)done(auth.email);else flow.showEmail();});
   }
  }
  if(page==='review'){
   const aside=main.querySelector('.booking-aside'),go=aside?.querySelector('a.button');
   if(aside&&go){
    const btn=document.createElement('button');btn.type='button';btn.className='button';btn.textContent=t('otp.continue');go.replaceWith(btn);
    const status=document.createElement('p');status.className='form-status';status.setAttribute('role','status');
    const gate=document.createElement('div');gate.className='otp-gate';gate.hidden=true;
    aside.append(gate,status);
    const proceed=async()=>{
     const result=await gateCall('checkout/eligibility');
     if(!main.isConnected)return;
     if(result.ok){status.textContent=t('marketing.m_1cc550df65ff');gate.hidden=true;btn.hidden=false;return;}
     if(result.status===401){gate.hidden=false;showEmail();return;}
     status.textContent=fail(result);
    };
     const {showEmail,fail}=otpFlow(gate,status,()=>{btn.hidden=true;},email=>{gate.hidden=true;status.textContent=t('otp.signedIn').replace('{email}',email);proceed();});
    btn.onclick=async()=>{btn.disabled=true;await proceed();btn.disabled=false;};
    ensureToken().then(ok=>{if(!ok||!main.isConnected)return;const who=document.createElement('p'),out=document.createElement('button');out.type='button';out.className='text-link';out.textContent=t('otp.signOut');who.append(t('otp.signedIn').replace('{email}',auth.email||'')+' ',out);status.before(who);out.onclick=async()=>{out.disabled=true;const result=await gateCall('auth/logout');if(result.ok)who.remove();else{status.textContent=fail(result);out.disabled=false;}};});
   }
  }
 }
 // The base URL must not drop the language when jumping to a home section.
 document.querySelectorAll('a[href^="#"]').forEach(a=>{a.href=location.pathname+a.getAttribute('href');});
 document.querySelectorAll('[data-lightbox]').forEach(button=>{if(!button.getAttribute('aria-label'))button.setAttribute('aria-label',`${labels.photo}: ${button.dataset.caption||button.querySelector('img')?.alt||labels.domes}`);});
 // Keep the native control as the form value; the themed list handles pointer
 // and keyboard selection without the operating system's blue option palette.
 document.querySelectorAll('#contact-form select[name="subject"],#quick-stay select,.approval-form select,#stay-form select').forEach((subject,index)=>{
  const label=subject.closest('label'),wrap=document.createElement('span');wrap.className='enquiry-select';
  const button=document.createElement('button');button.type='button';button.className='enquiry-select-button';button.setAttribute('role','combobox');button.setAttribute('aria-label',label.firstChild.textContent.trim());button.setAttribute('aria-haspopup','listbox');button.setAttribute('aria-expanded','false');
  const list=document.createElement('span');list.className='enquiry-options';list.id=`enquiry-options-${index}`;list.setAttribute('role','listbox');list.setAttribute('aria-label',button.getAttribute('aria-label'));list.hidden=true;button.setAttribute('aria-controls',list.id);
  const choices=Array.from(subject.options,(option,i)=>{const item=document.createElement('span');item.className='enquiry-option';item.setAttribute('role','option');item.tabIndex=-1;item.textContent=option.textContent;item.addEventListener('click',()=>choose(i));list.append(item);return item;});
  function sync(){button.textContent=subject.options[subject.selectedIndex]?.textContent||'';button.disabled=subject.disabled;choices.forEach((item,i)=>item.setAttribute('aria-selected',String(i===subject.selectedIndex)));}
  function close(){list.hidden=true;button.setAttribute('aria-expanded','false');}
  function open(){sync();list.hidden=false;button.setAttribute('aria-expanded','true');choices[subject.selectedIndex]?.focus();}
  function choose(i){subject.selectedIndex=i;subject.dispatchEvent(new Event('change',{bubbles:true}));sync();close();button.focus();}
  button.addEventListener('click',()=>list.hidden?open():close());
  button.addEventListener('keydown',e=>{if(['ArrowDown','ArrowUp'].includes(e.key)){e.preventDefault();open();}});
  list.addEventListener('keydown',e=>{const i=choices.indexOf(document.activeElement);let next=i;if(e.key==='ArrowDown')next=(i+1)%choices.length;else if(e.key==='ArrowUp')next=(i+choices.length-1)%choices.length;else if(e.key==='Home')next=0;else if(e.key==='End')next=choices.length-1;else if(['Enter',' '].includes(e.key)){e.preventDefault();choose(i);return;}else if(e.key==='Escape'){e.preventDefault();close();button.focus();return;}else if(e.key==='Tab'){close();return;}else return;e.preventDefault();choices[next].focus();});
  document.addEventListener('click',e=>{if(!wrap.contains(e.target))close();});
  subject.addEventListener('change',sync);subject.form.addEventListener('reset',()=>setTimeout(sync,0));
  subject.hidden=true;subject.after(wrap);wrap.append(button,list);sync();
 });
 document.title=`${['room','article'].includes(page)?main.querySelector('h1')?.textContent.trim()||labels.rooms:labels[page]||t('marketing.m_6166ec0ee0a3')} — B&W Sahara Sky`;
})();
