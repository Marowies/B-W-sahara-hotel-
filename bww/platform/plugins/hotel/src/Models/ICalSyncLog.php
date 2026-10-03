<?php

namespace Botble\Hotel\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ICalSyncLog extends BaseModel
{
    protected $table = 'ht_ical_sync_logs';

    protected $fillable = [
        'room_id',
        'calendar_id',
        'status',
        'message',
        'data',
    ];

    protected $casts = [
        'data' => 'json',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id')->withDefault();
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(RoomCalendar::class, 'calendar_id')->withDefault();
    }
}
