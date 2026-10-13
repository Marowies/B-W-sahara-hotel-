export function createTrafficLimit({limit=40,windowMs=60000,maxClients=10000,now=Date.now}={}){
 const clients=new Map();
 return ip=>{
  const time=now();
  if(clients.size>=maxClients){for(const [key,value] of clients)if(value.until<=time)clients.delete(key);}
  let entry=clients.get(ip);
  if(!entry||entry.until<=time){
   if(!entry&&clients.size>=maxClients)return {allowed:false,retryAfter:60};
   entry={count:0,until:time+windowMs};clients.set(ip,entry);
  }
  entry.count++;
  return {allowed:entry.count<=limit,retryAfter:Math.max(1,Math.ceil((entry.until-time)/1000))};
 };
}
