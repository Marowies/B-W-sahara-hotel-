<?php

namespace Botble\BotbleNulled\Providers;

use  Botble\BotbleNulled\Commands\BotbleNulledCommand;

use Botble\Base\Supports\ServiceProvider;

class BotbleNulledActivatorProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            BotbleNulledCommand::class,
        ]);
    }
}