import * as T from '../vendor/three.module.js';
import {batchStaticMeshes} from './batch-model.js';
// Geometry is rebuilt from the supplied reference views; no dimensional survey is implied.
export function buildReferenceDome(){
 const root=new T.Group(),envelope=new T.Group(),framework=new T.Group();root.add(envelope,framework);const radius=3.15,centerY=-.28,floorY=.15;
 const mat={frame:new T.MeshStandardMaterial({color:0x242323,roughness:.34,metalness:.65}),wood:new T.MeshStandardMaterial({color:0xb38c60,roughness:.8}),grain:new T.MeshStandardMaterial({color:0xa27a50,roughness:.9}),linen:new T.MeshStandardMaterial({color:0xf2ece2,roughness:1}),blanket:new T.MeshStandardMaterial({color:0xa38a72,roughness:1}),rug:new T.MeshStandardMaterial({color:0xe1d3bf,roughness:1}),stone:new T.MeshStandardMaterial({color:0x8d8b83,roughness:1}),bath:new T.MeshStandardMaterial({color:0x55554f,roughness:.8}),porcelain:new T.MeshStandardMaterial({color:0xf2f0e9,roughness:.24}),lamp:new T.MeshStandardMaterial({color:0xeee1c9,emissive:0xffc78b,emissiveIntensity:.25,roughness:.8}),glass:new T.MeshPhysicalMaterial({color:0x9d6946,transparent:true,opacity:.94,roughness:.28,metalness:.5,clearcoat:.6,clearcoatRoughness:.08,envMapIntensity:.65,side:T.DoubleSide,depthWrite:true}),shower:new T.MeshPhysicalMaterial({color:0xc7c9bf,transparent:true,opacity:.13,roughness:.05,side:T.DoubleSide,depthWrite:false})};
 const bronze=[0xa66f43,0xc08e59,0x74472e,0x925932,0xb38153,0x684533,0xd0a571].map(c=>new T.MeshPhysicalMaterial({color:c,roughness:.24,metalness:.58,clearcoat:1,clearcoatRoughness:.12,envMapIntensity:1.05,side:T.DoubleSide,transparent:true}));
 const mirror=[0x624b3f,0x3f3430,0xa37b57,0x8b6749,0x81918d].map(c=>new T.MeshPhysicalMaterial({color:c,roughness:.23,metalness:.65,clearcoat:1,clearcoatRoughness:.07,envMapIntensity:.7,side:T.DoubleSide,transparent:true,opacity:.98}));mat.frame.transparent=true;
 function mesh(g,m,x=0,y=0,z=0,parent=root){const o=new T.Mesh(g,m);o.position.set(x,y,z);o.castShadow=!m.transparent||m.opacity>.7;o.receiveShadow=true;parent.add(o);return o;}
 const shellFrame=mat.frame.clone();shellFrame.transparent=true;
 mat.linen.color.setHex(0xd3c2a4);mat.blanket.color.setHex(0x74334b);mat.rug.color.setHex(0xb6a387);mat.porcelain.color.setHex(0xd8c8ad);mat.wood.color.setHex(0xa47b50);
 const upholstery=new T.MeshStandardMaterial({color:0x667878,roughness:.94});
 // Fine woven and timber relief responds to light instead of flat solid fills.
 function relief(grain=false){const size=128,data=new Uint8Array(size*size*4);let seed=71;for(let y=0;y<size;y++)for(let x=0;x<size;x++){seed=(seed*16807)%2147483647;const noise=(seed%100)/100;const value=grain?128+Math.sin(x*.48+Math.sin(y*.075)*1.5)*26+noise*12:130+noise*28+((x+y)%2)*8;const i=(y*size+x)*4;data[i]=data[i+1]=data[i+2]=value;data[i+3]=255;}const tex=new T.DataTexture(data,size,size,T.RGBAFormat);tex.wrapS=tex.wrapT=T.RepeatWrapping;tex.repeat.set(grain?3:8,grain?2:8);tex.needsUpdate=true;return tex;}
 mat.wood.bumpMap=relief(true);mat.wood.bumpScale=.017;mat.linen.bumpMap=relief();mat.linen.bumpScale=.012;upholstery.bumpMap=mat.linen.bumpMap;upholstery.bumpScale=.015;
 function box(w,h,d,m,x,y,z,parent=root){return mesh(new T.BoxGeometry(w,h,d),m,x,y,z,parent)}
 function softBox(w,h,d,m,x,y,z,parent=root,roundness=null){const r=roundness===null?Math.min(.045,h*.18,d*.15):Math.min(roundness,w*.2,h*.45,d*.45),shape=new T.Shape();shape.moveTo(-w/2+r,-h/2);shape.lineTo(w/2-r,-h/2);shape.quadraticCurveTo(w/2,-h/2,w/2,-h/2+r);shape.lineTo(w/2,h/2-r);shape.quadraticCurveTo(w/2,h/2,w/2-r,h/2);shape.lineTo(-w/2+r,h/2);shape.quadraticCurveTo(-w/2,h/2,-w/2,h/2-r);shape.lineTo(-w/2,-h/2+r);shape.quadraticCurveTo(-w/2,-h/2,-w/2+r,-h/2);const g=new T.ExtrudeGeometry(shape,{depth:d-r*2,bevelEnabled:true,bevelSegments:3,steps:1,bevelSize:r*.5,bevelThickness:r,curveSegments:6});g.center();return mesh(g,m,x,y,z,parent);}
 function rod(a,b,r=.017,parent=framework,m=null){m??=parent===framework?shellFrame:mat.frame;const d=b.clone().sub(a),o=mesh(new T.CylinderGeometry(r,r,d.length(),8),m,0,0,0,parent);o.position.copy(a).add(b).multiplyScalar(.5);o.quaternion.setFromUnitVectors(new T.Vector3(0,1,0),d.normalize());return o;}
 function polyGeometry(points){const c=points.reduce((v,p)=>v.add(p),new T.Vector3()).divideScalar(points.length),p=[];for(let i=0;i<points.length;i++)for(const v of [c,points[i],points[(i+1)%points.length]])p.push(v.x,v.y,v.z);const g=new T.BufferGeometry();g.setAttribute('position',new T.Float32BufferAttribute(p,3));g.computeVertexNormals();return g;}
 function clipFloor(points){const out=[];for(let i=0;i<points.length;i++){const a=points[i],b=points[(i+1)%points.length],inside=a.y>=floorY,next=b.y>=floorY;if(inside)out.push(a.clone());if(inside!==next){const t=(floorY-a.y)/(b.y-a.y);out.push(a.clone().lerp(b,t));}}return out;}
 // The dual of a subdivided icosahedron produces pentagonal and hexagonal cells.
 const source=new T.IcosahedronGeometry(radius,2),pos=source.attributes.position,vertices=[],lookup=new Map(),adjacency=[],centers=[];
 for(let i=0;i<pos.count;i+=3){const ids=[];let c=new T.Vector3();for(let j=0;j<3;j++){const v=new T.Vector3().fromBufferAttribute(pos,i+j),key=[v.x,v.y,v.z].map(n=>n.toFixed(5)).join(',');let id=lookup.get(key);if(id===undefined){id=vertices.length;lookup.set(key,id);vertices.push(v);adjacency.push([])}ids.push(id);c.add(v)}c.normalize().multiplyScalar(radius);const f=centers.length;centers.push(c);ids.forEach(id=>adjacency[id].push(f));}
 const panels=[];
 for(let i=0;i<vertices.length;i++){const n=vertices[i].clone().normalize(),up=Math.abs(n.y)>.95?new T.Vector3(1,0,0):new T.Vector3(0,1,0),u=new T.Vector3().crossVectors(up,n).normalize(),v=new T.Vector3().crossVectors(n,u).normalize();let poly=adjacency[i].map(j=>centers[j].clone());poly.sort((a,b)=>Math.atan2(a.dot(v),a.dot(u))-Math.atan2(b.dot(v),b.dot(u)));poly=clipFloor(poly.map(p=>p.add(new T.Vector3(0,centerY,0))));if(poly.length<3)continue;const c=poly.reduce((a,b)=>a.add(b),new T.Vector3()).divideScalar(poly.length);if(c.x>2.35&&Math.abs(c.z)<.77&&c.y<1.8)continue;panels.push({poly,c,n,sides:adjacency[i].length,glass:c.z>.18&&c.y<2.27});}
 // Select two outward-opening front windows, one on each side.
 const windows=[];for(const side of [-1,1]){const candidates=panels.filter(p=>p.glass&&p.c.x*side>1.05&&p.c.y>1.05&&p.c.y<2.15);candidates.sort((a,b)=>Math.abs(a.c.y-1.65)+Math.abs(Math.abs(a.c.x)-1.85)-Math.abs(b.c.y-1.65)-Math.abs(Math.abs(b.c.x)-1.85));if(candidates[0])windows.push(candidates[0])}
 const edgeKeys=new Set(),flaps=[];let glassCount=0;
 for(let i=0;i<panels.length;i++){const p=panels[i],material=p.glass?mirror[(i*7)%mirror.length]:bronze[(i*3)%bronze.length];if(p.glass)glassCount++;
  for(let j=0;j<p.poly.length;j++){const a=p.poly[j],b=p.poly[(j+1)%p.poly.length],key=[a.toArray().map(x=>x.toFixed(4)).join(','),b.toArray().map(x=>x.toFixed(4)).join(',')].sort().join('|');if(!edgeKeys.has(key)){edgeKeys.add(key);rod(a,b,.016)}}
  if(windows.includes(p)){let best=0,max=-Infinity;for(let j=0;j<p.poly.length;j++){const y=(p.poly[j].y+p.poly[(j+1)%p.poly.length].y)/2;if(y>max){max=y;best=j}}const a=p.poly[best],b=p.poly[(best+1)%p.poly.length],hinge=a.clone().add(b).multiplyScalar(.5),axis=b.clone().sub(a).normalize();const pivot=new T.Group();pivot.position.copy(hinge);envelope.add(pivot);const local=p.poly.map(v=>v.clone().sub(hinge));mesh(polyGeometry(local),mat.glass,0,0,0,pivot);for(let j=0;j<local.length;j++)rod(local[j],local[(j+1)%local.length],.031,pivot);const test=p.c.clone().sub(hinge),out=test.clone().applyAxisAngle(axis,.6).sub(test).dot(p.n);flaps.push({pivot,axis,sign:out>0?1:-1});}
  else mesh(polyGeometry(p.poly),material,0,0,0,envelope);
 }
 // Circular concrete plinth, timber deck and individually jointed floorboards.
 mesh(new T.CylinderGeometry(3.13,3.16,.13,96),mat.stone,0,.005,0);
 mesh(new T.CylinderGeometry(3.04,3.04,.035,96),mat.wood,0,.09,0);
 for(let x=-2.96;x<3;x+=.185){const length=2*Math.sqrt(Math.max(0,3.025**2-(Math.abs(x)+.089)**2));if(length>.1)box(.178,.013,length,Math.round(x*100)%3===0?mat.grain:mat.wood,x,.116,0);}
 // Recessed side door: the room has no rectangular annex outside the dome.
 const entry=new T.Group();entry.name='recessed-entry';root.add(entry);
 for(const z of [-.52,.52])box(.065,1.38,.065,mat.frame,2.12,.86,z,entry);
 box(.065,.065,1.10,mat.frame,2.12,1.55,0,entry);box(.065,.065,1.10,mat.frame,2.12,.17,0,entry);
 const doorGlass=mat.glass.clone();doorGlass.name='door-glazing';doorGlass.color.setHex(0x81918d);doorGlass.opacity=.34;doorGlass.metalness=.14;doorGlass.roughness=.12;doorGlass.depthWrite=false;
 box(.03,1.30,.97,doorGlass,2.12,.86,0,entry);box(.045,.22,.045,mat.frame,2.15,.90,-.35,entry);
 // Roof ventilation cap, attached to the removable shell.
 box(.58,.15,.39,mat.frame,0,2.895,-.25,envelope);const cap=box(.75,.035,.53,mat.frame,0,3.0,-.25,envelope);cap.rotation.x=-.13;
 // Gathered textile panels use a smooth indexed cloth grid and level hems.
 // They sit ahead of the lounge, well away from the side entrance.
 const interiorPlanes=panels.map(p=>({normal:p.n,limit:Math.min(...p.poly.map(v=>p.n.dot(v)))-.08}));
 const curtains=new T.Group();curtains.name='curtains';root.add(curtains);
 const curtainMaterial=new T.MeshStandardMaterial({color:0xbba586,side:T.DoubleSide,roughness:.98,bumpMap:mat.linen.bumpMap,bumpScale:.008});
 for(const side of [-1,1]){
  const positions=[],indices=[],columns=64,rows=16,top=1.14,bottom=.205;
  for(let row=0;row<=rows;row++)for(let col=0;col<=columns;col++){
   const u=col/columns,v=row/rows,a=side*(.64+u*.19);
   const r=2.40+Math.sin(u*Math.PI*10)*.024*(.65+.35*Math.sin(v*Math.PI))+.015*Math.sin(v*Math.PI);
   positions.push(Math.sin(a)*r,bottom+(top-bottom)*v,Math.cos(a)*r);
  }
  for(let row=0;row<rows;row++)for(let col=0;col<columns;col++){const a=row*(columns+1)+col,b=a+1,c=a+columns+1,d=c+1;indices.push(a,b,c,b,d,c);}
  const geometry=new T.BufferGeometry();geometry.setAttribute('position',new T.Float32BufferAttribute(positions,3));geometry.setIndex(indices);geometry.computeVertexNormals();
  const cloth=mesh(geometry,curtainMaterial,0,0,0,curtains);cloth.name='gathered-cloth';
  const railPoints=[];for(let i=0;i<=16;i++){const a=side*(.625+i/16*.22);railPoints.push(new T.Vector3(Math.sin(a)*2.40,top+.032,Math.cos(a)*2.40));}
  mesh(new T.TubeGeometry(new T.CatmullRomCurve3(railPoints),20,.012,6,false),mat.wood,0,0,0,curtains);
 }
 // The compact bathroom is set inwards so every wall has a level, finished top.
 const bath=new T.Group();bath.name='bathroom';root.add(bath);
 const fixtures=new T.Group();fixtures.name='bathroom-fixtures';bath.add(fixtures);
 const wallStone=new T.MeshStandardMaterial({color:0xa3937b,roughness:.88});
 const wallTrim=new T.MeshStandardMaterial({color:0x6f533a,roughness:.55});
 const wallTop=1.58,wallBase=.18;
 const bathroomWalls=new T.Group();bathroomWalls.name='bathroom-walls';bath.add(bathroomWalls);
 function finishedWall(ax,az,bx,bz,thickness){
  const length=Math.hypot(bx-ax,bz-az),wall=new T.Group();wall.position.set((ax+bx)/2,0,(az+bz)/2);wall.rotation.y=-Math.atan2(bz-az,bx-ax);bathroomWalls.add(wall);
  box(length,wallTop-wallBase,thickness,wallStone,0,(wallTop+wallBase)/2,0,wall);
  softBox(length,.035,thickness+.01,wallTrim,0,wallTop+.008,0,wall);
 }
 box(2.12,.035,1.10,mat.bath,0,.16,-1.49,fixtures);
 finishedWall(-1.08,-2.04,1.08,-2.04,.09);
 finishedWall(-1.08,-2.04,-1.08,-.97,.09);
 finishedWall(1.08,-.97,1.08,-2.04,.09);
 finishedWall(-1.08,-.97,.38,-.97,.10);
 box(.065,1.36,.10,wallTrim,.425,.86,-.97,fixtures);
 box(.065,1.36,.10,wallTrim,1.035,.86,-.97,fixtures);
 box(.675,.055,.11,wallTrim,.73,1.555,-.97,fixtures);
 for(let x=-1.02;x<.35;x+=.10)box(.027,1.31,.020,mat.wood,x,.85,-.912,fixtures);
 const toilet=mesh(new T.SphereGeometry(1,20,12),mat.porcelain,-.04,.47,-1.57,fixtures);toilet.scale.set(.22,.20,.28);
 softBox(.41,.44,.18,mat.porcelain,-.04,.57,-1.84,fixtures);mesh(new T.CylinderGeometry(.16,.19,.27,18),mat.porcelain,-.04,.30,-1.60,fixtures);
 const seat=mesh(new T.TorusGeometry(.185,.027,8,28),mat.porcelain,-.04,.65,-1.57,fixtures);seat.rotation.x=Math.PI/2;seat.scale.y=1.25;
 softBox(.45,.55,.39,mat.wood,-.70,.44,-1.30,fixtures);mesh(new T.CylinderGeometry(.21,.18,.09,24),mat.porcelain,-.70,.77,-1.30,fixtures);
 rod(new T.Vector3(-.70,.78,-1.46),new T.Vector3(-.70,.94,-1.46),.014,fixtures);rod(new T.Vector3(-.70,.94,-1.46),new T.Vector3(-.70,.94,-1.35),.014,fixtures);
 box(.65,.04,.73,mat.stone,.64,.21,-1.49,fixtures);box(.022,1.08,.75,mat.shower,.28,.78,-1.49,fixtures);
 rod(new T.Vector3(.28,.23,-1.105),new T.Vector3(.28,1.32,-1.105),.012,fixtures);
 rod(new T.Vector3(.83,.60,-1.90),new T.Vector3(.83,1.37,-1.75),.013,fixtures);
 const showerHead=mesh(new T.CylinderGeometry(.09,.09,.022,20),mat.frame,.83,1.40,-1.70,fixtures);showerHead.rotation.x=.3;
 // Two inspectable furnishing configurations derived from the supplied top views.
 const king=new T.Group(),twin=new T.Group();root.add(king,twin);twin.visible=false;
 function bed(parent,x,width){softBox(width+.08,.20,2.05,mat.wood,x,.28,.35,parent);softBox(width,.22,2.01,mat.linen,x,.49,.35,parent);softBox(width+.13,.96,.10,mat.wood,x,.67,-.72,parent);const cover=softBox(width-.03,.04,.80,mat.blanket,x,.635,.87,parent);cover.geometry.computeBoundingBox();const coverTop=cover.position.y+cover.geometry.boundingBox.max.y;for(const dx of width>1.3?[-.38,.38]:[0]){const cushion=softBox(width>1.3?.65:.66,.16,.45,mat.linen,x+dx,.69,-.31,parent);cushion.rotation.y=dx*.13;const accent=softBox(width>1.3?.43:.44,.13,.28,mat.blanket,x+dx,.69,-.04,parent);accent.rotation.y=-dx*.1;}
 for(let sx=-width/2+.12;sx<width/2;sx+=.12)box(.025,.74,.018,mat.grain,x+sx,.67,-.655,parent);
 for(const sx of [-1,1])for(const sz of [-1,1])box(.075,.16,.075,mat.frame,x+sx*(width/2-.12),.17,.35+sz*.86,parent);
 // The throw rests directly on the burgundy cover. Its tiny folds follow the
 // support surface; in-plane rotation never tilts it up off the mattress.
 const cloth=new T.Group();cloth.name='supported-throw';cloth.position.set(x,coverTop+.0015,.88);cloth.userData.supportY=coverTop;parent.add(cloth);
 const g=new T.PlaneGeometry(width*.80,.62,24,12),pp=g.attributes.position,a=.04;
 for(let i=0;i<pp.count;i++){const u=pp.getX(i),v=pp.getY(i);pp.setXYZ(i,u*Math.cos(a)-v*Math.sin(a),.0025*(.5+.5*Math.sin(u*12)*Math.cos(v*17)),u*Math.sin(a)+v*Math.cos(a));}
 g.computeVertexNormals();mesh(g,new T.MeshStandardMaterial({color:0xb4a087,side:T.DoubleSide,roughness:1,bumpMap:mat.linen.bumpMap,bumpScale:.006}),0,0,0,cloth);

 }
 bed(king,0,1.68);bed(twin,-.63,.94);bed(twin,.63,.94);
 const lampMetal=new T.MeshStandardMaterial({color:0x806449,metalness:.65,roughness:.35});
 const lampShade=mat.lamp.clone();lampShade.side=T.DoubleSide;lampShade.color.setHex(0xc7b292);
 const bulbMaterial=new T.MeshStandardMaterial({color:0xf0d5a4,emissive:0xffca7c,emissiveIntensity:1,roughness:.25});
 function bedsideLamp(parent,x,tableTop,z){
  const lamp=new T.Group();lamp.name='bedside-lamp';lamp.position.set(x,tableTop,z);parent.add(lamp);
  mesh(new T.CylinderGeometry(.082,.092,.026,24),lampMetal,0,.014,0,lamp);
  mesh(new T.CylinderGeometry(.015,.019,.17,12),lampMetal,0,.11,0,lamp);
  mesh(new T.SphereGeometry(.041,16,10),bulbMaterial,0,.258,0,lamp);
  const shade=mesh(new T.CylinderGeometry(.085,.15,.21,32,1,true),lampShade,0,.295,0,lamp);shade.name='open-lampshade';
  for(const [r,y] of [[.085,.400],[.15,.190]]){const rim=mesh(new T.TorusGeometry(r,.0045,6,32),lampMetal,0,y,0,lamp);rim.rotation.x=Math.PI/2;}
  rod(new T.Vector3(-.07,.395,0),new T.Vector3(.07,.395,0),.0035,lamp,lampMetal);
 }
 for(const side of [-1,1]){box(.49,.4,.48,mat.wood,side*1.15,.33,-.43,king);bedsideLamp(king,side*1.15,.53,-.43);}
 box(.35,.42,.42,mat.wood,0,.34,-.30,twin);bedsideLamp(twin,0,.55,-.30);

 box(2.20,.012,2.48,mat.rug,0,.142,.37);
 // Lounge furniture has a coherent local frame: back behind the seat, arms at its sides.
 const lounge=new T.Group();lounge.name='lounge';root.add(lounge);
 for(const [z,angle] of [[-.03,Math.PI/2+.10],[1.06,Math.PI/2-.10]]){
  const chair=new T.Group();chair.name='lounge-chair';chair.position.set(-1.88,0,z);chair.rotation.y=angle;lounge.add(chair);
  softBox(.65,.09,.63,mat.wood,0,.40,0,chair);
  softBox(.58,.15,.56,upholstery,0,.49,.015,chair,.065);
  const back=softBox(.59,.48,.115,upholstery,0,.765,-.25,chair,.06);back.rotation.x=-.14;
  // Open timber supports replace the oversized solid panel behind the cushion.
  for(const side of [-1,1])rod(new T.Vector3(side*.265,.43,-.27),new T.Vector3(side*.265,.985,-.35),.020,chair,mat.wood);
  softBox(.59,.038,.045,mat.wood,0,.986,-.35,chair);
  for(const side of [-1,1]){
   for(const front of [-1,1])rod(new T.Vector3(side*.29,.16,front*.27),new T.Vector3(side*.235,.405,front*.22),.025,chair,mat.wood);
   rod(new T.Vector3(side*.32,.41,.22),new T.Vector3(side*.32,.65,.22),.022,chair,mat.wood);
   const curve=new T.CatmullRomCurve3([new T.Vector3(side*.32,.64,.27),new T.Vector3(side*.32,.68,.13),new T.Vector3(side*.32,.69,-.10),new T.Vector3(side*.32,.71,-.28)]);
   mesh(new T.TubeGeometry(curve,12,.030,6,false),mat.wood,0,0,0,chair);
  }
  const lumbar=softBox(.39,.18,.10,mat.blanket,0,.67,-.155,chair);lumbar.rotation.x=-.12;
 }
 mesh(new T.CylinderGeometry(.22,.22,.055,32),mat.wood,-1.49,.56,.515,lounge);
 mesh(new T.CylinderGeometry(.033,.065,.36,12),mat.frame,-1.49,.355,.515,lounge);
 mesh(new T.CylinderGeometry(.17,.18,.028,24),mat.frame,-1.49,.166,.515,lounge);
 const lampLight=new T.PointLight(0xffd4a1,10,8,2);lampLight.position.set(.1,1.7,.45);root.add(lampLight);
 // Contain small bathroom fixtures and the recessed entry, with interior clearance.
 // Geometry, rather than render-only clipping, keeps every inspected vertex inside.
 function containGeometry(group){root.updateMatrixWorld(true);group.traverse(o=>{if(!o.isMesh)return;const src=o.geometry.index?o.geometry.toNonIndexed():o.geometry.clone(),a=src.attributes.position,out=[],inverse=o.matrixWorld.clone().invert();for(let i=0;i<a.count;i+=3){let poly=[0,1,2].map(j=>new T.Vector3().fromBufferAttribute(a,i+j).applyMatrix4(o.matrixWorld));for(const plane of interiorPlanes){const next=[];for(let j=0;j<poly.length;j++){const v=poly[j],w=poly[(j+1)%poly.length],dv=plane.normal.dot(v)-plane.limit,dw=plane.normal.dot(w)-plane.limit;if(dv<=0)next.push(v);if((dv<=0)!==(dw<=0))next.push(v.clone().lerp(w,dv/(dv-dw)));}poly=next;if(poly.length<3)break;}for(let j=1;j<poly.length-1;j++)for(const v of [poly[0],poly[j],poly[j+1]]){const local=v.clone().applyMatrix4(inverse);out.push(local.x,local.y,local.z);}}const g=new T.BufferGeometry();g.setAttribute('position',new T.Float32BufferAttribute(out,3));g.computeVertexNormals();o.geometry.dispose();src.dispose();o.geometry=g;});}
 containGeometry(fixtures);
 root.userData.interiorFinish={wallTop:wallTop+.0255,curtainHem:[.205,1.14],bathroomRear:-2.04,chairCenters:[[-1.88,-.03],[-1.88,1.06]]};
 root.userData.interiorPlanes=interiorPlanes.map(p=>({normal:p.normal.toArray(),limit:p.limit}));
 let windowOpen=1;function update(cut,win=windowOpen){windowOpen=win;envelope.position.y=0;envelope.visible=cut<.985;framework.visible=cut<.985;shellFrame.opacity=1-cut;bronze.forEach(m=>m.opacity=1-cut);mat.frame.opacity=1;mat.glass.opacity=.94*(1-cut);mirror.forEach(m=>m.opacity=.98*(1-cut));flaps.forEach(f=>f.pivot.quaternion.setFromAxisAngle(f.axis,f.sign*.55*win));}
 function setLayout(layout){king.visible=layout==='king';twin.visible=layout==='twin'}
 update(0,1);Object.assign(root.userData,{panelCount:panels.length,glassCount,windowCount:flaps.length,pentagons:panels.filter(p=>p.sides===5).length,hexagons:panels.filter(p=>p.sides===6).length});
 batchStaticMeshes(root);
 return {root,envelope,king,twin,setLayout,update,light:lampLight,materials:mat,stats:root.userData};
}
