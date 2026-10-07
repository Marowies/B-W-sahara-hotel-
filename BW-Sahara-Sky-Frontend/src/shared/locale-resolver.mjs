import {languages,resolveRoute,localizedPath} from './locale.mjs';
export const crawlerPattern = /bot|crawler|spider|slurp|bingpreview|facebookexternalhit|twitterbot|linkedinbot|chatgpt-user|oai-searchbot|anthropic-ai|google-extended|applebot|bytespider|yandex|baiduspider/i;
const countryLocales = {EG:'ar',SA:'ar',AE:'ar',QA:'ar',KW:'ar',BH:'ar',OM:'ar',JO:'ar',LB:'ar',IQ:'ar',MA:'ar',DZ:'ar',TN:'ar',CN:'zh'};
export function localeRedirect(url,headers,{trustedCountryHeader=false,method='GET'}={}) {
 if (!['GET','HEAD'].includes(method)) return null;
 const route=resolveRoute(url.pathname);
 if (!route) return null; // Admin, APIs, auth callbacks, assets and webhooks never match.
 const legacy=url.searchParams.get('lang');
 const clean=new URL(url);clean.searchParams.delete('lang');
 if(legacy!==null){clean.pathname=localizedPath(route.id,languages.includes(legacy)?legacy:route.locale);return {status:301,location:clean.pathname+clean.search+clean.hash,reason:'legacy-locale'};}
 if(url.pathname!==route.path){clean.pathname=route.path;return {status:301,location:clean.pathname+clean.search,reason:'canonical-path'};}
 if(route.locale!=='en'||!route.indexable||crawlerPattern.test(headers.get('user-agent')||''))return null;
 const preference=(headers.get('cookie')||'').match(/(?:^|;\s*)hotel_locale=(en|ar|zh)(?:;|$)/)?.[1];
 const country=trustedCountryHeader?(headers.get('x-vercel-ip-country')||'').toUpperCase():null;
 const locale=preference||countryLocales[country]||'en';
 if(locale==='en')return null;
 clean.pathname=localizedPath(route.id,locale);
 return {status:307,location:clean.pathname+clean.search,reason:preference?'locale-preference':'trusted-country'};
}
