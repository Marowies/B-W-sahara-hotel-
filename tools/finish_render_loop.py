from pathlib import Path
p=Path(__file__).resolve().parents[1]/'src'/'scene.js'
s=p.read_text(encoding='utf-8')
s=s.replace("ambient.intensity=T.MathUtils.lerp", "const lightEase=window.motionPaused?1:1-Math.exp(-dt*7),sunEase=window.motionPaused?1:1-Math.exp(-dt*6);ambient.intensity=T.MathUtils.lerp")
s=s.replace(',.04)',',lightEase)').replace(',.025)',',sunEase)')
p.write_text(s,encoding='utf-8')
