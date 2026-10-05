"""Package the standalone frontend without tooling installs or backend data."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
import json

root = Path(__file__).resolve().parents[1]
target = root.parent / 'BW-Sahara-Sky-Frontend.zip'
allowed = {'src', 'dist', 'tools', 'tests', 'docs'}
top_files = {'README.md', 'package.json', 'package-lock.json', '.gitignore'}
with ZipFile(target, 'w', ZIP_DEFLATED, compresslevel=6) as archive:
    for path in sorted(root.rglob('*')):
        if not path.is_file():
            continue
        relative = path.relative_to(root)
        if '__pycache__' in relative.parts or path.suffix == '.pyc':
            continue
        if relative.parts[0] in allowed or str(relative) in top_files or relative.as_posix() in {'test-results/verification.json', 'test-results/refinements.json', 'test-results/model-angles.json'}:
            archive.write(path, Path(root.name) / relative)
with ZipFile(target) as archive:
    assert archive.testzip() is None
    count = len(archive.namelist())
print(json.dumps({'archive': str(target), 'bytes': target.stat().st_size, 'files': count}, ensure_ascii=True))
