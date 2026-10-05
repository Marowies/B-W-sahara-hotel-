import {spawn} from 'node:child_process';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {createWriteStream} from 'node:fs';
import {mkdir,writeFile} from 'node:fs/promises';
import http from 'node:http';
import os from 'node:os';
import {performance} from 'node:perf_hooks';
const php=process.env.HOTEL_PHP_PATH,config=process.env.HOTEL_PERF_DB_CONFIG;
if(!php||!config)throw new Error('Set HOTEL_PHP_PATH and HOTEL_PERF_DB_CONFIG for a synthetic test database.');
const root=resolve(dirname(fileURLToPath(import.meta.url)),'../..');
const logs=process.env.HOTEL_LAB_LOG_DIR||resolve(os.tmpdir(),'hotel-service-lab');await mkdir(logs,{recursive:true});
const ports=[8891,8892,8893,8894],children=[],agents=ports.map(()=>new http.Agent({keepAlive:true,maxSockets:1}));
let cursor=0;
const server=http.createServer((req,res)=>{
 const index=cursor++%ports.length,start=performance.now();let queue=0;
 const backend=http.request({hostname:'127.0.0.1',port:ports[index],path:req.url,method:req.method,headers:req.headers,agent:agents[index]},reply=>{
  res.writeHead(reply.statusCode,{...reply.headers,'x-lab-queue-ms':queue.toFixed(2)});reply.pipe(res);
 });backend.on('socket',()=>{queue=performance.now()-start;});
 backend.on('error',()=>{if(!res.headersSent)res.writeHead(502);res.end(JSON.stringify({ok:false,error:'Local PHP worker unavailable'}));});
 res.on('close',()=>{if(!res.writableFinished)backend.destroy();});req.pipe(backend);
});
function stop(){server.close();agents.forEach(x=>x.destroy());children.forEach(x=>x.kill());setTimeout(()=>process.exit(),250).unref();}
process.on('SIGINT',stop);process.on('SIGTERM',stop);
for(const port of ports){
 const log=createWriteStream(resolve(logs,`php-${port}.log`));
 const options=process.env.HOTEL_PERF_OPCACHE==='1'?['-d','zend_extension=opcache','-d','opcache.enable_cli=1','-d','opcache.memory_consumption=128','-d','opcache.max_accelerated_files=20000']:[];
 const child=spawn(php,[...options,'-S',`127.0.0.1:${port}`,resolve(root,'tests/Performance/router.php')],{cwd:root,env:process.env,windowsHide:true,stdio:['ignore','pipe','pipe']});
 child.stdout.pipe(log);child.stderr.pipe(log);children.push(child);child.on('error',error=>{console.error(error.message);stop();});
}
await new Promise(resolve=>setTimeout(resolve,1000));
await new Promise(resolve=>server.listen(8790,'127.0.0.1',resolve));
await writeFile(resolve(logs,'workers.json'),JSON.stringify({workers:children.map(x=>x.pid),ports,proxyPid:process.pid},null,2));
console.log('Loopback service laboratory ready: 4 PHP workers at http://127.0.0.1:8790');
