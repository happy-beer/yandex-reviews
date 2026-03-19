<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = (int) $request->user()->id;
        $placeIds = Place::query()->ownedBy($userId)->pluck('id');

        return Inertia::render('Dashboard', [
            'stats' => [
                'places_count' => $placeIds->count(),
                'reviews_count' => Review::query()->whereIn('place_id', $placeIds)->count(),
                'average_rating' => $placeIds->isEmpty()
                    ? null
                    : Place::query()->whereIn('id', $placeIds)->avg('rating'),
                'last_synced_at' => Place::query()
                    ->whereIn('id', $placeIds)
                    ->whereNotNull('last_synced_at')
                    ->max('last_synced_at'),
            ],
        ]);
    }
}
