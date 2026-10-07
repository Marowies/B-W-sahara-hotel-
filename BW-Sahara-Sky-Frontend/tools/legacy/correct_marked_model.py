from pathlib import Path

p=Path(__file__).resolve().parents[1]/'src/dome-model.js'
s=p.read_text(encoding='utf-8')
s=s.replace('function softBox(w,h,d,m,x,y,z,parent=root){const r=Math.min(.045,h*.18,d*.15)', 'function softBox(w,h,d,m,x,y,z,parent=root,roundness=null){const r=roundness===null?Math.min(.045,h*.18,d*.15):Math.min(roundness,w*.2,h*.45,d*.45)')
s=s.replace('3.025**2-x*x','3.025**2-(Math.abs(x)+.089)**2')
start=s.index(' // Drapes are gathered')
end=s.index(' const bath=new T.Group()',start)
s=s[:start]+''' // Gathered textile panels use a smooth indexed cloth grid and level hems.
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
''' +s[end:]
start=s.index(' function ceilingAt(')
end=s.index(' // Two inspectable furnishing',start)
s=s[:start]+''' const wallTop=1.58,wallBase=.18;
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
''' +s[end:]
s=s.replace("[[.08,Math.PI/2+.12],[1.20,Math.PI/2-.16]]", "[[-.03,Math.PI/2+.10],[1.06,Math.PI/2-.10]]")
s=s.replace('chair.position.set(-1.91,0,z)', 'chair.position.set(-1.88,0,z)')
s=s.replace('softBox(.58,.15,.56,upholstery,0,.49,.015,chair);', 'softBox(.58,.15,.56,upholstery,0,.49,.015,chair,.065);')
s=s.replace('const back=softBox(.60,.49,.14,upholstery,0,.77,-.25,chair);back.rotation.x=-.14;\n  const backFrame=softBox(.64,.48,.06,mat.wood,0,.76,-.34,chair);backFrame.rotation.x=-.14;', '''const back=softBox(.59,.48,.115,upholstery,0,.765,-.25,chair,.06);back.rotation.x=-.14;
  // Open timber supports replace the oversized solid panel behind the cushion.
  for(const side of [-1,1])rod(new T.Vector3(side*.265,.43,-.27),new T.Vector3(side*.265,.985,-.35),.020,chair,mat.wood);
  softBox(.59,.038,.045,mat.wood,0,.986,-.35,chair);''')
s=s.replace('-1.52,.56,.64','-1.49,.56,.515').replace('-1.52,.355,.64','-1.49,.355,.515').replace('-1.52,.166,.64','-1.49,.166,.515')
s=s.replace('containGeometry(fixtures);containGeometry(entry);containGeometry(curtains);','containGeometry(fixtures);containGeometry(entry);')
s=s.replace('root.userData.interiorPlanes=','root.userData.interiorFinish={wallTop:wallTop+.0255,curtainHem:[.205,1.14],bathroomRear:-2.04,chairCenters:[[-1.88,-.03],[-1.88,1.06]]};\n root.userData.interiorPlanes=')
p.write_text(s,encoding='utf-8')
