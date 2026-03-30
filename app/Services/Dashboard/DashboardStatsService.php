<?php

namespace App\Services\Dashboard;

use App\Models\Place;
use App\Models\Review;

class DashboardStatsService
{
    public function forUser(int $userId): array
    {
        $placeIds = Place::query()
            ->ownedBy($userId)
            ->pluck('id');
        $averageRating = $placeIds->isEmpty()
            ? null
            : Place::query()->whereIn('id', $placeIds)->avg('rating');

        return [
            'places_count' => $placeIds->count(),
            'reviews_count' => Review::query()->whereIn('place_id', $placeIds)->count(),
            'average_rating' => $averageRating === null ? null : round((float) $averageRating, 2),
            'last_synced_at' => Place::query()
                ->whereIn('id', $placeIds)
                ->whereNotNull('last_synced_at')
                ->max('last_synced_at'),
        ];
    }
}
