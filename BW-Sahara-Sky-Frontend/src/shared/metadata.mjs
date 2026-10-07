export function pageContent(config,route) {
 const catalogue=config.messages[route.locale];
 if(catalogue.geo[route.id])return catalogue.geo[route.id];
 if(route.id.startsWith('room-')){const {comparison,...content}=catalogue.geo.rooms;return content;}
 return null;
}
export function pageMetadata(config,route,assetUrl) {
 const catalogue=config.messages[route.locale],copy=catalogue.seo[route.id],canonical=config.origin+route.path;
 const row=config.routes.find(r=>r[0]===route.id);
 const alternates=route.indexable?config.languages.map((code,i)=>({lang:code==='zh'?'zh-Hans':code,href:config.origin+row[i+1]})).concat({lang:'x-default',href:config.origin+row[1]}):[];
 const content=pageContent(config,route),e=config.entity,graph=[];
 if(route.indexable){
  graph.push({'@type':'Hotel','@id':config.origin+'/#hotel',name:e.name,url:config.origin+'/',description:e.oneLiner,email:e.email,telephone:e.telephone,logo:assetUrl('hotel-official-logo.png'),image:assetUrl('exterior.jpg'),address:{'@type':'PostalAddress',...e.address},sameAs:e.sameAs});
  graph.push({'@type':'WebSite','@id':config.origin+'/#website',url:config.origin+'/',name:e.name,publisher:{'@id':config.origin+'/#hotel'},inLanguage:['en','ar','zh-Hans']});
  graph.push({'@type':'WebPage','@id':canonical+'#page',url:canonical,name:copy.title,description:copy.description,inLanguage:route.locale==='zh'?'zh-Hans':route.locale,isPartOf:{'@id':config.origin+'/#website'},...(content?{dateModified:e.reviewed}:{})});
  if(route.id!=='home')graph.push({'@type':'BreadcrumbList',itemListElement:[{'@type':'ListItem',position:1,name:catalogue.seo.home.title.split(' | ')[0],item:config.origin+config.routes[0][config.languages.indexOf(route.locale)+1]},{'@type':'ListItem',position:2,name:copy.title.split(' | ')[0],item:canonical}]});
  if(content?.faqs?.length)graph.push({'@type':'FAQPage','@id':canonical+'#questions',mainEntity:content.faqs.map(f=>({'@type':'Question',name:f.question,acceptedAnswer:{'@type':'Answer',text:f.answer}}))});
  if(route.id==='guide')graph.push({'@type':'Article','@id':canonical+'#article',headline:copy.title.split(' | ')[0],description:copy.description,mainEntityOfPage:{'@id':canonical+'#page'},datePublished:e.reviewed,dateModified:e.reviewed,inLanguage:route.locale==='zh'?'zh-Hans':route.locale,author:{'@type':'Organization',name:e.name+' website team'},publisher:{'@id':config.origin+'/#hotel'}});
 }
 return {...copy,canonical,alternates,robots:config.indexable&&route.indexable?'index,follow':'noindex,nofollow',image:assetUrl('exterior.jpg'),schema:{'@context':'https://schema.org','@graph':graph}};
}
