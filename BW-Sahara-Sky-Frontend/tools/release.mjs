// Production build: indexable by search engines, with real canonical URLs and sitemap.
// Override the domain with HOTEL_SITE_ORIGIN if the site is served from another origin.
import {spawnSync} from 'node:child_process';
const env={...process.env,HOTEL_INDEXABLE:'1',HOTEL_SITE_ORIGIN:process.env.HOTEL_SITE_ORIGIN||'https://bwsaharaskyhotel.com'};
for(const script of ['tools/compile.mjs']){
 const run=spawnSync(process.execPath,[script],{stdio:'inherit',env});
 if(run.status!==0)process.exit(run.status||1);
}
console.log(`Release build ready for ${env.HOTEL_SITE_ORIGIN}`);
