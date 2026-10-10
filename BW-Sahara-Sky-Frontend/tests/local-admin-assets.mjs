import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import { mkdtemp, mkdir, writeFile, rm } from 'node:fs/promises';
import { resolve } from 'node:path';
import { request } from 'node:http';
import { gunzipSync } from 'node:zlib';
import { serveAdminAsset } from '../tools/local-admin-assets.mjs';
const root = await mkdtemp(resolve('.tooling/admin-assets-'));
await mkdir(resolve(root,'vendor'));
await writeFile(resolve(root,'vendor/test.css'),'.card{color:burgundy}\n'.repeat(300));
await writeFile(resolve(root,'vendor/private.php'),'<?php secret();');
await writeFile(resolve(root,'outside.css'),'private');
const server=createServer(async(req,res)=>{
 if(await serveAdminAsset(req,res,root))return;
 res.writeHead(404,{'Cache-Control':'no-store'});res.end();
});
await new Promise(ok=>server.listen(0,'127.0.0.1',ok));
const port=server.address().port;
const get=(path,headers={},method='GET')=>new Promise((ok,fail)=>{
 const req=request({host:'127.0.0.1',port,path,headers,method},res=>{
  const chunks=[];res.on('data',v=>chunks.push(v));res.on('end',()=>ok({status:res.statusCode,headers:res.headers,body:Buffer.concat(chunks)}));
 });req.on('error',fail);req.end();
});
const checks=[];
try {
 const compressed=await get('/vendor/test.css',{'Accept-Encoding':'gzip'});
 assert.equal(compressed.status,200);assert.equal(compressed.headers['content-encoding'],'gzip');assert.match(gunzipSync(compressed.body).toString(),/burgundy/);
 checks.push('Static CSS is gzip-compressed and decodes correctly');
 const raw=await get('/vendor/test.css',{'Accept-Encoding':'gzip;q=0, *;q=1'});
 assert.equal(raw.headers['content-encoding'],undefined);assert(raw.body.length>compressed.body.length);checks.push('Explicit gzip q=0 honored');
 const cached=await get('/vendor/test.css',{'If-None-Match':compressed.headers.etag});
 assert.equal(cached.status,304);assert.equal(cached.body.length,0);checks.push('Conditional requests return bodyless 304');
 const head=await get('/vendor/test.css',{},'HEAD');assert.equal(head.status,200);assert.equal(head.body.length,0);checks.push('HEAD response has no body');
 await writeFile(resolve(root,'vendor/test.css'),'.changed{color:tan}\n'.repeat(350));
 const changed=await get('/vendor/test.css',{'If-None-Match':compressed.headers.etag});
 assert.equal(changed.status,200);assert.notEqual(changed.headers.etag,compressed.headers.etag);checks.push('Changed files invalidate memory cache and ETag');
 for(const path of ['/admin','/api/hotel/rooms','/vendor/private.php','/vendor/%2e%2e%2foutside.css']) {
  const result=await get(path);assert.equal(result.status,404);assert.equal(result.headers['cache-control'],'no-store');checks.push(`Not served or cached: ${path}`);
 }
 console.log(JSON.stringify({passed:true,checks}));
} finally {
 await new Promise(ok=>server.close(ok));
 // mkdtemp creates this exact test-owned directory below .tooling.
 await rm(root,{recursive:true,force:true});
}
