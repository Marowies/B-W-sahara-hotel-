from pathlib import Path
from html.parser import HTMLParser
from PIL import Image
import re, json

root=Path(__file__).parent
dist=root/'dist'
class References(HTMLParser):
 def __init__(self): super().__init__();self.references=[]
 def handle_starttag(self,tag,attrs):
  for key,value in attrs:
   if key in ['src','href'] and value: self.references.append((tag,value))
errors=[]
for html in dist.rglob('*.html'):
 parser=References();parser.feed(html.read_text(encoding='utf-8'))
 for tag,ref in parser.references:
  if ref.startswith(('http:','https:','mailto:','tel:','#','data:')): continue
  ref=ref.split('?')[0].split('#')[0]
  if not ref: continue
  target=dist/ref.lstrip('/')
  if target.is_dir(): target=target/'index.html'
  if not target.exists(): errors.append(f'{html.relative_to(dist)}: missing {ref}')
for image in (dist/'assets').iterdir():
 if image.suffix.lower() in ['.jpg','.png','.webp']:
  try:
   with Image.open(image) as check: check.verify()
  except Exception as e: errors.append(f'{image.name}: {e}')
scripts='\n'.join((dist/name).read_text(encoding='utf-8') for name in ['app.js','approval.js','scene.js'])
for ref in set(re.findall(r"(?:assets/|img:'|image:')([\w-]+\.(?:jpg|png))",scripts)):
 if not (dist/'assets'/ref).exists(): errors.append('Missing scripted image '+ref)
for route in ['rooms','services','experiences','about-us','galleries','blog','contact-us','faq','privacy','term-and-conditions','login','register','forgot-password','booking','booking/review','booking/confirmation','design-review']:
 if not (dist/route/'index.html').exists(): errors.append('Missing page '+route)
manifest=json.loads((root/'.openai/hosting.json').read_text(encoding='utf-8'))
assert manifest['project_id']=='appgprj_6abc406bf6c481919c93d510cccbe7b3'
if errors: raise SystemExit('\n'.join(errors))
print(f'PASS: {len(list(dist.rglob("*.html")))} HTML pages, local references, image files and Site manifest.')
