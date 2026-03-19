<?php

namespace App\Models;

use App\Models\Builders\ReviewBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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

    protected $casts = [
        'published_at' => 'datetime',
        'owner_replied_at' => 'datetime',
        'has_owner_reply' => 'boolean',
        'raw_payload' => 'array',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function newEloquentBuilder($query): ReviewBuilder
    {
        return new ReviewBuilder($query);
    }

    public function scopeForPlace(Builder $query, ?int $placeId): Builder
    {
        if (!$placeId) {
            return $query;
        }

        return $query->where('place_id', $placeId);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term) {
            $builder
                ->where('text', 'like', '%' . $term . '%')
                ->orWhere('author_name', 'like', '%' . $term . '%');
        });
    }

    public function scopeWithRating(Builder $query, ?int $rating): Builder
    {
        if (!$rating) {
            return $query;
        }

        return $query->where('rating', $rating);
    }

    public function scopePublishedFrom(Builder $query, string|\DateTimeInterface|null $date): Builder
    {
        if (!$date) {
            return $query;
        }

        return $query->where('published_at', '>=', $date);
    }

    public function scopePublishedTo(Builder $query, string|\DateTimeInterface|null $date): Builder
    {
        if (!$date) {
            return $query;
        }

        return $query->where('published_at', '<=', $date);
    }

    public function scopeNewest(Builder $query): Builder
    {
        return $query->newest();
    }

    public function scopeOldest(Builder $query): Builder
    {
        return $query->oldest();
    }
}
