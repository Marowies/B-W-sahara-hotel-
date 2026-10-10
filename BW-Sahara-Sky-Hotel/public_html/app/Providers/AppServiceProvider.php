<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $invalidate = static function (): void {
            $publish = static fn () => \Illuminate\Support\Facades\Cache::put('hotel.catalogue.generation', (string) \Illuminate\Support\Str::uuid());
            if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) {
                \Illuminate\Support\Facades\DB::afterCommit($publish);
            } else {
                $publish();
            }
        };
        foreach ([\Botble\Hotel\Models\Room::class, \Botble\Hotel\Models\Currency::class, \Botble\Slug\Models\Slug::class] as $model) {
            $model::saved($invalidate);
            $model::deleted($invalidate);
        }
    }
}
