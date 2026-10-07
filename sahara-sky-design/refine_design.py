from pathlib import Path
import re

root=Path(__file__).parent
css=root/'dist/burgundy.css'
s=css.read_text(encoding='utf-8').split('/* Dune reference palette:')[0]
for a,b in {'#d4b18b':'#d2ad83','#cba27b':'#c59c73','#b98e6b':'#b78d67','#54372f':'#38242a','#674337':'#4c2b30','#694232':'#4c2b30','#784c36':'#65233b','#736571':'#38242a'}.items():
    s=s.replace(a,b)
s+='''
/* A restrained three-tone system: sandstone surfaces, burgundy, copper accents. */
:root{--sand:#d2ad83;--surface:#c59c73;--burgundy:#571c35;--burgundy-dark:#351020;--gold:#e5c194;--ink:#38242a;--body:#38242a;--muted:#38242a;--soft:#a77c59;--copper:#875435;--paper:#e5c194;--light:#e5c194;--cream:#d2ad83;--ivory:#d2ad83}
html{background:var(--burgundy-dark);accent-color:var(--burgundy)}
body{background:var(--sand);color:var(--ink)}
.reveal{opacity:1!important;transform:none!important}
.nav,.nav.scrolled{background:#4b162df7;border-bottom:1px solid #d2ad8340;color:var(--gold)}
.nav .brand,.nav #nav-links>a,.subpage .nav #nav-links>a,.nav .language-select,.nav .menu-toggle{color:var(--gold)}
.nav #nav-links>a:hover,.nav #nav-links>a[aria-current=page]{color:var(--gold);border-bottom-color:var(--gold)}
.nav .button{background:var(--gold);border-color:var(--gold);color:var(--burgundy-dark);font-size:14px;font-weight:600}
.brand span{font-size:14px}.brand small{color:var(--gold);font-size:10px}
.nav #nav-links>a,.language-select{font-size:14px}
.nav-actions>.motion-button{font-size:12px;color:var(--gold)}
.stay-strip,.intro,.experiences,.home-note{background:var(--sand);color:var(--ink)}
.rooms,.moments{background:#c29a72;color:var(--ink)}
.room-description,.room-showcase,.photo-card,.stay-card,.service,.article-card,.complete-state{background:var(--surface);color:var(--ink);border-color:#a77c59;box-shadow:none}
.booking-aside,.location-panel,.approval-banner,.review-summary{background:#c29a72;color:var(--ink);border-color:#a77c59}
.auth-panel,.mobile-book{background:var(--sand);color:var(--ink)}
.page-body,.section-title p,.intro-details p,.rooms-top p,.room-description p,.stay-card p,.service p,.article-card p,.home-note p,.moments-heading p,.room-specs,.room-tabs button,.room-description>.eyebrow{color:var(--ink)}
.eyebrow,.section-mark,.section-mark span:first-child,.intro-photo figcaption,.stay-card .card-index,.article-meta,.spec-list span,.gallery-item figcaption,.approval-form label,.stay-strip label{color:#4c2b30}
.intro h2 em,.rooms-top h2 em,.experience-heading h2 em,.moments-heading h2 em{color:var(--burgundy)}
.experience-heading .section-mark{color:#4c2b30}
.intro-seal{border-color:var(--burgundy);color:var(--burgundy)}
.filter-row button{background:transparent;color:var(--burgundy)}
.filter-row button[aria-pressed=true]{background:var(--burgundy);color:var(--gold)}
input,select,textarea,option{background-color:var(--surface);color:var(--ink);color-scheme:light}
.approval-form input,.approval-form select,.approval-form textarea,.form-grid input,.form-grid select{background:var(--sand);color:var(--ink);border:1px solid #875435}
input::placeholder,textarea::placeholder{color:#4c2b30;opacity:1}
.stay-strip input,.stay-strip select{background:#c59c73;color:var(--ink);border-color:#875435;padding-inline:8px;border-radius:3px}
.preview-note{color:#4c2b30!important}
.cinema-copy>.preview-note{color:var(--gold)!important}
.hero,.page-hero,.cinema-architecture,.architecture-shell,.final-call,footer,.footer-top,.footer-bottom{color:var(--gold)}
.hero h1,.page-hero h1,.cinema-copy h2,.horizon-copy h2,.final-copy h2,.experience-label strong{color:var(--gold)}
.hero h1 em,.cinema-copy h2 em,.horizon-copy h2 em,.final-copy h2 em,.experience-label strong em{color:#d2ad83}
.hero-description,.hero-explore,.hero-explore small,.hero-bottom,.hero-caption,.page-hero p,.cinema-copy>p:first-of-type,.footer-story p,.footer-top a,.footer-bottom{color:var(--gold)}
.hero .eyebrow,.page-hero .eyebrow,.cinema-copy .eyebrow,.horizon .eyebrow,.final-call .eyebrow{color:var(--gold)}
.hero-shade{background:linear-gradient(90deg,#351020f5 0%,#351020d9 27%,#35102075 54%,#35102000 82%)}
[dir=rtl] .hero-shade{background:linear-gradient(270deg,#351020f5 0%,#351020d9 27%,#35102075 54%,#35102000 82%)}
.button,.subpage .button,.room-description .button,.stay-strip .button,.card-actions .button,#booking .button{color:var(--gold);background:var(--burgundy);border-color:var(--burgundy);font-size:14px;font-weight:500}
.hero-actions .button,.final-copy .button,.cinema-copy>.button{background:var(--gold);border-color:var(--gold);color:var(--burgundy-dark)}
.hero-actions .button.outline{background:#35102060;border-color:var(--gold);color:var(--gold)}
.button:hover{color:var(--gold)!important}
.scene-time button,.chapter-list button,.model-options button,.model-controls button{font-size:13px;color:var(--gold)}
.scene-time button[aria-pressed=true],.model-controls button[aria-pressed=true],.model-options button[aria-pressed=true]{background:var(--gold);color:var(--burgundy-dark)}
.chapter-list button[aria-pressed=true]{background:#d2ad8312;color:var(--gold)}
#immersive{background:var(--burgundy-dark);color:var(--gold)}
.immersive-header,.immersive-disclaimer{color:var(--gold)}
.experience-heading .section-mark,.room-specs,.room-tabs button,.stay-strip label,.approval-form label,.text-link,.card-actions .text-link,.filter-row button,.footer-top a{font-size:14px}
.hero-content>.eyebrow,.eyebrow,.hero-explore,.hero-explore small,.preview-note,.card-tag,.card-index,.hero-bottom,.intro-photo figcaption,.footer-top h3,.footer-bottom,.gallery-item figcaption{font-size:12px}
.hero-description,.stay-card p,.article-card p,.service p,.intro-details p,.rooms-top p,.room-description p,.booking-aside p,.home-note p,.contact-details p,.reading-copy p,.policy-content p,details p{font-size:16px}
dialog{background:var(--sand);color:var(--ink)}
::selection{background:var(--burgundy);color:var(--gold)}
@media(max-width:900px){.nav #nav-links{background:#4b162d}.nav #nav-links>a{color:var(--gold);border-color:#d2ad8333}.nav #nav-links .mobile-motion{color:var(--gold)}.hero-shade{display:none}.brand span{font-size:12px}}
@media(max-width:600px){.brand span{font-size:11px}.nav .menu-toggle,.language-select{font-size:13px}.hero h1{font-size:52px}.hero-content>.eyebrow,.hero-explore,.hero-explore small,.hero-bottom .hero-palette,.cinema-copy .eyebrow,.cinema-copy>.preview-note{font-size:12px!important}.chapter-list button,.model-options button,.model-controls button,.scene-time button{font-size:12px}.stay-strip label,.room-specs,.approval-form label,.card-actions .text-link,.filter-row button{font-size:14px}.card-actions{flex-wrap:wrap}.architecture-shell,.motion-paused .architecture-shell{height:1200px}.model-stage,[dir=rtl] .model-stage{top:49%;height:47%}[dir=rtl] .hero h1{font-size:42px}}
'''
css.write_text(s,encoding='utf-8')
for p in (root/'dist').rglob('*.html'):
    html=p.read_text(encoding='utf-8')
    html=re.sub(r'burgundy\.css(?:\?[^"\s]*)?', 'burgundy.css?v=refined-6',html)
    html=html.replace('src="app.js"','src="app.js?v=6"')
    p.write_text(html,encoding='utf-8')
app=root/'dist/app.js'
app.write_text(app.read_text(encoding='utf-8').replace("import('./scene.js')","import('./scene.js?v=6')"),encoding='utf-8')
scene=root/'dist/scene.js'
scene.write_text(scene.read_text(encoding='utf-8').replace("from './dome-model.js'","from './dome-model.js?v=6'"),encoding='utf-8')
print('Refined palette, legibility and model cache version.')
