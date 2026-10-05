from pathlib import Path
p=Path(__file__).resolve().parents[1]/'src/dome-model.js'
s=p.read_text(encoding='utf-8')
s=s.replace('2.32,.86','2.12,.86').replace('2.32,1.55','2.12,1.55').replace('2.32,.17','2.12,.17').replace('2.35,.90','2.15,.90')
s=s.replace('containGeometry(fixtures);containGeometry(entry);','containGeometry(fixtures);')
s=s.replace('softBox(width-.03,.04,.80,mat.blanket,x,.635,.87,parent);','const cover=softBox(width-.03,.04,.80,mat.blanket,x,.635,.87,parent);cover.geometry.computeBoundingBox();const coverTop=cover.position.y+cover.geometry.boundingBox.max.y;')
start=s.index(' // A softly folded throw,')
end=s.index('\n }\n bed(king',start)
s=s[:start]+''' // The throw rests directly on the burgundy cover. Its tiny folds follow the
 // support surface; in-plane rotation never tilts it up off the mattress.
 const cloth=new T.Group();cloth.name='supported-throw';cloth.position.set(x,coverTop+.0015,.88);cloth.userData.supportY=coverTop;parent.add(cloth);
 const g=new T.PlaneGeometry(width*.80,.62,24,12),pp=g.attributes.position,a=.04;
 for(let i=0;i<pp.count;i++){const u=pp.getX(i),v=pp.getY(i);pp.setXYZ(i,u*Math.cos(a)-v*Math.sin(a),.0025*(.5+.5*Math.sin(u*12)*Math.cos(v*17)),u*Math.sin(a)+v*Math.cos(a));}
 g.computeVertexNormals();mesh(g,new T.MeshStandardMaterial({color:0xb4a087,side:T.DoubleSide,roughness:1,bumpMap:mat.linen.bumpMap,bumpScale:.006}),0,0,0,cloth);
''' +s[end:]
start=s.index(' for(const side of [-1,1]){box(.49,.4,.48')
end=s.index('\n box(2.20,.012',start)
s=s[:start]+''' const lampMetal=new T.MeshStandardMaterial({color:0x806449,metalness:.65,roughness:.35});
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
''' +s[end:]
p.write_text(s,encoding='utf-8')
