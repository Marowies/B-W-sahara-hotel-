<?php

namespace Botble\BotbleNulled;

use Illuminate\Support\Facades\Schema;
use Botble\PluginManagement\Abstracts\PluginOperationAbstract;

class Plugin extends PluginOperationAbstract
{
    public static function remove(): void
    {
        Schema::dropIfExists('Botble_Nulleds');
        Schema::dropIfExists('Botble_Nulleds_translations');
    }
}
