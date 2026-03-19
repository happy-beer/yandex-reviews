<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_id',
        'status',
        'started_at',
        'finished_at',
        'reviews_fetched',
        'reviews_created',
        'reviews_updated',
        'error_message',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
