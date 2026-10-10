<?php

namespace Tests\Feature;

use Botble\Language\Facades\Language;
use Botble\Language\Listeners\AddHrefLangListener;
use Botble\Slug\Models\Slug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MissingSlugTranslationTest extends TestCase
{
    public function test_the_cluster_resolves_the_original_slug_from_every_source_language(): void
    {
        Schema::create('slugs', function (Blueprint $table): void {
            $table->id();
            $table->string('key');
            $table->string('prefix');
            $table->string('reference_type');
            $table->unsignedInteger('reference_id');
            $table->timestamps();
        });
        Schema::create('test_slug_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('slug_id');
            $table->string('lang_code');
            $table->string('key');
        });
        Slug::resolveRelationUsing('translations', fn (Slug $slug) => $slug->hasMany(SyntheticSlugTranslation::class, 'slug_id'));
        $slug = Slug::query()->create(['key' => 'original-room', 'prefix' => 'rooms', 'reference_type' => 'synthetic-room', 'reference_id' => 7]);
        SyntheticSlugTranslation::query()->create(['slug_id' => $slug->id, 'lang_code' => 'ar', 'key' => 'arabic-room']);

        Language::swap(new class {
            public function getDefaultLocale(): string { return 'en'; }
            public function getDefaultLocaleCode(): string { return 'en_US'; }
            public function hideDefaultLocaleInURL(): bool { return true; }
            public function getLocaleByLocaleCode($code): string { return ['en_US' => 'en', 'ar' => 'ar', 'zh_CN' => 'zh'][$code]; }
            public function getSupportedLocales(): array { return ['en' => ['lang_code' => 'en_US'], 'ar' => ['lang_code' => 'ar'], 'zh' => ['lang_code' => 'zh_CN']]; }
            public function formatLocaleForHrefLang($code): string { return strtolower(str_replace('_', '-', $code)); }
            public function getLocalizedURL($locale, $url): string { return $url; }
        });
        $listener = new class extends AddHrefLangListener {
            protected function isLanguageAdvancedSupported(string $type): bool { return true; }
            public function cluster(): array { return $this->generateHreflangUrls('synthetic-room', 7); }
        };
        $expected = ['en-us' => 'http://localhost/rooms/original-room', 'en' => 'http://localhost/rooms/original-room', 'ar' => 'http://localhost/ar/rooms/arabic-room', 'zh-cn' => 'http://localhost/zh/rooms/original-room', 'zh' => 'http://localhost/zh/rooms/original-room'];
        foreach (['/rooms/original-room', '/ar/rooms/arabic-room', '/zh/rooms/original-room'] as $source) {
            $this->app->instance('url', new \Illuminate\Routing\UrlGenerator(new \Illuminate\Routing\RouteCollection(), \Illuminate\Http\Request::create('http://localhost' . $source)));
            self::assertSame($expected, $listener->cluster());
        }
    }
}

class SyntheticSlugTranslation extends Model
{
    protected $table = 'test_slug_translations';
    protected $guarded = [];
    public $timestamps = false;
}
