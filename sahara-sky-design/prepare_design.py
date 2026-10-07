from pathlib import Path
import json, re, urllib.request, concurrent.futures

root = Path(__file__).parent
dist = root / 'dist'
photos = {
 '1': ['507563099', '507562058', '507562070'],
 '2': ['507563735', '507563518', '507563302'],
 '3': ['532283914', '532283939', '532284046'],
 '4': ['532580793', '532580890', '532580898'],
 '5': ['588703878', '588703718', '588703908'],
 '6': ['892569674', '892569685', '892569677'],
 '7': ['892614173', '892614172', '892614845']
}
def download(item):
 rid, index, image = item
 url = f'https://bwsaharaskyhotel.com/storage/rooms/{rid}/{image}-850x460.jpg'
 file = dist / 'assets' / f'room-{rid}-{index}.jpg'
 if not file.exists():
  request = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
  with urllib.request.urlopen(request, timeout=40) as response: file.write_bytes(response.read())
 return {'room':rid,'file':file.name,'source':url}
with concurrent.futures.ThreadPoolExecutor(max_workers=5) as pool:
 sources = list(pool.map(download, [(rid,i,image) for rid,images in photos.items() for i,image in enumerate(images)]))
(root/'asset-sources.json').write_text(json.dumps(sources, ensure_ascii=False, indent=2),encoding='utf-8')

home = (dist/'index.html').read_text(encoding='utf-8')
if 'approval.js' not in home:
 home = home.replace('<meta charset="utf-8">','<meta charset="utf-8"><base href="/">')
 home = home.replace('</head>','<link rel="stylesheet" href="approval.css"></head>')
 home = home.replace('<body class="desert-edition">','<body class="desert-edition" data-page="home">')
 # Rooms before the optional long 3D sequence.
 room = re.search(r'<section class="rooms".*?</section>',home,re.S).group()
 home = home.replace(room,'')
 home = home.replace('<section class="architecture',room+'\n<section class="architecture')
 home = home.replace('<a class="hero-explore"','<div class="hero-actions"><button class="button" data-book data-i18n="availability">Check availability</button><a class="button outline" href="/rooms/" data-i18n="viewRooms">View the rooms</a></div><a class="hero-explore"')
 home = home.replace('<span class="hero-edition">','<span class="hero-caption" data-i18n="visualisation">Architectural visualisation</span><span class="hero-edition">')
 home = home.replace('</script></body>','</script><script src="approval.js"></script></body>')
 (dist/'index.html').write_text(home,encoding='utf-8')
head = home.split('</head>')[0]+'</head>'
pages = {'about-us':'about','rooms':'rooms','services':'services','experiences':'experiences','galleries':'gallery','blog':'blog','contact-us':'contact','faq':'faq','privacy':'privacy','term-and-conditions':'terms','login':'login','register':'register','forgot-password':'forgot','booking':'booking','booking/review':'review','booking/confirmation':'confirmation','design-review':'approval'}
slugs = ['bw-sahara-sky-hotel-deluxe-room-single','bw-sahara-sky-hotel-deluxe-room-double','standard-room-single','bw-sahara-sky-hotel-standard-room-double','bw-sahara-sky-hotel-standard-room-triple','bw-sahara-sky-hotel-superior-room-single','bw-sahara-sky-hotel-superior-room-double','copper-glass-domes']
for slug in slugs: pages['rooms/'+slug]='room'
for slug in ['a-desert-made-for-discovery','a-night-beneath-the-stars','the-art-of-a-slower-stay']: pages['blog/'+slug]='article'
for route, page in pages.items():
 directory = dist / route
 directory.mkdir(parents=True,exist_ok=True)
 shell=head+f'<body class="desert-edition subpage" data-page="{page}"><header class="nav"></header><main id="main"></main><footer></footer><script src="approval.js"></script></body></html>'
 (directory/'index.html').write_text(shell,encoding='utf-8')
(dist/'404.html').write_text(head+'<body class="subpage" data-page="404"><header class="nav"></header><main id="main"></main><footer></footer><script src="approval.js"></script></body></html>',encoding='utf-8')
print(f'Prepared {len(pages)+2} pages and {len(sources)} room photographs.')
app = dist/'app.js'
source = app.read_text(encoding='utf-8')
for old,new in [('room-large.jpg','room-2-0.jpg'),('superior.jpg','room-6-0.jpg'),('triple.jpg','room-5-0.jpg')]:
 source = source.replace("img:'"+old+"'","img:'"+new+"'")
app.write_text(source,encoding='utf-8')
for html in dist.rglob('*.html'):
 content = html.read_text(encoding='utf-8')
 if 'name="robots"' not in content: content=content.replace('<meta name="viewport"','<meta name="robots" content="noindex,nofollow"><meta name="viewport"')
 if 'burgundy.css' not in content: content=content.replace('</head>','<link rel="stylesheet" href="burgundy.css"></head>')
 content=content.replace('content="#412a1f"','content="#571c35"')
 html.write_text(content,encoding='utf-8')
