<?php

namespace Botble\Hotel\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Models\BaseModel;
use Illuminate\Support\Facades\Cache;

class Currency extends BaseModel
{
    protected $table = 'ht_currencies';

    protected $fillable = [
        'title',
        'symbol',
        'is_prefix_symbol',
        'order',
        'decimals',
        'number_format_style',
        'space_between_price_and_currency',
        'is_default',
        'exchange_rate',
    ];

    protected $casts = [
        'title' => SafeContent::class,
        'is_prefix_symbol' => 'boolean',
        'space_between_price_and_currency' => 'boolean',
        'is_default' => 'boolean',
        'exchange_rate' => 'double',
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('currencies');
        });

        static::deleted(function () {
            Cache::forget('currencies');
        });
    }
}
