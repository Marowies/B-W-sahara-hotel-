from pathlib import Path

root = Path(__file__).resolve().parents[1]
p = root/'src/dome-model.js'
s = p.read_text(encoding='utf-8')
start=s.index(' // Bathroom module behind the beds')
end=s.index(' // Two inspectable furnishing',start)
s=s[:start]+''' // Finished bathroom enclosure: solid sloped walls, closed top surfaces and oak coping.
 // Height is designed inside the envelope, rather than cutting open boxes at its edge.
 const interiorPlanes=panels.map(p=>({normal:p.n,limit:Math.min(...p.poly.map(v=>p.n.dot(v)))-.08}));
 const bath=new T.Group();bath.name='bathroom';root.add(bath);
 const fixtures=new T.Group();fixtures.name='bathroom-fixtures';bath.add(fixtures);
 const wallStone=new T.MeshStandardMaterial({color:0xa3937b,roughness:.88});
 const wallTrim=new T.MeshStandardMaterial({color:0x6f533a,roughness:.55});
 function ceilingAt(x,z){let height=1.88;for(const p of interiorPlanes)if(p.normal.y>.001)height=Math.min(height,(p.limit-p.normal.x*x-p.normal.z*z)/p.normal.y-.045);return height;}
 function finishedWall(ax,az,bx,bz,thickness){
  const dx=bx-ax,dz=bz-az,length=Math.hypot(dx,dz),nx=-dz/length*thickness/2,nz=dx/length*thickness/2,positions=[];
  const segments=12,base=.18;
  const cross=t=>{const x=ax+dx*t,z=az+dz*t,h=Math.min(1.78,ceilingAt(x+nx,z+nz),ceilingAt(x-nx,z-nz));return [new T.Vector3(x+nx,base,z+nz),new T.Vector3(x-nx,base,z-nz),new T.Vector3(x+nx,h,z+nz),new T.Vector3(x-nx,h,z-nz)];};
  const quad=(a,b,c,d)=>{for(const v of [a,b,c,a,c,d])positions.push(v.x,v.y,v.z);};
  for(let i=0;i<segments;i++){const a=cross(i/segments),b=cross((i+1)/segments);quad(a[0],b[0],b[2],a[2]);quad(b[1],a[1],a[3],b[3]);quad(a[2],b[2],b[3],a[3]);quad(a[1],b[1],b[0],a[0]);if(i===0)quad(a[1],a[0],a[2],a[3]);if(i===segments-1)quad(b[0],b[1],b[3],b[2]);
   // Solid slim coping follows the deliberate slope, with no exposed hollow edges.
   const ta=a[2].clone().add(a[3]).multiplyScalar(.5),tb=b[2].clone().add(b[3]).multiplyScalar(.5);ta.y-=.028;tb.y-=.028;rod(ta,tb,.026,bath,wallTrim);
  }
  const geometry=new T.BufferGeometry();geometry.setAttribute('position',new T.Float32BufferAttribute(positions,3));geometry.computeVertexNormals();const wall=mesh(geometry,wallStone,0,0,0,bath);wall.name='finished-bath-wall';
 }
 box(2.30,.035,1.56,mat.bath,0,.16,-1.80,fixtures);
 finishedWall(-1.12,-2.49,1.12,-2.49,.09);
 finishedWall(-1.12,-2.49,-1.12,-1.02,.09);
 finishedWall(1.12,-1.02,1.12,-2.49,.09);
 // A clean, continuous privacy wall with a framed opening on the right.
 finishedWall(-1.12,-1.02,.39,-1.02,.10);
 box(.065,1.54,.09,wallTrim,.43,.95,-1.02,fixtures);
 box(.065,1.54,.09,wallTrim,1.065,.95,-1.02,fixtures);
 box(.70,.065,.10,wallTrim,.745,1.735,-1.02,fixtures);
 // Narrow oak battens add depth and a readable front instead of a plain cut slab.
 for(let x=-1.065;x<.37;x+=.10)box(.033,1.49,.025,mat.wood,x,.96,-.952,fixtures);
 const toilet=mesh(new T.SphereGeometry(1,20,12),mat.porcelain,-.05,.47,-2.02,fixtures);toilet.scale.set(.245,.20,.34);
 softBox(.43,.46,.20,mat.porcelain,-.05,.59,-2.32,fixtures);mesh(new T.CylinderGeometry(.17,.21,.27,18),mat.porcelain,-.05,.30,-2.04,fixtures);
 const seat=mesh(new T.TorusGeometry(.20,.028,8,28),mat.porcelain,-.05,.65,-2.02,fixtures);seat.rotation.x=Math.PI/2;seat.scale.y=1.35;
 softBox(.50,.55,.45,mat.wood,-.76,.44,-1.59,fixtures);mesh(new T.CylinderGeometry(.23,.19,.09,24),mat.porcelain,-.76,.77,-1.59,fixtures);
 rod(new T.Vector3(-.76,.78,-1.76),new T.Vector3(-.76,.94,-1.76),.014,fixtures);rod(new T.Vector3(-.76,.94,-1.76),new T.Vector3(-.76,.94,-1.65),.014,fixtures);
 box(.69,.04,.90,mat.stone,.69,.21,-1.95,fixtures);box(.022,1.26,.94,mat.shower,.30,.85,-1.96,fixtures);
 rod(new T.Vector3(.30,.23,-1.49),new T.Vector3(.30,1.48,-1.49),.012,fixtures);
 rod(new T.Vector3(.88,.60,-2.35),new T.Vector3(.88,1.32,-2.16),.013,fixtures);
 const showerHead=mesh(new T.CylinderGeometry(.10,.10,.022,20),mat.frame,.88,1.35,-2.10,fixtures);showerHead.rotation.x=.3;
''' +s[end:]
start=s.index(' // Two lounge chairs and a small table')
end=s.index(' const lampLight=',start)
s=s[:start]+''' // Lounge furniture has a coherent local frame: back behind the seat, arms at its sides.
 const lounge=new T.Group();lounge.name='lounge';root.add(lounge);
 for(const [z,angle] of [[.08,Math.PI/2+.12],[1.20,Math.PI/2-.16]]){
  const chair=new T.Group();chair.name='lounge-chair';chair.position.set(-1.91,0,z);chair.rotation.y=angle;lounge.add(chair);
  softBox(.65,.09,.63,mat.wood,0,.40,0,chair);
  softBox(.58,.15,.56,upholstery,0,.49,.015,chair);
  const back=softBox(.60,.49,.14,upholstery,0,.77,-.25,chair);back.rotation.x=-.14;
  const backFrame=softBox(.64,.48,.06,mat.wood,0,.76,-.34,chair);backFrame.rotation.x=-.14;
  for(const side of [-1,1]){
   for(const front of [-1,1])rod(new T.Vector3(side*.29,.16,front*.27),new T.Vector3(side*.235,.405,front*.22),.025,chair,mat.wood);
   rod(new T.Vector3(side*.32,.41,.22),new T.Vector3(side*.32,.65,.22),.022,chair,mat.wood);
   const curve=new T.CatmullRomCurve3([new T.Vector3(side*.32,.64,.27),new T.Vector3(side*.32,.68,.13),new T.Vector3(side*.32,.69,-.10),new T.Vector3(side*.32,.71,-.28)]);
   mesh(new T.TubeGeometry(curve,12,.030,6,false),mat.wood,0,0,0,chair);
  }
  const lumbar=softBox(.39,.18,.10,mat.blanket,0,.67,-.155,chair);lumbar.rotation.x=-.12;
 }
 mesh(new T.CylinderGeometry(.22,.22,.055,32),mat.wood,-1.52,.56,.64,lounge);
 mesh(new T.CylinderGeometry(.033,.065,.36,12),mat.frame,-1.52,.355,.64,lounge);
 mesh(new T.CylinderGeometry(.17,.18,.028,24),mat.frame,-1.52,.166,.64,lounge);
''' +s[end:]
s=s.replace(" const interiorPlanes=panels.map(p=>({normal:p.n,limit:Math.min(...p.poly.map(v=>p.n.dot(v)))-.08}));\n function containGeometry", " function containGeometry")
s=s.replace('containGeometry(bath);containGeometry(entry);containGeometry(curtains);','containGeometry(fixtures);containGeometry(entry);containGeometry(curtains);')
p.write_text(s,encoding='utf-8')

p=root/'src/approval.js';s=p.read_text(encoding='utf-8')
start=s.index(' const socials = ');end=s.index(" document.body.insertAdjacentHTML('afterbegin'",start)
s=s[:start]+''' const icons={
 instagram:'<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".7" fill="currentColor" stroke="none"/>',
 facebook:'<path fill="currentColor" stroke="none" d="M14 22v-9h3l.5-4H14V7c0-1.1.3-2 2-2h2V1.4A26 26 0 0 0 15 1c-3 0-5 1.8-5 5v3H7v4h3v9z"/>',
 tiktok:'<path d="M14 3v12a4.5 4.5 0 1 1-4-4.47M14 3c.6 3 2.6 5 6 5V5c-1.9-.2-3-1-3.5-2z"/>',
 whatsapp:'<path d="M20.5 11.6a8.5 8.5 0 0 1-12.7 7.5L3 20.5l1.4-4.8A8.5 8.5 0 1 1 20.5 11.6z"/><path d="M8 7.5c-.5.5-.6 1.5-.1 2.6 1 2.3 2.7 4 5 5 .9.4 1.9.4 2.5-.1l.7-1.2-2.7-1.3-.8.9c-1.4-.6-2.5-1.7-3.1-3.1l.8-.8L9 7z"/>',
 email:'<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>'
 };
 const channels=[['instagram','Instagram','https://www.instagram.com/bw_sahara_sky_hotel/'],['facebook','Facebook','https://www.facebook.com/Sahara.Starry.Sky.Camp'],['tiktok','TikTok','https://www.tiktok.com/@bwsaharaskyhotel'],['whatsapp','WhatsApp','https://wa.me/201098255777'],['email',t('Email','البريد الإلكتروني','电子邮件'),'mailto:info@bwsaharaskyhotel.com']];
 const socials=`<div class="socials icon-channels" aria-label="${t('Contact and social channels','التواصل والسوشيال','联系与社交渠道')}">${channels.map(([id,label,href])=>`<a class="channel-icon" href="${href}" aria-label="${label}" title="${label}" ${id==='email'?'':'target="_blank" rel="noopener noreferrer"'}><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">${icons[id]}</svg></a>`).join('')}</div>`;
 document.querySelector('footer').innerHTML=`<div class="footer-top"><div class="footer-story">${brand}<p>${t('A warm welcome between Bahariya Oasis and Farafra. Rooms, open horizons, and nights beneath the stars.','أهلًا بيك بين الواحات البحرية والفرافرة. غرف هادية، أفق مفتوح، وليالي تحت النجوم.','欢迎来到拜哈里耶绿洲与法拉夫拉之间。舒适客房、辽阔风景与璀璨星空。')}</p>${socials}</div><nav class="footer-links" aria-label="${t('Discover','اكتشف','探索')}"><h3>${t('Discover','اكتشف','探索')}</h3>${['rooms','experiences','services','gallery','about','blog'].map(k=>link(k)).join('')}</nav><nav class="footer-links" aria-label="${t('Plan your visit','خطط لزيارتك','规划旅程')}"><h3>${t('Plan your visit','خطط لزيارتك','规划旅程')}</h3>${['contact','faq','login','privacy','terms','approval'].map(k=>link(k)).join('')}</nav><div class="footer-contact"><h3>${t('Find us in the desert','قابلنا في الصحراء','在沙漠找到我们')}</h3><p>${t('Bahariya–Farafra Road<br>Egypt’s Western Desert','طريق الواحات البحرية – الفرافرة<br>الصحراء الغربية، مصر','拜哈里耶—法拉夫拉公路<br>埃及西部沙漠')}</p><a class="footer-phone" href="tel:+201098255777" dir="ltr">+20 109 825 5777</a><span class="footer-contact-note">${t('A conversation away.','خطوة من حكاية جديدة.','相距一场对话。')}</span></div></div><div class="footer-bottom"><span>© ${new Date().getFullYear()} B&W Sahara Sky</span><span>${labels.demo}</span><a href="https://bwsaharaskyhotel.com/" target="_blank" rel="noopener">${t('Current hotel website','موقع الفندق الحالي','酒店当前官网')}</a></div>`;
''' +s[end:]
p.write_text(s,encoding='utf-8')
