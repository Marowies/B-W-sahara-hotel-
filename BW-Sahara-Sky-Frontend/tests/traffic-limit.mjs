import {test} from 'node:test';
import assert from 'node:assert/strict';
import {createTrafficLimit} from '../tools/traffic-limit.mjs';
test('repeated documents are limited per client and recover after the window',()=>{
 let time=0;const check=createTrafficLimit({limit:2,windowMs:60000,now:()=>time});
 assert.equal(check('client-a').allowed,true);assert.equal(check('client-a').allowed,true);
 assert.deepEqual(check('client-a'),{allowed:false,retryAfter:60});assert.equal(check('client-b').allowed,true);
 time=60000;assert.equal(check('client-a').allowed,true);
});
test('unique clients cannot grow limiter memory without a bound',()=>{
 let time=0;const check=createTrafficLimit({maxClients:2,now:()=>time});
 check('a');check('b');assert.equal(check('c').allowed,false);time=60000;assert.equal(check('c').allowed,true);
});
