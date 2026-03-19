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

        return [
            'places_count' => $placeIds->count(),
            'reviews_count' => Review::query()->whereIn('place_id', $placeIds)->count(),
            'average_rating' => $placeIds->isEmpty()
                ? null
                : Place::query()->whereIn('id', $placeIds)->avg('rating'),
            'last_synced_at' => Place::query()
                ->whereIn('id', $placeIds)
                ->whereNotNull('last_synced_at')
                ->max('last_synced_at'),
        ];
    }
}
