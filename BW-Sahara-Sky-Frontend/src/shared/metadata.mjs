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
 const ROOMS={'room-deluxe-single':[1,36],'room-deluxe-double':[2,36],'room-standard-single':[3,22],'room-standard-double':[4,22],'room-standard-triple':[5,28],'room-superior-single':[6,25],'room-superior-double':[7,25]};
 if(route.indexable){
  graph.push({'@type':'Hotel','@id':config.origin+'/#hotel',name:e.name,url:config.origin+'/',description:e.oneLiner,email:e.email,telephone:e.telephone,logo:assetUrl('hotel-official-logo.png'),image:assetUrl('exterior.jpg'),address:{'@type':'PostalAddress',...e.address},geo:{'@type':'GeoCoordinates',...e.geo},hasMap:e.hasMap,sameAs:e.sameAs,amenityFeature:[{'@type':'LocationFeatureSpecification',name:'Swimming pool',value:true},{'@type':'LocationFeatureSpecification',name:'Free Wi-Fi',value:true}],availableLanguage:['English','Arabic','Chinese']});
  graph.push({'@type':'WebSite','@id':config.origin+'/#website',url:config.origin+'/',name:e.name,publisher:{'@id':config.origin+'/#hotel'},inLanguage:['en','ar','zh-Hans']});
  graph.push({'@type':'WebPage','@id':canonical+'#page',url:canonical,name:copy.title,description:copy.description,inLanguage:route.locale==='zh'?'zh-Hans':route.locale,isPartOf:{'@id':config.origin+'/#website'},...(content?{dateModified:e.reviewed}:{})});
  if(route.id!=='home')graph.push({'@type':'BreadcrumbList',itemListElement:[{'@type':'ListItem',position:1,name:catalogue.seo.home.title.split(' | ')[0],item:config.origin+config.routes[0][config.languages.indexOf(route.locale)+1]},{'@type':'ListItem',position:2,name:copy.title.split(' | ')[0],item:canonical}]});
  // Room pages describe one room; the rooms page lists them all so search engines can connect rooms to the hotel.
  if(ROOMS[route.id])graph.push({'@type':'HotelRoom','@id':canonical+'#room',name:copy.title.split(' | ')[0].replace(/, Bahariya Oasis$/,''),description:copy.description,url:canonical,image:assetUrl('room-'+ROOMS[route.id][0]+'-0.jpg'),floorSize:{'@type':'QuantitativeValue',value:ROOMS[route.id][1],unitCode:'MTK'},containedInPlace:{'@id':config.origin+'/#hotel'}});
  if(route.id==='rooms')graph.push({'@type':'ItemList','@id':canonical+'#rooms',itemListElement:Object.keys(ROOMS).map((id,i)=>{const r=config.routes.find(x=>x[0]===id);return {'@type':'ListItem',position:i+1,name:catalogue.seo[id].title.split(' | ')[0].replace(/, Bahariya Oasis$/,''),url:config.origin+r[config.languages.indexOf(route.locale)+1]};})});
  if(content?.faqs?.length)graph.push({'@type':'FAQPage','@id':canonical+'#questions',mainEntity:content.faqs.map(f=>({'@type':'Question',name:f.question,acceptedAnswer:{'@type':'Answer',text:f.answer}}))});
  if(route.id==='guide')graph.push({'@type':'Article','@id':canonical+'#article',headline:copy.title.split(' | ')[0],description:copy.description,mainEntityOfPage:{'@id':canonical+'#page'},datePublished:e.reviewed,dateModified:e.reviewed,inLanguage:route.locale==='zh'?'zh-Hans':route.locale,author:{'@type':'Organization',name:e.name+' website team'},publisher:{'@id':config.origin+'/#hotel'}});
 }
 return {...copy,canonical,alternates,robots:config.indexable&&route.indexable?'index,follow':'noindex,nofollow',image:assetUrl(ROOMS[route.id]?'room-'+ROOMS[route.id][0]+'-0.jpg':'exterior.jpg'),schema:{'@context':'https://schema.org','@graph':graph}};
}
