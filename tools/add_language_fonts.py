from pathlib import Path
from urllib.request import Request,urlopen
from urllib.parse import urlencode
import re,hashlib,json

root=Path(__file__).resolve().parents[1]
# Download public font families only; never transmit project-derived text.
# Any glyph subsetting is performed offline by optimize_fonts.py.
url='https://fonts.googleapis.com/css2?'+urlencode([('family','Noto Sans Arabic:wght@400..700'),('family','Noto Sans SC:wght@400..600'),('display','swap')])
css=urlopen(Request(url,headers={'User-Agent':'Mozilla/5.0 Chrome/130.0.0.0 Safari/537.36'}),timeout=40).read().decode()
out=root/'src'/'fonts'
count=size=0
for source in sorted(set(re.findall(r'https://fonts.gstatic.com/[^)\s]+',css))):
    name=hashlib.sha256(source.encode()).hexdigest()[:12]+'.woff2'
    data=urlopen(source,timeout=40).read()
    (out/name).write_bytes(data)
    css=css.replace(source,'/fonts/'+name);count+=1;size+=len(data)
p=out/'fonts.css';existing=p.read_text(encoding='utf-8')
if "font-family: 'Noto Sans Arabic'" not in existing:p.write_text(existing+'\n'+css,encoding='utf-8')
for name in ['notosansarabic','notosanssc']:
    (out/(name+'-OFL.txt')).write_bytes(urlopen('https://raw.githubusercontent.com/google/fonts/main/ofl/'+name+'/OFL.txt',timeout=25).read())
print(json.dumps({'language_font_files':count,'download_bytes':size}))
