<?php

namespace Botble\BotbleNulled\Providers;

use Botble\Base\Supports\ServiceProvider;
use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\Base\Facades\DashboardMenu;
use Botble\BotbleNulled\Models\BotbleNulled;
use Botble\BotbleNulled\Providers\BotbleNulledActivatorProvider;

class BotbleNulledServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot(): void
    {
        $this
            ->setNamespace('plugins/botble_nulled')
            ->loadHelpers()
            ->loadAndPublishConfigurations(["permissions"])
            ->loadAndPublishTranslations()
            ->loadRoutes()
            ->loadAndPublishViews()
            ->loadMigrations();
            
            if (defined('LANGUAGE_ADVANCED_MODULE_SCREEN_NAME')) {
                \Botble\LanguageAdvanced\Supports\LanguageAdvancedManager::registerModule(BotbleNulled::class, [
                    'name',
                ]);
            }

            $this->app->register(BotbleNulledActivatorProvider::class);
            
    }
}
