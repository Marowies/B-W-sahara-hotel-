from pathlib import Path

root=Path(__file__).resolve().parents[1]/'src'
app=root/'app.js'
s=app.read_text(encoding='utf-8')
start=s.index('const stage=document.getElementById(\'model-stage\');const loadWorld=')
end=s.index('// The immersive view reuses',start)
s=s[:start]+'''const stage=document.getElementById('model-stage');
const startWorld=()=>window.hotel3D.load().then(()=>document.dispatchEvent(new CustomEvent('timechange',{detail:hero.dataset.time}))).catch(()=>{});
const loadWorld=new IntersectionObserver(entries=>{if(entries.some(e=>e.isIntersecting)){loadWorld.disconnect();startWorld();}},{rootMargin:navigator.connection?.saveData?'0px':'180px'});loadWorld.observe(stage);
'''+s[end:]
s=s.replace("document.getElementById('open-immersive').onclick=()=>{", "document.getElementById('open-immersive').onclick=()=>{startWorld();")
app.write_text(s,encoding='utf-8')

p=root/'scene.js'
s=p.read_text(encoding='utf-8')
s=s.replace("const stage=document.getElementById('model-stage');", "let worldInstance;\nconst stage=document.getElementById('model-stage');")
s=s.replace('export function createWorld(){', "export function createWorld(){\n if(worldInstance)return worldInstance;\n let frames=0,raf=0,contextLost=false,disposed=false;\n const stats=window.hotelPerformance;\n const invalidate=()=>{if(!raf&&!disposed&&!contextLost&&visible&&!document.hidden)raf=requestAnimationFrame(frame)};\n const stop=()=>{cancelAnimationFrame(raf);raf=0};")
s=s.replace("stage.querySelector('.model-controls').hidden=true;return;", "return;")
s=s.replace("renderer.setPixelRatio(Math.min(devicePixelRatio,1.75));", "stats.renderersCreated++;renderer.setPixelRatio(Math.min(devicePixelRatio,matchMedia('(max-width: 760px)').matches?1.25:1.75));")
s=s.replace('const homeDistance=()=>Math.max(11,11/(stage.clientWidth/stage.clientHeight));', 'const homeDistance=()=>Math.max(11,11/Math.max(.35,(stage.clientWidth||800)/(stage.clientHeight||600)));')
s=s.replace('const model=buildReferenceDome();', 'const model=buildReferenceDome();stats.modelBuilds++;')
begin=s.index(' const pmrem=new T.PMREMGenerator')
finish=s.index(' const shadow=',begin)
s=s[:begin]+''' let environmentSource,environmentTarget;
 function refreshEnvironment(){if(!environmentSource||contextLost||disposed)return;const pmrem=new T.PMREMGenerator(renderer);try{environmentTarget?.dispose();environmentTarget=pmrem.fromEquirectangular(environmentSource);scene.environment=environmentTarget.texture;scene.environmentIntensity=.9;}finally{pmrem.dispose();}invalidate();}
 new T.TextureLoader().load('assets/desert.jpg',texture=>{if(disposed){texture.dispose();return;}environmentSource=texture;texture.mapping=T.EquirectangularReflectionMapping;texture.colorSpace=T.SRGBColorSpace;refreshEnvironment();},undefined,()=>invalidate());
'''+s[finish:]
s=s.replace('sunlight.shadow.mapSize.set(1024,1024);', "const shadowSize=matchMedia('(max-width: 760px)').matches?512:1024;sunlight.shadow.mapSize.set(shadowSize,shadowSize);")
s=s.replace('renderer.setSize(w,h);camera.aspect=w/h;', 'if(!w||!h)return;renderer.setSize(w,h);camera.aspect=w/h;')
s=s.replace('camera.updateProjectionMatrix();};new ResizeObserver(resize).observe(stage);resize();stage.classList.add(\'ready\');', "camera.updateProjectionMatrix();invalidate();};const sizeObserver=new ResizeObserver(resize);sizeObserver.observe(stage);resize();stage.classList.add('ready');stage.querySelector('.model-fallback').style.opacity='';")
begin=s.index(' const visibility=new IntersectionObserver')
end=s.index('\n let prev=0;function frame',begin)
s=s[:begin]+''' const visibility=new IntersectionObserver(es=>{visible=es[0].isIntersecting;if(visible)invalidate();else stop();},{rootMargin:'0px'});visibility.observe(stage);
 document.addEventListener('motionchange',()=>{if(window.motionPaused)setRotate(false);invalidate();});
 document.addEventListener('timechange',e=>{night=e.detail==='night';section.dataset.night=String(night);document.getElementById('immersive')?.setAttribute('data-night',String(night));invalidate();});
 document.addEventListener('visibilitychange',()=>{if(document.hidden)stop();else invalidate();});
 document.addEventListener('hotelroutechange',e=>{if(e.detail.page!=='home'){visible=false;stop();}else{resize();invalidate();}});
 window.addEventListener('scroll',invalidate,{passive:true});
 stage.addEventListener('pointermove',invalidate,{passive:true});
 stage.addEventListener('keydown',invalidate);
 document.addEventListener('click',e=>{if(stage.contains(e.target)||e.target.closest('[data-view],[data-time],[data-viewer-time],[data-layout],#open-immersive,.close-immersive'))invalidate();});
'''+s[end:]
s=s.replace('function frame(now){requestAnimationFrame(frame);if(!visible||document.hidden){prev=now;return}', 'function frame(now){raf=0;if(!visible||document.hidden||contextLost||disposed){prev=now;return}')
s=s.replace('camera.lookAt(target);renderer.render(scene,camera)}requestAnimationFrame(frame);', '''camera.lookAt(target);renderer.render(scene,camera);frames++;
 const unsettled=Math.abs(targetAngle-angle)+Math.abs(targetElevation-elevation)+Math.abs(targetDistance-distance)+Math.abs(roofTarget-roofLift)+Math.abs(windowsTarget-windowsAmount)+Math.abs((night?.65:1.8)-ambient.intensity)+Math.abs((night?.65:2.5)-sunlight.intensity)+Math.abs((night?.35:.85)-fill.intensity)+Math.abs((night?6:2.6)-interiorLight.intensity)+Math.abs((night?.9:0)-starsMat.opacity)+Math.abs((night?5:-5)-sunlight.position.x)>.003;
 if((rotate&&!window.motionPaused)||dragging||unsettled)invalidate();
 }''')
begin=s.index(" renderer.domElement.addEventListener('webglcontextlost'")
s=s[:begin]+''' renderer.domElement.addEventListener('webglcontextlost',e=>{e.preventDefault();contextLost=true;stop();stage.classList.remove('ready');stage.querySelector('.model-loading').textContent='3D is paused while your device restores graphics. Hotel photographs remain available.';});
 renderer.domElement.addEventListener('webglcontextrestored',()=>{contextLost=false;refreshEnvironment();renderer.shadowMap.needsUpdate=true;stage.classList.add('ready');resize();invalidate();});
 function dispose(){if(disposed)return;disposed=true;stop();visibility.disconnect();sizeObserver.disconnect();const geometries=new Set(),materials=new Set(),textures=new Set();scene.traverse(o=>{if(o.geometry)geometries.add(o.geometry);for(const m of [o.material].flat().filter(Boolean)){materials.add(m);for(const v of Object.values(m))if(v?.isTexture)textures.add(v);}});geometries.forEach(g=>g.dispose());textures.forEach(t=>t.dispose());materials.forEach(m=>m.dispose());environmentTarget?.dispose();environmentSource?.dispose();renderer.dispose();}
 addEventListener('pagehide',e=>{if(!e.persisted)dispose();});
 worldInstance={invalidate,dispose,getStats:()=>({frames,visible,contextLost,drawCalls:renderer.info.render.calls,triangles:renderer.info.render.triangles,geometries:renderer.info.memory.geometries,textures:renderer.info.memory.textures})};
 invalidate();return worldInstance;
}
'''
p.write_text(s,encoding='utf-8')
print('Prepared singleton lazy loader and demand-driven renderer.')
