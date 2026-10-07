import {next} from '@vercel/functions';
import {localeRedirect} from './src/shared/locale-resolver.mjs';

export default function proxy(request) {
 // Only Vercel's platform request header is authoritative; never accept a query-country override.
 const redirect=localeRedirect(new URL(request.url),request.headers,{trustedCountryHeader:process.env.VERCEL==='1',method:request.method});
 if(redirect)return new Response(null,{status:redirect.status,headers:{Location:redirect.location,'Cache-Control':'private, no-store',Vary:'Cookie, User-Agent'}});
 return next();
}
