<?php

// Real Laravel routing with the frontend's route registry; no CMS, database or network.
// php tests/FrontendGate/run.php [vendor/autoload.php]
$root = dirname(__DIR__, 2);
require $argv[1] ?? $root . '/vendor/autoload.php';
// Fixture public/ folder: route-map.json built from the frontend registry, plus the 404 pages and one static page.
$pub = sys_get_temp_dir() . '/frontend-gate-' . getmypid();
@mkdir($pub . '/ar', 0777, true); @mkdir($pub . '/rooms', 0777, true);
file_put_contents($pub . '/404.html', '<html>EN404</html>'); file_put_contents($pub . '/ar/404.html', '<html>AR404</html>');
file_put_contents($pub . '/rooms/index.html', '<html>ROOMS</html>');
preg_match_all("~\['([^']+)','([^']+)','([^']+)','([^']+)'~", file_get_contents(dirname($root, 2) . '/BW-Sahara-Sky-Frontend/src/shared/locale.mjs'), $rows, PREG_SET_ORDER);
file_put_contents($pub . '/route-map.json', json_encode(['routes' => array_map(fn ($r) => ['id' => $r[1], 'en' => $r[2], 'ar' => $r[3], 'zh' => $r[4]], $rows)]));
require $root.'/app/Http/Middleware/StaticFrontendGate.php';
use Illuminate\Http\Request; use Symfony\Component\HttpFoundation\Response;
// The project's vendor has the full framework and its helpers; a minimal routing install gets small stand-ins.
$app = class_exists(Illuminate\Foundation\Application::class) ? new Illuminate\Foundation\Application($root) : new Illuminate\Container\Container;
Illuminate\Container\Container::setInstance($app);
$app->instance('config', new Illuminate\Config\Repository(['core'=>['base'=>['general'=>['admin_dir'=>'admin']]]]));
$setPublic = fn ($path) => method_exists($app, 'usePublicPath') ? $app->usePublicPath($path) : $app->instance('path.public', $path);
$setPublic($pub);
if (! function_exists('public_path')) { function public_path($p=''){return Illuminate\Container\Container::getInstance()['path.public'].'/'.ltrim($p,'/');} }
if (! function_exists('config')) { function config($k,$d=null){return Illuminate\Container\Container::getInstance()['config']->get($k,$d);} }
if (! function_exists('app')) { function app($a=null){$c=Illuminate\Container\Container::getInstance();return $a?$c[$a]:$c;} }
$app->bind(Illuminate\Routing\Contracts\CallableDispatcher::class, fn($c)=>new Illuminate\Routing\CallableDispatcher($c));
$router = new Illuminate\Routing\Router(new Illuminate\Events\Dispatcher($app), $app); $app->instance('router',$router);
$ok=fn()=>new Response('LARAVEL',200,['Content-Type'=>'text/html']);
$router->get('/', $ok)->name('public.index');
$router->get('{slug?}', $ok)->name('public.single')->where('slug','[^/]+');
$router->get('rooms/{slug}', $ok)->name('public.single');
$router->get('ar/{slug?}', $ok)->name('public.single')->where('slug','.*');
$router->get('search', $ok)->name('public.search');
$router->get('booking/{token}', fn($t)=>$t==='bad'?new Response('old theme 404',404,['Content-Type'=>'text/html']):new Response('BOOKINGFORM',200,['Content-Type'=>'text/html']))->name('public.booking.form');
$router->get('customer/overview', $ok)->name('customer.overview');
$router->get('admin/x', fn()=>new Response('ADMIN404',404,['Content-Type'=>'text/html']));
$router->get('api/hotel/rooms', fn()=>new Response('{}',404,['Content-Type'=>'application/json']));
$router->getRoutes()->refreshNameLookups();
$cases = [
 // [url, method, expected status, expected location or body fragment]
 ['/ar/rooms','GET',301,'/ar/ghoraf/'], ['/ar/about-us','GET',301,'/ar/aan-al-fondoq/'], ['/zh/contact-us','GET',301,'/zh/lianxi/'],
 ['/ar/faq','GET',301,'/ar/asela-shaea/'], ['/ar/experiences/','GET',301,'/ar/tajarob-al-sahraa/'], ['/zh/experiences','GET',301,'/zh/shamo-tiyan/'],
 ['/about','GET',301,'/about-us/'], ['/contact','GET',301,'/contact-us/'], ['/news','GET',301,'/blog/'], ['/news/some-old-post','GET',301,'/blog/'],
 ['/Rooms','GET',301,'/rooms/'], ['/ROOMS/','GET',301,'/rooms/'], ['/AR/GHORAF/','GET',301,'/ar/ghoraf/'],
 ['/rooms/b-w-sahara-sky-hotel-superior-room-double','GET',301,'/rooms/bw-sahara-sky-hotel-superior-room-double/'],
 ['/ar/rooms/b-w-sahara-sky-hotel-deluxe-room-single','GET',301,'/ar/ghoraf/deluxe-fardeya/'],
 ['/index.php','GET',301,'/'], ['/index.php/ar','GET',301,'/ar/'], ['/search?q=a','GET',301,'/?q=a'],
 ['/?lang=ar','GET',301,'/ar/'], ['/rooms/?lang=zh','GET',301,'/zh/kefang/'], ['/rooms/?lang=ar&x=1','GET',301,'/ar/ghoraf/?x=1'],
 ['/xyz','GET',404,'EN404'], ['/ar/xyz','GET',404,'AR404'], ['/zh/xyz','GET',404,'EN404'], ['/design-review/','GET',404,'EN404'],
 ['/booking/bad','GET',404,'EN404'], ['/booking/abc?lang=ar','GET',200,'BOOKINGFORM'],
 ['/customer/overview','GET',200,'LARAVEL'], ['/customer/overview?lang=ar','GET',200,'LARAVEL'],
 ['/admin/x','GET',404,'ADMIN404'], ['/api/hotel/rooms','GET',404,'{}'],
 ['/ar/rooms','POST',599,'MethodNotAllowed'], ['/xyz','HEAD',404,''],
 ['/rooms/','GET',200,'ROOMS'],
];
$fail=0;
foreach ($cases as [$url,$m,$st,$want]) {
  $req = Request::create($url,$m);
  $gate = new App\Http\Middleware\StaticFrontendGate;
  try { $res = $gate->handle($req, fn($r)=>$router->dispatch($r)); }
  catch (Throwable $e) { $res = new Response('EXC '.get_class($e),599); }
  $loc = $res->headers->get('Location'); $got = $res->getStatusCode();
  $pass = $got===$st && ($st===301 ? $loc===$want : ($want===''||str_contains($res->getContent(),$want)));
  if(!$pass){$fail++; echo "FAIL $m $url => $got ".($loc?:substr($res->getContent(),0,40))." (want $st $want)\n";}
}
// Inactive without route-map.json
$setPublic(sys_get_temp_dir().'/none-'.uniqid());
$res=(new App\Http\Middleware\StaticFrontendGate)->handle(Request::create('/ar/rooms'), fn($r)=>$router->dispatch($r));
if($res->getStatusCode()!==200){$fail++;echo "FAIL inactive gate still intercepted\n";}
echo $fail? "$fail failures of ".(count($cases)+1)."\n" : "all ".(count($cases)+1)." pass\n";
