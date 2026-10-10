import {readFile,writeFile} from 'node:fs/promises';
import {resolve,sep} from 'node:path';
import {gzipSync,brotliCompressSync} from 'node:zlib';

export function backendMode(env=process.env){
 const mode=env.HOTEL_BACKEND_MODE||'preview';
 if(!['preview','same-origin'].includes(mode))throw new Error('HOTEL_BACKEND_MODE must be preview or same-origin');
 return mode;
}
export function connectedHtml(html,mode){
 if(mode!=='same-origin')return html;
 if(/<meta\b[^>]*name=["']hotel-backend["']/i.test(html))return html;
 if(!html.includes('</head>'))throw new Error('Missing HTML head');
 return html.replace('</head>','<meta name="hotel-backend" content="same-origin"></head>');
}
// Apply after prerendering: build-time Chromium has no booking API and must
// never serialize a catalogue failure into the published HTML.
export async function configureDeployment(directory,mode){
 const root=resolve(directory),manifestPath=resolve(root,'build-manifest.json');
 const manifest=JSON.parse(await readFile(manifestPath,'utf8'));
 const prerender=mode==='same-origin'?JSON.parse(await readFile(resolve(root,'prerender-manifest.json'),'utf8')):null;
 const routes=[...new Set([...manifest.routes,...(prerender?.publicPaths||[]).map(p=>p.endsWith('.html')?p:p+'index.html')])];
 if(mode==='same-origin')for(const route of routes.filter(p=>p.endsWith('.html'))){
  const path=resolve(root,'.'+route);
  if(!path.startsWith(root+sep))throw new Error('Invalid build route');
  const html=connectedHtml(await readFile(path,'utf8'),mode);
  await writeFile(path,html);
  await writeFile(path+'.gz',gzipSync(html));
  await writeFile(path+'.br',brotliCompressSync(Buffer.from(html)));
 }
 manifest.deployment={backendMode:mode};
 await writeFile(manifestPath,JSON.stringify(manifest,null,2));
}
