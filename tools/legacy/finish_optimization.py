from pathlib import Path
root=Path(__file__).resolve().parents[1]/'src'
p=root/'scene.js'
s=p.read_text(encoding='utf-8').replace('Math.min((now-prev)/1000,.05)','Math.min((now-prev)/1000,.1)').replace('window.motionPaused?1:.075','window.motionPaused?1:1-Math.exp(-dt*8)').replace('frames,visible,contextLost,drawCalls','frames,visible,contextLost,idle:!raf,batching:model.stats.batching,drawCalls')
p.write_text(s,encoding='utf-8')
p=root/'approval.js'
s=p.read_text(encoding='utf-8').replace("box.querySelector('img').src='assets/'+image","box.querySelector('img').src=window.hotelAssetUrl(image)")
s=s.replace("location.href=url('/booking/')","window.hotelNavigate(url('/booking/'))").replace("location.href=url('/booking/review/')","window.hotelNavigate(url('/booking/review/'))")
p.write_text(s,encoding='utf-8')
