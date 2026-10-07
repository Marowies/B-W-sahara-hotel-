import {readFile} from 'node:fs/promises';
import assert from 'node:assert/strict';
const manifest=JSON.parse(await readFile(new URL('../dist/build-manifest.json',import.meta.url),'utf8'));
const T=await import(new URL('../dist/'+manifest.files['three.module.js'],import.meta.url));
const {buildReferenceDome}=await import(new URL('../dist/'+manifest.files['dome-model.js'],import.meta.url));
const model=buildReferenceDome();model.root.updateMatrixWorld(true);let vertices=0;
model.envelope.traverse(mesh=>{if(!mesh.isMesh)return;const p=mesh.geometry.attributes.position,ids=mesh.geometry.index;for(let i=0;i<(ids?ids.count:p.count);i+=3){const c=new T.Vector3();for(let j=0;j<3;j++)c.add(new T.Vector3().fromBufferAttribute(p,ids?ids.getX(i+j):i+j).applyMatrix4(mesh.matrixWorld));c.divideScalar(3);assert(!(c.x>1.8&&c.y<1.5999&&Math.abs(c.z)<.5699),'Shell must not cover the rectangular doorway');}});
for(const name of ['bathroom','curtains','lounge']){
 const group=model.root.getObjectByName(name);assert(group);
 group.traverse(mesh=>{if(!mesh.isMesh)return;const p=mesh.geometry.attributes.position;assert(p.count>0);for(let i=0;i<p.count;i++){
  const v=new T.Vector3().fromBufferAttribute(p,i).applyMatrix4(mesh.matrixWorld);
  for(const plane of model.stats.interiorPlanes)assert(new T.Vector3(...plane.normal).dot(v)<=plane.limit+1e-5,name+' breaches dome');
  vertices++;
 }});
}
const walls=model.root.getObjectByName('bathroom-walls');assert.equal(walls.children.length,4);
const tops=walls.children.map(w=>new T.Box3().setFromObject(w).max.y);
assert(Math.max(...tops)-Math.min(...tops)<1e-5,'Bathroom walls must have a consistent level top');
const curtainBounds=new T.Box3().setFromObject(model.root.getObjectByName('curtains'));
const loungeBounds=new T.Box3().setFromObject(model.root.getObjectByName('lounge'));
const entryBounds=new T.Box3().setFromObject(model.root.getObjectByName('recessed-entry'));
assert(curtainBounds.min.z-loungeBounds.max.z>.10,'Curtains must clear the chairs');
assert(curtainBounds.min.z-entryBounds.max.z>.50,'Curtains must clear the side door');
const headerCorners=new Set();model.root.getObjectByName('entrance-door-frame').traverse(o=>{if(!o.isMesh)return;const a=o.geometry.attributes.position;for(let i=0;i<a.count;i++){const v=new T.Vector3().fromBufferAttribute(a,i).applyMatrix4(o.matrixWorld);if(Math.abs(v.y-1.5825)<1e-5)headerCorners.add(v.x.toFixed(4)+','+v.z.toFixed(4));}});
assert.equal(headerCorners.size,4,'Door header must retain all four rectangular top corners');
assert(entryBounds.max.x>3.25&&entryBounds.max.x<3.4,'Reference entrance must project beyond the circular deck');
assert.equal(model.root.getObjectByName('entrance-returns').children.length,3,'Entrance must include solid jambs, roof and floor finishes after batching');
let throws=0,lamps=0;model.root.traverse(o=>{
 if(o.name==='supported-throw'){throws++;const b=new T.Box3().setFromObject(o),support=o.userData.supportY;assert(b.min.y>=support&&b.max.y-support<.005,'Throw must rest on its cover without a floating gap');}
 if(o.name==='bedside-lamp'){lamps++;assert(o.getObjectByName('open-lampshade'),'Lamp must include an open shade');const b=new T.Box3().setFromObject(o);assert(b.max.y-b.min.y>.37,'Lamp must include a base, stem and shade');}
});assert.equal(throws,3);assert.equal(lamps,3);
model.update(1,0);assert.equal(model.envelope.position.y,0);assert.equal(model.root.children[1].visible,false);assert.equal(model.materials.frame.opacity,1);
let doorOpacity;model.root.getObjectByName('recessed-entry').traverse(o=>{if(o.material?.name==='door-glazing')doorOpacity=o.material.opacity;});assert.equal(doorOpacity,.58,'Door glazing must remain visible in cutaway mode');
model.update(0,0);assert.equal(model.root.children[1].visible,true);
model.setLayout('twin');assert(model.twin.visible&&!model.king.visible);
model.setLayout('king');assert(model.king.visible&&!model.twin.visible);
assert(model.stats.batching.after<model.stats.batching.before);
console.log(JSON.stringify({status:'PASS',vertices,batching:model.stats.batching,shellAlignment:true,layouts:true,levelBathroomWalls:true,completeDoorHeader:true,supportedThrows:throws,completeLamps:lamps,curtainChairClearance:curtainBounds.min.z-loungeBounds.max.z,curtainDoorClearance:curtainBounds.min.z-entryBounds.max.z}));
