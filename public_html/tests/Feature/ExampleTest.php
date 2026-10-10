<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_a_fresh_cms_redirects_to_installation_without_a_real_database(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('installers.welcome'));
        self::assertSame('sqlite', config('database.default'));
        self::assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    public function test_laravel_health_route_is_available(): void
    {
        $this->get('/up')->assertOk();
    }
}
