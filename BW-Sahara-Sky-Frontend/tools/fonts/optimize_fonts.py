from pathlib import Path
import sys
sys.path.insert(0,str(Path(__file__).resolve().parents[2]/'.tooling'/'font-libs'))
from fontTools import subset

root=Path(__file__).resolve().parents[2]/'src'/'fonts'
before=after=0
ranges=[(0,0x24f),(0x600,0x6ff),(0x750,0x77f),(0x8a0,0x8ff),(0x2000,0x206f),(0x20a0,0x20cf),(0xfb50,0xfdff),(0xfe70,0xfeff)]
content=''.join(p.read_text(encoding='utf-8') for p in (root.parent).rglob('*') if p.suffix in ['.html','.js','.css'])
extra={ord(c) for c in content if 0x3000<=ord(c)<=0x30ff or 0x4e00<=ord(c)<=0x9fff or 0xff00<=ord(c)<=0xffef}
for path in root.glob('*.woff2'):
    before+=path.stat().st_size
    font=subset.load_font(str(path),subset.Options())
    engine=subset.Subsetter(options=subset.Options(layout_features=['*']))
    engine.populate(unicodes=[code for start,end in ranges for code in range(start,end+1)]+sorted(extra))
    engine.subset(font)
    font.flavor='woff2'
    font.save(str(path))
    after+=path.stat().st_size
css=root/'fonts.css'
css.write_text(css.read_text(encoding='utf-8').replace("format('truetype')","format('woff2')"),encoding='utf-8')
print(f'Fonts: {before} -> {after} bytes (Latin + Arabic, WOFF2).')
