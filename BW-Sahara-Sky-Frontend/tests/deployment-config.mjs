import {test} from 'node:test';
import assert from 'node:assert/strict';
import {mkdir,writeFile,readFile,mkdtemp,rm} from 'node:fs/promises';
import {resolve} from 'node:path';
import {gunzipSync,brotliDecompressSync} from 'node:zlib';
import {backendMode,connectedHtml,configureDeployment} from '../tools/deployment-config.mjs';
test('Backend mode defaults to preview and rejects unsupported values',()=>{
 assert.equal(backendMode({}),'preview');assert.equal(backendMode({HOTEL_BACKEND_MODE:'same-origin'}),'same-origin');
 assert.throws(()=>backendMode({HOTEL_BACKEND_MODE:'https://remote.invalid'}));
});
test('Connected mode adds one same-origin marker after prerendering',()=>{
 const html='<html><head></head><body>Hotel</body></html>';
 assert.equal(connectedHtml(html,'preview'),html);
 const output=connectedHtml(html,'same-origin');assert.match(output,/name="hotel-backend" content="same-origin"/);
 assert.equal(connectedHtml(output,'same-origin'),output);
});
test('Published HTML and compressed copies have identical backend configuration',async()=>{
 const parent=resolve('.tooling');await mkdir(parent,{recursive:true});const root=await mkdtemp(resolve(parent,'deployment-test-'));
 try{
  await mkdir(resolve(root,'ar'),{recursive:true});
  await writeFile(resolve(root,'build-manifest.json'),JSON.stringify({routes:['/index.html']}));
  await writeFile(resolve(root,'prerender-manifest.json'),JSON.stringify({publicPaths:['/','/ar/']}));
  for(const file of ['index.html','ar/index.html'])await writeFile(resolve(root,file),'<html><head></head><body>Hotel</body></html>');
  await configureDeployment(root,'same-origin');
  for(const file of ['index.html','ar/index.html']){
   const html=await readFile(resolve(root,file),'utf8');assert.match(html,/hotel-backend/);
   assert.equal(gunzipSync(await readFile(resolve(root,file+'.gz'))).toString(),html);
   assert.equal(brotliDecompressSync(await readFile(resolve(root,file+'.br'))).toString(),html);
  }
  assert.equal(JSON.parse(await readFile(resolve(root,'build-manifest.json'),'utf8')).deployment.backendMode,'same-origin');
 }finally{
  assert(root.startsWith(parent+'\\')||root.startsWith(parent+'/'));
  await rm(root,{recursive:true,force:true});
 }
});
