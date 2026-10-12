<?php

// Real Laravel requests/middleware and URL generator; no CMS, secrets or outbound traffic.
$root = dirname(__DIR__, 2);
require $argv[1] ?? $root . '/vendor/autoload.php';
require $root . '/app/Http/Middleware/CanonicalHost.php';
$app = new Illuminate\Foundation\Application($root);
$app->instance('env', 'production');
$app->instance('config', new Illuminate\Config\Repository());
Illuminate\Container\Container::setInstance($app);
Illuminate\Support\Facades\Facade::setFacadeApplication($app);
$defaults = [
    'enabled'=>true, 'origin'=>'https://bwsaharaskyhotel.com', 'status'=>302,
    'permanent_confirmed'=>false, 'production_host'=>'bwsaharaskyhotel.com',
    'excluded_paths'=>['booking','bookings','checkout','payment','payments','paypal','stripe',
        'webhook','webhooks','callback','callbacks','customer','customers','login',
        'register','logout','password','admin','api'],
    'sensitive_query_keys'=>['signature','token','_token','checkout_token','transaction_id',
        'payment_intent','payment_intent_client_secret','payerid','paymentid','code','order_id'],
];
$results = [];
function test(string $name, Closure $test): void {
    global $results, $app;
    $app->instance('env', 'production');
    Illuminate\Http\Request::setTrustedProxies([], -1);
    try { $test(); $results[$name]='PASS'; }
    catch (Throwable $e) { $results[$name]='FAIL: '.$e->getMessage(); }
    finally { Illuminate\Http\Request::setTrustedProxies([], -1); }
}
function check(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
function runRequest(string $url, string $method='GET', array $config=[], array $headers=[], array $cookies=[]): array {
    global $app, $defaults;
    $app['config']->set('canonical-host', array_replace($defaults, $config));
    // Preserve original query, rather than Request::create's GET parameter merge.
    $request = Illuminate\Http\Request::create($url, $method, $method==='GET'||$method==='HEAD'?[]:['synthetic'=>'body'], $cookies);
    foreach ($headers as $key=>$value) $request->headers->set($key,$value);
    $reached=false;
    $response=(new App\Http\Middleware\CanonicalHost())->handle($request,function($received)use(&$reached){
        $reached=true; return new Symfony\Component\HttpFoundation\Response($received->getMethod().':'.$received->input('synthetic'),200);
    });
    return [$response,$reached,$request];
}
foreach (['/','/ar','/zh','/rooms','/ar/rooms','/zh/rooms',
    '/rooms/standard-room-single','/ar/rooms/غرفة',
    '/zh/rooms/room%2Bname?start_date=20-10-2026&end_date=22-10-2026&adults=2&children=0&rooms=2',
    '/rooms/?a=1&a=2&q=a%2Bb+space&empty='] as $path) {
    test('Preserve path language and raw query '.$path,function()use($path){
        [$r,$next,$request]=runRequest('https://www.bwsaharaskyhotel.com'.$path);
        check($r->getStatusCode()===302&&!$next,'Did not redirect temporarily.');
        check($r->headers->get('Location')==='https://bwsaharaskyhotel.com'.$request->getRequestUri(),'URI changed.');
        check(str_contains($r->headers->get('Cache-Control'),'no-store'),'Temporary redirect cacheable.');
    });
}
test('HEAD follows the same safe redirect',function(){[$r]=runRequest('https://www.bwsaharaskyhotel.com/ar/rooms','HEAD');check($r->getStatusCode()===302,'HEAD incorrect.');});
foreach (['POST','PUT','PATCH','DELETE','OPTIONS'] as $method) {
    test('No redirect or body loss on '.$method,function()use($method){
        [$r,$next]=runRequest('https://www.bwsaharaskyhotel.com/ar/rooms',$method);
        check($next&&$r->getContent()===$method.':body','Method/body altered.');
    });
}
foreach (['/ar/booking/abc','/zh/checkout','/payments/paypal/callback','/webhooks/stripe','/admin/bookings','/customer/bookings','/api/orders','/ar/%62ooking/abc','/booking%2Fabc'] as $path) {
    test('Stateful endpoint unchanged '.$path,function()use($path){[$r,$next]=runRequest('https://www.bwsaharaskyhotel.com'.$path);check($next&&$r->getStatusCode()===200,'Stateful GET redirected.');});
}
foreach (['signature','token','_token','checkout_token','transaction_id','payment_intent','PayerID','paymentId'] as $key) {
    test('Signed or payment query unchanged '.$key,function()use($key){[$r,$next]=runRequest('https://www.bwsaharaskyhotel.com/rooms?'.$key.'=synthetic');check($next,'Sensitive link redirected.');});
}
test('Existing cookie session stays on its original host',function(){[$r,$next]=runRequest('https://www.bwsaharaskyhotel.com/rooms','GET',[],['Cookie'=>'session=synthetic']);check($next,'Cookie request redirected.');});
test('Parsed cookies also bypass redirect',function(){[$r,$next]=runRequest('https://www.bwsaharaskyhotel.com/rooms','GET',[],[],['session'=>'synthetic']);check($next,'Parsed session redirected.');});
test('Authenticated header stays unchanged',function(){[$r,$next]=runRequest('https://www.bwsaharaskyhotel.com/rooms','GET',[],['Authorization'=>'Bearer synthetic']);check($next,'Authenticated request redirected.');});
test('Disabled configuration changes nothing',function(){[$r,$next]=runRequest('https://www.bwsaharaskyhotel.com/rooms','GET',['enabled'=>false]);check($next,'Default opt-out ignored.');});
test('Primary host never redirects even behind HTTP TLS termination',function(){[$r,$next]=runRequest('http://bwsaharaskyhotel.com/rooms','GET',[],['X-Forwarded-Proto'=>'https']);check($next,'Primary host loop.');});
test('Untrusted forwarded host cannot trigger redirect',function(){[$r,$next]=runRequest('http://attacker.example/rooms','GET',[],['X-Forwarded-Host'=>'www.bwsaharaskyhotel.com']);check($next,'Untrusted forwarding used.');});
test('Trusted forwarding does not create a second hop',function(){
    Illuminate\Http\Request::setTrustedProxies(['127.0.0.1'],Illuminate\Http\Request::HEADER_X_FORWARDED_HOST|Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO);
    [$r,$next]=runRequest('http://internal.example/rooms','GET',[],['X-Forwarded-Host'=>'www.bwsaharaskyhotel.com','X-Forwarded-Proto'=>'https']);
    check(!$next&&$r->headers->get('Location')==='https://bwsaharaskyhotel.com/rooms','Trusted origin incorrect.');
    [$r,$next]=runRequest('http://internal.example/rooms','GET',[],['X-Forwarded-Host'=>'bwsaharaskyhotel.com','X-Forwarded-Proto'=>'https']);check($next,'Trusted primary host loops.');
});
test('Unrelated and staging hosts never redirect to production',function(){foreach(['https://staging.bwsaharaskyhotel.com/rooms','https://other.example/rooms']as$url){[$r,$next]=runRequest($url);check($next,'Unrelated host redirected.');}});
test('Nonproduction environment rejects production origin even if enabled',function(){global$app;$app->instance('env','staging');[$r,$next]=runRequest('https://www.bwsaharaskyhotel.com/rooms');check($next,'Staging redirected to production.');});
test('Staging uses its own primary origin',function(){global$app;$app->instance('env','staging');[$r,$next]=runRequest('https://www.hotel-stage.example/zh/rooms?adults=2','GET',['origin'=>'https://hotel-stage.example']);check(!$next&&$r->headers->get('Location')==='https://hotel-stage.example/zh/rooms?adults=2','Staging destination incorrect.');});
test('Permanent redirect requires explicit completed acceptance confirmation',function(){[$r]=runRequest('https://www.bwsaharaskyhotel.com/rooms','GET',['status'=>301]);check($r->getStatusCode()===302,'301 activated prematurely.');[$r]=runRequest('https://www.bwsaharaskyhotel.com/rooms','GET',['status'=>301,'permanent_confirmed'=>true]);check($r->getStatusCode()===301,'Confirmed 301 not supported.');});
test('Invalid origins fail closed',function(){foreach(['','http://bwsaharaskyhotel.com','https://www.bwsaharaskyhotel.com','https://user:pass@bwsaharaskyhotel.com','https://bwsaharaskyhotel.com/path','https://bwsaharaskyhotel.com?x=1']as$origin){[$r,$next]=runRequest('https://www.bwsaharaskyhotel.com/rooms','GET',['origin'=>$origin]);check($next,'Invalid origin redirected.');}});
test('URL generator uses one configured origin for routes and model URLs',function()use($app){
    $router=new Illuminate\Routing\Router(new Illuminate\Events\Dispatcher($app),$app);
    $router->get('/rooms',fn()=>null)->name('public.rooms');
    $router->getRoutes()->refreshNameLookups();
    $url=new Illuminate\Routing\UrlGenerator($router->getRoutes(),Illuminate\Http\Request::create('https://www.hotel-stage.example'));
    $url->useOrigin('https://hotel-stage.example');
    check($url->route('public.rooms')==='https://hotel-stage.example/rooms','Route host differs.');
    check($url->to('/ar/rooms/translated')==='https://hotel-stage.example/ar/rooms/translated','Model URL host differs.');
});
test('An actual Laravel signed URL keeps its valid signature',function()use($app){
    $router=new Illuminate\Routing\Router(new Illuminate\Events\Dispatcher($app),$app);
    $router->get('/rooms',fn()=>null)->name('signed.room');
    $router->getRoutes()->refreshNameLookups();
    $url=new Illuminate\Routing\UrlGenerator($router->getRoutes(),Illuminate\Http\Request::create('https://www.bwsaharaskyhotel.com'));
    $url->setKeyResolver(fn()=>'synthetic-test-key');
    $signed=$url->signedRoute('signed.room');
    [$response,$next,$request]=runRequest($signed);
    check($next&&$url->hasValidSignature($request),'Signed URL altered or signature lost.');
});
test('Actual environment configuration is disabled and temporary by default',function()use($root){
    $config=require $root.'/config/canonical-host.php';
    check($config['enabled']===false && $config['status']===302 && $config['permanent_confirmed']===false,'Unsafe configuration defaults.');
});
test('Both Apache trailing slash rules exclude unsafe methods headers cookies signed links and stateful paths',function()use($root){foreach([$root.'/.htaccess',$root.'/public/.htaccess']as$file){$source=file_get_contents($file);check((bool)preg_match('/RewriteCond %\{REQUEST_METHOD\} \^\(GET\|HEAD\)\$\s+RewriteCond %\{HTTP:Authorization\} \^\$\s+RewriteCond %\{HTTP:X-XSRF-Token\} \^\$\s+RewriteCond %\{HTTP_COOKIE\} \^\$\s+RewriteCond %\{QUERY_STRING\} .*signature.*payment_intent_client_secret.*code.*order_id.*\s+RewriteCond %\{REQUEST_URI\} .*booking.*\s+RewriteCond %\{REQUEST_FILENAME\} !-d/',$source),'Unsafe slash redirect rule.');}});
test('Google auth and callback paths bypass redirects with the real application configuration',function()use($root){
    $settings=require $root.'/config/canonical-host.php';
    $settings['enabled']=true;
    $settings['origin']='https://hotel-stage.example';
    foreach(['/auth/callback/google','/auth/callback/google?code=synthetic&state=synthetic','/api/hotel/auth/google?return_to=/login/']as$path){
        [$response,$next]=runRequest('https://www.hotel-stage.example'.$path,'GET',$settings);
        check($next&&$response->getStatusCode()===200,'Google authentication path redirected: '.$path);
    }
});
test('Global middleware registration is present',function()use($root){check(str_contains(file_get_contents($root.'/bootstrap/app.php'),'$middleware->append(\App\Http\Middleware\CanonicalHost::class)'),'Middleware not registered.');});
echo json_encode($results,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
exit(count(array_filter($results,fn($result)=>str_starts_with($result,'FAIL'))) ? 1 : 0);
