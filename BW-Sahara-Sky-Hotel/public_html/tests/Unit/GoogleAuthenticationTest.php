<?php

namespace Tests\Unit;

use Botble\Base\Supports\Filter;
use Botble\Base\Supports\MacroableModels;
use Botble\SocialLogin\Models\SocialLogin;
use Botble\SocialLogin\Services\GoogleAccountResolver;
use Botble\SocialLogin\Services\SocialLoginService;
use Botble\SocialLogin\Supports\LoginRedirect;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use Laravel\Socialite\Two\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

// Plugin namespaces normally register during application boot. These tests only
// boot Eloquent with an isolated in-memory database; they never boot the hotel app.
spl_autoload_register(function (string $class): void {
    $prefix = 'Botble\\SocialLogin\\';
    if (str_starts_with($class, $prefix)) {
        $path = dirname(__DIR__, 2) . '/platform/plugins/social-login/src/'
            . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
});

class GoogleAuthenticationTest extends TestCase
{
    private Capsule $database;

    protected function setUp(): void
    {
        parent::setUp();
        $container = new Container();
        Container::setInstance($container);
        $container->instance('config', new Repository());
        $container->singleton(MacroableModels::class);
        $container->singleton(Filter::class);
        $container->alias(Filter::class, 'core.filter');
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($container);
        require_once dirname(__DIR__, 2) . '/platform/core/base/helpers/action-filter.php';

        $this->database = new Capsule($container);
        $this->database->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->database->setAsGlobal();
        $this->database->bootEloquent();
        $container->instance('db', $this->database->getDatabaseManager());
        $container->instance('db.schema', $this->database->schema());

        $this->database->schema()->create('google_test_customers', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->timestamps();
        });

        $migration = require dirname(__DIR__, 2) . '/platform/plugins/social-login/database/migrations/2025_04_08_040931_create_social_logins_table.php';
        $migration->up();
    }

    private function google(string $email = 'customer@example.test', string $id = 'google-123', bool $verified = true): User
    {
        return (new User())->setRaw(['verified_email' => $verified])
            ->map(['id' => $id, 'email' => $email, 'name' => 'Hotel Customer']);
    }

    private function customer(string $email = 'customer@example.test'): GoogleTestCustomer
    {
        return GoogleTestCustomer::query()->create(['email' => $email]);
    }

    private function link(Model $customer, string $id = 'google-123'): SocialLogin
    {
        return (new SocialLoginService())->createOrUpdateSocialLogin($customer, [
            'provider' => 'google', 'provider_id' => $id, 'token' => 'test-only-token',
        ]);
    }

    public function test_new_verified_customer_returns_null_for_creation(): void
    {
        $resolver = new GoogleAccountResolver(new SocialLoginService());
        $this->assertNull($resolver->resolve($this->google(), GoogleTestCustomer::class));
    }

    public function test_existing_email_reuses_customer_id(): void
    {
        $customer = $this->customer();
        $resolved = (new GoogleAccountResolver(new SocialLoginService()))
            ->resolve($this->google(), GoogleTestCustomer::class);
        $this->assertSame($customer->id, $resolved->id);
        $this->assertSame(1, GoogleTestCustomer::query()->count());
    }

    public function test_changed_google_email_keeps_original_customer_even_if_email_matches_another_customer(): void
    {
        $original = $this->customer();
        $this->link($original);
        $this->customer('changed@example.test');
        $resolved = (new GoogleAccountResolver(new SocialLoginService()))
            ->resolve($this->google('changed@example.test'), GoogleTestCustomer::class);
        $this->assertSame($original->id, $resolved->id);
        $this->assertSame($original->id, SocialLogin::query()->first()->user_id);
    }

    public function test_unverified_email_is_rejected_before_linking(): void
    {
        $this->expectException(RuntimeException::class);
        (new GoogleAccountResolver(new SocialLoginService()))
            ->resolve($this->google(verified: false), GoogleTestCustomer::class);
    }

    public function test_different_google_identity_cannot_replace_existing_link_by_email(): void
    {
        $this->link($this->customer());
        $this->expectException(RuntimeException::class);
        (new GoogleAccountResolver(new SocialLoginService()))
            ->resolve($this->google(id: 'another-google-id'), GoogleTestCustomer::class);
    }

    public function test_identity_from_another_model_type_cannot_log_in_as_customer(): void
    {
        $this->link($this->customer());
        $this->expectException(RuntimeException::class);
        (new GoogleAccountResolver(new SocialLoginService()))
            ->resolve($this->google(), OtherGoogleTestCustomer::class);
    }

    public function test_service_cannot_move_identity_between_customers(): void
    {
        $original = $this->customer();
        $identity = $this->link($original);
        try {
            $this->link($this->customer('other@example.test'));
            $this->fail('An existing identity was reassigned.');
        } catch (RuntimeException) {
            $this->assertSame($original->id, $identity->fresh()->user_id);
        }
    }

    public function test_repeat_login_updates_same_link_without_creating_duplicate(): void
    {
        $customer = $this->customer();
        $identity = $this->link($customer);
        $this->assertSame($identity->id, $this->link($customer)->id);
        $this->assertSame(1, SocialLogin::query()->count());
    }

    public function test_service_rejects_replacing_customer_google_identity(): void
    {
        $customer = $this->customer();
        $identity = $this->link($customer);
        try {
            $this->link($customer, 'another-google-id');
            $this->fail('The original identity was replaced.');
        } catch (RuntimeException) {
            $this->assertSame('google-123', $identity->fresh()->provider_id);
        }
    }

    #[DataProvider('redirects')]
    public function test_login_return_url_stays_on_hotel_site(mixed $intended, string $expected): void
    {
        $this->assertSame($expected, LoginRedirect::resolve($intended, 'https://hotel.example', '/'));
    }

    public static function redirects(): array
    {
        return [
            'booking token retained' => ['/booking/test-token', '/booking/test-token'],
            'same origin' => ['https://hotel.example/booking/test-token', 'https://hotel.example/booking/test-token'],
            'external host' => ['https://other.example/', '/'],
            'protocol relative' => ['//other.example/', '/'],
            'backslash' => ['/\\other.example/', '/'],
            'credentials' => ['https://user@hotel.example/', '/'],
            'different port' => ['https://hotel.example:8443/', '/'],
            'invalid type' => [[], '/'],
            'control character' => ["/\n/other.example", '/'],
        ];
    }
}

class GoogleTestCustomer extends Model
{
    protected $table = 'google_test_customers';
    protected $guarded = [];
}

class OtherGoogleTestCustomer extends GoogleTestCustomer
{
}
