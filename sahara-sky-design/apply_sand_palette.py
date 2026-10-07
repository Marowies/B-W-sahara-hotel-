from pathlib import Path
import re

root = Path(__file__).parent
css = root / 'dist/burgundy.css'
s = css.read_text(encoding='utf-8')
palette = {
    '#fbf7f1': '#d4b18b', '#fffdfa': '#cba27b', '#f1e8e2': '#b98e6b',
    '#ffffff05': '#d4b18b0a', '#f3e9df': '#e0bd93', '#f3e8df': '#d4b18b',
    '#ecded1': '#d4b18b', '#ead7db': '#e0bd93', '#e4d9ce': '#af8769',
    '#736571': '#54372f', '#967d87': '#674337', '#866b75': '#674337',
    '#986f50': '#694232', '#9b6f50': '#694232', '#9d795b': '#784c36',
    '#8b7665': '#674337', '#8c705f': '#674337', '#a89891': '#674337',
    '#926950': '#674337', '#8f674f': '#674337', '#7d6267': '#674337',
    '#a07855': '#694232', '#b18b60': '#784c36', '#755361': '#54372f',
    '#cdbac1': '#cba27b', '#d7c3c6': '#d4b18b', '#c5aeb5': '#cba27b',
    '#c9aeb8': '#cba27b', '#bca3ac': '#cba27b', '#bfaab1': '#cba27b',
    '#e4d5c9': '#ac7f60', '#decdc0': '#a77e5f', '#dcc6b2': '#a77e5f',
    '#dac7ba': '#9e7053', '#ddcbbb': '#a77e5f', '#dbc5b2': '#a77e5f',
    '#dac4b0': '#a77e5f', '#d9c3ab': '#a77e5f', '#d7bc9f': '#a77e5f',
}
# Replace complete color prefixes, preserving the original alpha when present.
for old, new in sorted(palette.items(), key=lambda x: -len(x[0])):
    s = s.replace(old, new)
s = s.replace('burgundy, warm ivory, brushed gold.', 'burgundy, dune sand, copper and muted glass blue.')
s += '''
/* Dune reference palette: no white UI surfaces or white typography. */
:root{--sand:#d4b18b;--sand-deep:#b98e6b;--glass:#617476;--olive:#59543d;--copper:#a56f48;--earth-hover:#742647}
html{background:var(--burgundy-dark);accent-color:var(--burgundy)}
body{background:linear-gradient(120deg,#d4b18b,#c7a17c 65%,#b98e6b)}
.nav,.nav.scrolled{background:linear-gradient(110deg,#cba27bf5,#d4b18bf5 62%,#bfa184f5);border-bottom:2px solid #a56f4870}
.stay-strip{background:linear-gradient(110deg,#c7a17c,#d4b18b);border-inline:1px solid #a77e5f70}
.intro,.experiences,.home-note{background:linear-gradient(120deg,#d4b18b,#c7a17c)}
.rooms,.moments{background:linear-gradient(120deg,#b98e6b,#c29b76)}
.room-description,.room-showcase,.photo-card{background:#cba27b}
.room-description>.eyebrow,.room-tabs button,.room-specs{color:#674337}
.stay-card,.service,.article-card,.complete-state{background:linear-gradient(135deg,#cba27b,#c39b75);border-color:#a77e5f;box-shadow:0 12px 32px #54372f0a}
.stay-card .card-index,.service>span,.article-meta{color:#694232}
.filter-row button{background:#cba27b70}
.filter-row button[aria-pressed=true]{background:var(--burgundy)}
.booking-aside,.location-panel,.approval-banner,.review-summary{background:linear-gradient(135deg,#b98e6b,#c7a17c)}
.auth-panel{background:linear-gradient(120deg,#c7a17c,#d4b18b)}
input,select,textarea,option{background-color:#cba27b;color:#491b2e;color-scheme:light}
input::placeholder,textarea::placeholder{color:#674337;opacity:1}
.approval-form input,.approval-form select,.approval-form textarea{background:#cba27b;border-color:#9e7053}
.stay-strip input,.stay-strip select{background:#cba27b80;border-radius:3px;padding-inline:8px}
.mobile-book{background:#c7a17cf7}
.hero-description,.page-hero p{color:#e0bd93}
.eyebrow,.section-mark{color:#694232}
.hero .eyebrow,.horizon .eyebrow,.final-call .eyebrow,.page-hero .eyebrow,.cinema-copy .eyebrow{color:#dcb78b}
.intro-seal{border-color:#617476;color:#491b2e;background:#61747615}
.material-key i{background:var(--glass);border-color:#8e9a90}
.gallery-controls button{border-color:#617476}
.section-mark span:first-child{color:#694232}
.hero-explore small,.hero-bottom,.hero-caption{color:#d4b18b}
dialog{background:linear-gradient(130deg,#d4b18b,#c7a17c)}
::selection{background:#571c35;color:#e0bd93}
'''
css.write_text(s, encoding='utf-8')
for html in (root / 'dist').rglob('*.html'):
    markup = html.read_text(encoding='utf-8')
    markup = re.sub(r'burgundy\.css(?:\?[^"\s]*)?', 'burgundy.css?v=sand-5', markup)
    html.write_text(markup, encoding='utf-8')
print('Updated desert and burgundy palette across all HTML pages.')
