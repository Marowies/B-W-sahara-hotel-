import en from '../src/messages/en.json' with {type:'json'};
import ar from '../src/messages/ar.json' with {type:'json'};
import zh from '../src/messages/zh.json' with {type:'json'};
import {entity} from '../src/content/entity.mjs';
import {languages,routeDefinitions,resolveRoute} from '../src/shared/locale.mjs';
import {pageContent} from '../src/shared/metadata.mjs';
export {languages};
export function seoConfig(env=process.env){
 const url=new URL(env.HOTEL_SITE_ORIGIN||entity.origin);
 if(url.protocol!=='https:'||url.username||url.password||url.pathname!=='/'||url.search||url.hash)throw Error('HOTEL_SITE_ORIGIN must be a bare HTTPS origin.');
 return {origin:url.origin,indexable:env.HOTEL_INDEXABLE==='1',entity,languages,routes:routeDefinitions,messages:{en,ar,zh}};
}
export function routePath(file){return file.replace(/index\.html$/,'');}
export function publicPath(path){return resolveRoute(path)?.indexable===true;}
const xml=value=>value.replaceAll('&','&amp;').replaceAll('"','&quot;').replaceAll('<','&lt;');
export function sitemap(config){
 const entries=config.indexable?routeDefinitions.filter(r=>r[4]!==false).flatMap(row=>languages.map((lang,i)=>{
  const content=pageContent(config,resolveRoute(row[i+1]));
  return `<url><loc>${xml(config.origin+row[i+1])}</loc>${content?`<lastmod>${entity.reviewed}</lastmod>`:''}${languages.map((code,n)=>`<xhtml:link rel="alternate" hreflang="${code==='zh'?'zh-Hans':code}" href="${xml(config.origin+row[n+1])}"/>`).join('')}<xhtml:link rel="alternate" hreflang="x-default" href="${xml(config.origin+row[1])}"/></url>`;
 })):[];
 return `<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">${entries.join('')}</urlset>\n`;
}
export function robots(config){
 if(!config.indexable)return 'User-agent: *\nDisallow: /\n';
 const excluded=['/admin/','/api/','/webhooks/','/localized/',...routeDefinitions.filter(r=>r[4]===false).flatMap(r=>r.slice(1,4))];
 return 'User-agent: *\nAllow: /\n'+excluded.map(p=>'Disallow: '+p+'\n').join('')+`Sitemap: ${config.origin}/sitemap.xml\n`;
}
export function markdownPage(config,route){
 const c=pageContent(config,route),title=config.messages[route.locale].seo[route.id].title.split(' | ')[0];
 if(!c)return null;
 const sections=[`# ${title}`,`> ${c.answer}`,c.facts||'',`Content reviewed: ${entity.reviewed}`,`Official page: ${config.origin}${route.path}`];
 if(c.comparison)sections.push(`## ${c.comparison.heading}\n\n| ${c.comparison.columns.join(' | ')} |\n| ${c.comparison.columns.map(()=>'---').join(' | ')} |\n${c.comparison.rows.map(row=>'| '+row.join(' | ')+' |').join('\n')}`);
 for(const step of c.steps||[])sections.push(`## ${step.heading}\n\n${step.text}`);
 for(const faq of c.faqs||[])sections.push(`## ${faq.question}\n\n${faq.answer}`);
 return sections.join('\n\n')+'\n';
}
export function llms(config){
 const wanted=['home','about','rooms','experiences','services','contact','faq','guide'];
 return `# ${entity.name}\n\n> ${entity.oneLiner}\n\nLocation: Bahariya–Farafra Road, Egypt. Contact: ${entity.telephone}; ${entity.email}.\nRoom categories and experiences must be confirmed with the hotel. The dome model is illustrative. Booking confirmation does not prove payment.\n\n`+languages.map((lang,i)=>`## ${{en:'Official planning information',ar:'المعلومات الرسمية للتخطيط للإقامة',zh:'住宿规划信息'}[lang]}\n\n`+routeDefinitions.filter(r=>wanted.includes(r[0])).map(row=>`- [${config.messages[lang].seo[row[0]].title.split(' | ')[0]}](${config.origin}${row[i+1]}index.md): [HTML](${config.origin}${row[i+1]})`).join('\n')).join('\n\n')+'\n';
}
