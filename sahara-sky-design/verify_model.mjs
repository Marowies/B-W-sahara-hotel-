import {readFile} from 'node:fs/promises';
import assert from 'node:assert/strict';
import * as T from './dist/three.module.js';
const source=(await readFile(new URL('./dist/dome-model.js',import.meta.url),'utf8')).replace("'./three.module.js'",JSON.stringify(new URL('./dist/three.module.js',import.meta.url).href));
const {buildReferenceDome}=await import('data:text/javascript;base64,'+Buffer.from(source).toString('base64'));
const model=buildReferenceDome();
model.root.updateMatrixWorld(true);
let checked=0;
for(const name of ['bathroom','recessed-entry','curtains']){
 const group=model.root.getObjectByName(name);assert(group);
 group.traverse(o=>{if(!o.isMesh)return;const p=o.geometry.attributes.position;assert(p.count>0);for(let i=0;i<p.count;i++){const v=new T.Vector3().fromBufferAttribute(p,i).applyMatrix4(o.matrixWorld);for(const plane of model.stats.interiorPlanes){const n=new T.Vector3(...plane.normal);assert(n.dot(v)<=plane.limit+1e-5,`${name} vertex breaches dome envelope`);}checked++;}});
}
model.update(.75,0);assert.equal(model.envelope.position.y,0,'Cutaway must not lift panels away from the frame');
model.update(1,0);assert.equal(model.root.children[1].visible,false,'Interior view must remove the overhead wire frame');assert.equal(model.materials.frame.opacity,1,'Room fixtures stay opaque');
model.update(0,0);assert.equal(model.root.children[1].visible,true,'Exterior view restores the frame');
model.setLayout('twin');assert(model.twin.visible&&!model.king.visible);
model.setLayout('king');assert(model.king.visible&&!model.twin.visible);
console.log(`PASS: ${checked} bathroom/door/curtain vertices inside dome; shell alignment and both bed layouts.`);
