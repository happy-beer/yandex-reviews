<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexReviewsRequest;
use App\Http\Resources\PlaceResource;
use App\Http\Resources\ReviewResource;
use App\Models\Place;
use App\Models\Review;
use Inertia\Inertia;
use Inertia\Response;

class ReviewsController extends Controller
{
    public function index(IndexReviewsRequest $request): Response
    {
        $data = $request->validated();
        $userId = (int) $request->user()->id;

        $ownedPlaceIds = Place::query()
            ->ownedBy($userId)
            ->pluck('id');

        $requestedPlaceId = isset($data['place_id']) ? (int) $data['place_id'] : null;
        $placeId = null;

        if ($requestedPlaceId && $ownedPlaceIds->contains($requestedPlaceId)) {
            $placeId = $requestedPlaceId;
        }

        $query = Review::query()
            ->whereIn('place_id', $ownedPlaceIds)
            ->forPlace($placeId)
            ->search($data['search'] ?? null)
            ->withRating(isset($data['rating']) ? (int) $data['rating'] : null)
            ->publishedFrom($data['date_from'] ?? null)
            ->publishedTo($data['date_to'] ?? null)
            ->with('place:id,name');

        $sort = $data['sort'] ?? 'newest';

        if ($sort === 'oldest') {
            $query->oldest();
        } elseif ($sort === 'rating_desc') {
            $query->orderByDesc('rating')->orderByDesc('published_at');
        } elseif ($sort === 'rating_asc') {
            $query->orderBy('rating')->orderByDesc('published_at');
        } else {
            $query->newest();
        }

        $reviews = $query
            ->paginate(15)
            ->withQueryString();

        $places = Place::query()
            ->ownedBy($userId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Reviews/Index', [
            'filters' => [
                'place_id' => $placeId,
                'rating' => isset($data['rating']) ? (int) $data['rating'] : null,
                'search' => $data['search'] ?? null,
                'date_from' => $data['date_from'] ?? null,
                'date_to' => $data['date_to'] ?? null,
                'sort' => $sort,
            ],
            'places' => PlaceResource::collection($places),
            'reviews' => ReviewResource::collection($reviews),
        ]);
    }
}
