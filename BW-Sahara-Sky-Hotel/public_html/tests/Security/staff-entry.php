<?php

$root = dirname(__DIR__, 2);
require $argv[1] ?? $root . '/vendor/autoload.php';
require $root . '/app/Http/Middleware/StaffEntry.php';
$app = new Illuminate\Foundation\Application($root);
$app->instance('config', new Illuminate\Config\Repository(['core' => ['base' => ['general' => ['admin_dir' => 'hotel-team-portal']]]]));
Illuminate\Container\Container::setInstance($app);
$middleware = new App\Http\Middleware\StaffEntry();
$count = 0;
foreach (['GET', 'POST', 'HEAD'] as $method) {
    foreach (['/admin', '/admin/login', '/ADMIN/users', '/%61dmin/login', '/index.php/admin/login', '/login/admin'] as $path) {
        $called = false;
        $response = $middleware->handle(Illuminate\Http\Request::create('https://hotel.example' . $path, $method), function () use (&$called) { $called = true; return new Symfony\Component\HttpFoundation\Response('unexpected'); });
        if ($called || $response->getStatusCode() !== 404 || $response->headers->has('Location')) throw new RuntimeException('Old path leaked: ' . $path);
        $count++;
    }
}
foreach (['/hotel-team-portal', '/hotel-team-portal/login', '/customer/overview', '/api/hotel/auth/google', '/administrator'] as $path) {
    $called = false;
    $response = $middleware->handle(Illuminate\Http\Request::create('https://hotel.example' . $path), function () use (&$called) { $called = true; return new Symfony\Component\HttpFoundation\Response('protected handler'); });
    if (! $called || $response->getStatusCode() !== 200) throw new RuntimeException('Unrelated route blocked');
    if (str_starts_with($path, '/hotel-team-portal') && ! str_contains($response->headers->get('Cache-Control'), 'no-store')) throw new RuntimeException('Staff response cacheable');
    $count++;
}
$app['config']->set('core.base.general.admin_dir', 'admin');
$response = $middleware->handle(Illuminate\Http\Request::create('https://hotel.example/admin/login'), fn () => new Symfony\Component\HttpFoundation\Response('existing login'));
if ($response->getStatusCode() !== 200) throw new RuntimeException('Default installation locked out');
echo 'PASS: ' . ($count + 1) . " staff entry cases; old URLs do not redirect or reveal the new URL; existing access control preserved\n";
