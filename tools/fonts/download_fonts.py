from pathlib import Path
from urllib.request import Request,urlopen
import re,hashlib,json

out=Path(__file__).resolve().parents[2]/'src'/'fonts'
out.mkdir(exist_ok=True)
url='https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Manrope:wght@400;500;600;700&family=Tajawal:wght@400;500;700&display=swap'
css=urlopen(Request(url,headers={'User-Agent':'Mozilla/5.0 Chrome/130.0.0.0 Safari/537.36'}),timeout=30).read().decode()
sources=[]
for source in sorted(set(re.findall(r'https://fonts.gstatic.com/[^)\s]+',css))):
    name=hashlib.sha256(source.encode()).hexdigest()[:12]+'.woff2'
    data=urlopen(source,timeout=30).read()
    (out/name).write_bytes(data)
    css=css.replace(source,'/fonts/'+name)
    sources.append({'url':source,'file':name,'bytes':len(data)})
(out/'fonts.css').write_text(css,encoding='utf-8')
(out/'sources.json').write_text(json.dumps({'css_source':url,'fonts':sources},indent=2),encoding='utf-8')
print(json.dumps({'font_files':len(sources),'font_bytes':sum(x['bytes'] for x in sources)}))
