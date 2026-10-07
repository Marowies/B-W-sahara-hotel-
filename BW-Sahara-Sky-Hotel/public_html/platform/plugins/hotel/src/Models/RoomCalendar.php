<?php

namespace Botble\Hotel\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomCalendar extends BaseModel
{
    protected $table = 'ht_room_calendars';

    protected $fillable = [
        'room_id',
        'name',
        'url',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id')->withDefault();
    }
}
