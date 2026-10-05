import http from 'node:http';
import {performance} from 'node:perf_hooks';
import {writeFile} from 'node:fs/promises';
import os from 'node:os';

const target = new URL(process.env.HOTEL_LOAD_URL || 'http://127.0.0.1:8790');
if(target.hostname!=='127.0.0.1'||target.protocol!=='http:')throw new Error('Load testing is restricted to HTTP loopback.');
const duration=Number(process.env.HOTEL_LOAD_STAGE_MS||5000),timeout=Number(process.env.HOTEL_LOAD_TIMEOUT_MS||15000);
const routes=['/rooms','/rooms/1001','/availability','/quote','/rooms','/checkout','/rooms','/quote','/availability','/rooms'];
const agent=new http.Agent({keepAlive:true,maxSockets:1100});
const sleep=ms=>new Promise(resolve=>setTimeout(resolve,ms));
function request(path){return new Promise(resolve=>{
 const start=performance.now();const req=http.request(new URL(path,target),{method:path==='/checkout'?'POST':'GET',agent},res=>{
  let body='';res.on('data',chunk=>body+=chunk);res.on('end',()=>{let ok=false;try{const data=JSON.parse(body);ok=res.statusCode===200&&data.ok===true&&(path!=='/checkout'||Number(data.booking_id)>0);}catch{}
   resolve({path,ms:performance.now()-start,status:res.statusCode,ok,queries:Number(res.headers['x-lab-queries']||0),queueMs:Number(res.headers['x-lab-queue-ms']||0),serverTiming:res.headers['server-timing']||'',error:ok?undefined:body.slice(0,300)});
  });
 });req.setTimeout(timeout,()=>req.destroy(new Error('request timeout')));req.on('error',error=>resolve({path,ms:performance.now()-start,status:0,ok:false,error:error.message}));req.end();
});}
const percentile=(values,p)=>values.length?values[Math.min(values.length-1,Math.floor(values.length*p))]:null;
function summary(samples,seconds){const values=samples.map(x=>x.ms).sort((a,b)=>a-b),queues=samples.filter(x=>x.ok).map(x=>x.queueMs).sort((a,b)=>a-b),errors=samples.filter(x=>!x.ok);return {requests:samples.length,successfulRequests:samples.length-errors.length,errorCount:errors.length,throughputRps:Number((samples.length/seconds).toFixed(2)),successfulThroughputRps:Number(((samples.length-errors.length)/seconds).toFixed(2)),errorRatePercent:Number((100*errors.length/samples.length).toFixed(2)),p50Ms:Number(percentile(values,.5)?.toFixed(2)),p95Ms:Number(percentile(values,.95)?.toFixed(2)),p99Ms:Number(percentile(values,.99)?.toFixed(2)),queueP95Ms:Number(percentile(queues,.95)?.toFixed(2)),errors:errors.slice(0,5)};}
const results={scope:'Four local PHP service-lab workers; synthetic InnoDB schema and helpers; no CMS templates, external providers, admin or real customer data.',environment:{node:process.version,cpus:os.cpus().length,totalMemoryBytes:os.totalmem(),opcache:process.env.HOTEL_PERF_OPCACHE==='1'},baseline:[],stages:[]};
for(const path of [...new Set(routes)]){const samples=[];for(let i=0;i<5;i++)samples.push(await request(path));results.baseline.push({path,...summary(samples,samples.reduce((total,x)=>total+x.ms,0)/1000),queryCounts:samples.map(x=>x.queries),serverTiming:samples.map(x=>x.serverTiming)});}
if(results.baseline.some(x=>x.errorRatePercent>0)){await writeFile(process.env.HOTEL_LOAD_OUTPUT||'load-results.json',JSON.stringify(results,null,2));throw new Error('Baseline route failures; refusing invalid load experiment.');}
for(const users of [25,50,100,250,500,1000]){
 const start=performance.now(),deadline=start+duration,samples=[];
 await Promise.all(Array.from({length:users},async(_,user)=>{let round=0;await sleep(user%25);while(performance.now()<deadline){samples.push(await request(routes[(user+round++)%routes.length]));await sleep(100);}}));
 const elapsed=(performance.now()-start)/1000;const result={concurrentUsers:users,dispatchDurationMs:duration,elapsedIncludingDrainSeconds:Number(elapsed.toFixed(2)),...summary(samples,elapsed),routes:[...new Set(routes)].map(path=>({path,...summary(samples.filter(x=>x.path===path),elapsed)}))};
 results.stages.push(result);console.log(JSON.stringify({users,requests:result.requests,p95Ms:result.p95Ms,rps:result.throughputRps,errorRate:result.errorRatePercent}));
 await writeFile(process.env.HOTEL_LOAD_OUTPUT||'load-results.json',JSON.stringify(results,null,2));await sleep(1000);
}
agent.destroy();
