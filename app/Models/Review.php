<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_id',
        'external_id',
        'author_name',
        'text',
        'rating',
        'published_at',
        'has_owner_reply',
        'owner_reply_text',
        'owner_replied_at',
        'raw_payload',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
