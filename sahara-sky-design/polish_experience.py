from pathlib import Path
import re
root=Path(__file__).parent
dist=root/'dist'
for p in dist.rglob('*'):
    if p.suffix not in {'.html','.js','.css'} or p.name=='three.module.js':continue
    text=p.read_text(encoding='utf-8')
    text=text.replace(' ↗','').replace(' ↖','').replace('↗','').replace('↖','').replace(' → ',' · ')
    if p.suffix=='.html':
        text=re.sub(r'burgundy\.css\?[^"\s]+','burgundy.css?v=polished-7',text)
        text=text.replace('app.js?v=6','app.js?v=7').replace('approval.js?v=6','approval.js?v=7')
    text=text.replace('scene.js?v=6','scene.js?v=7').replace('dome-model.js?v=6','dome-model.js?v=7')
    p.write_text(text,encoding='utf-8')
scene=dist/'scene.js'
s=scene.read_text(encoding='utf-8')
s=s.replace('toneMappingExposure=.94','toneMappingExposure=.90')
s=s.replace('0x9f866c,2.7','0x9f866c,1.8').replace('0xffe6c8,3.4','0xffe6c8,2.5').replace('0xffffff,1.4','0xffeed7,.85')
s=s.replace('night?.65:2.7','night?.65:1.8').replace('night?.65:3.4','night?.65:2.5').replace('night?.35:1.4','night?.35:.85').replace('night?15:8','night?6:2.6')
scene.write_text(s,encoding='utf-8')
css=dist/'burgundy.css'
with css.open('a',encoding='utf-8') as f:f.write('''
/* Quiet detailing, generous image framing and arrow-free links. */
.text-link::after,[dir=rtl] .text-link::after{content:none;display:none}
.text-link{letter-spacing:0;border-bottom:1px solid #87543580;padding-bottom:7px;transition:color .2s,border-color .2s}
.text-link:hover{color:#742647;border-color:#742647}
.button{border-radius:6px;transition:background .2s,box-shadow .2s;box-shadow:none}
.button:hover{transform:none;box-shadow:0 8px 24px #35102018}
.hero-actions{gap:16px}.hero-actions .button{padding-inline:28px}
.room-showcase,.stay-card,.article-card{border-radius:16px;overflow:hidden;border-color:#a77c5970;box-shadow:0 14px 34px #3510200b}
.stay-card:hover{transform:translateY(-4px);box-shadow:0 20px 42px #35102014}
.card-photo img{transition:transform .7s}.card-photo:hover img{transform:scale(1.035)}
.card-tag{border-radius:4px;padding:9px 14px;letter-spacing:0}
.stay-card h3{margin-top:13px}.card-actions{padding-top:15px;border-top:1px solid #87543540}
.service{border-radius:12px;padding:34px;border-color:#a77c5970}
.service:hover{box-shadow:0 14px 34px #3510200b}
.booking-aside,.location-panel,.review-summary,.complete-state{border-radius:12px}
.split-story img,.intro-photo img,[dir=rtl] .split-story img,[dir=rtl] .intro-photo img{border-radius:16px;transform:none}
.intro-photo{transform:none}.experience-card{border-radius:16px}.experience-overlay{background:linear-gradient(0deg,#351020f2,#35102000 78%)}
.experience-wide>img,.gallery-item img,.detail-gallery img{border-radius:12px}
.gallery-item figcaption,.detail-gallery figcaption{padding-top:16px;font-size:14px;line-height:1.8;letter-spacing:0}
.gallery-grid{gap:32px 24px}.gallery-item:hover img{filter:brightness(.96)}
.photo-card{border-radius:10px;padding:12px}.photo-card img{border-radius:5px}.photo-card span{font-size:13px}
.footer-top{padding-block:76px;gap:48px;border-top:1px solid #d2ad8326}
.footer-top .footer-story{gap:15px}.footer-story p{max-width:300px;font-size:15px;line-height:1.95;margin-block:13px}
.footer-top h3{font-size:13px;font-weight:500;letter-spacing:.05em;color:var(--gold);margin-bottom:13px}
.footer-top a{font-size:14px;line-height:1.8}.footer-top>div{gap:13px}
.footer-top .socials{gap:8px;flex-wrap:wrap;margin-top:5px}
.footer-top .socials a{padding:7px 12px;border:1px solid #d2ad8355;border-radius:6px;color:var(--gold);font-size:13px;transition:background .2s,color .2s}
.footer-top .socials a:hover{background:var(--gold);color:var(--burgundy-dark)}
.footer-bottom{padding-block:25px;letter-spacing:0;font-size:12px;align-items:center}
.model-options>button,.layout-toggle,.model-controls,.scene-time,.chapter-list{border-radius:8px;overflow:hidden}
.model-controls{padding:4px;background:#351020ed;border-color:#d2ad8366;box-shadow:0 10px 35px #16080d35}
.model-controls button{padding:12px 15px;border-radius:4px}
.model-options>button{border-radius:8px}.layout-toggle button{padding:13px 17px}.model-options{gap:10px}
.immersive-header h3{font-size:30px}.immersive-tools button{border-radius:6px}.immersive-header .eyebrow{font-size:12px}
.approval-form input,.approval-form select,.approval-form textarea,.stay-strip input,.stay-strip select{border-radius:6px}
@media(max-width:600px){.footer-top{padding-block:48px;gap:32px 20px}.footer-bottom{padding-bottom:100px}.footer-top .socials a{padding:7px 11px}.hero-actions .button{padding-inline:18px}.service{padding:27px}.gallery-item figcaption,.detail-gallery figcaption{font-size:12px}.model-controls button{padding:10px 11px}.model-options{gap:5px}.layout-toggle button{padding:10px 12px}.immersive-header h3{font-size:22px}.immersive-tools{gap:5px}.close-immersive{margin-left:0}}
''')
print('Removed decorative arrows and polished shared layouts and model lighting.')
