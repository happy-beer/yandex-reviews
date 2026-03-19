<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlaceResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\SyncRunResource;
use App\Http\Requests\StorePlaceRequest;
use App\Http\Requests\UpdatePlaceRequest;
use App\Models\Place;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlaceController extends Controller
{
    public function index(Request $request): Response
    {
        $places = Place::query()
            ->ownedBy((int) $request->user()->id)
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Places/Index', [
            'places' => PlaceResource::collection($places),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Places/Create');
    }

    public function store(StorePlaceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $place = Place::query()->create([
            'user_id' => (int) $request->user()->id,
            'name' => $data['name'],
            'source_url' => $data['source_url'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        return redirect()
            ->route('places.show', $place)
            ->with('success', 'Organization created successfully.');
    }

    public function show(Request $request, Place $place): Response
    {
        $this->authorize('view', $place);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'sort' => ['nullable', 'in:newest,oldest,rating_desc,rating_asc'],
        ]);

        $sort = $filters['sort'] ?? 'newest';

        $reviewsQuery = $place->reviews()
            ->search($filters['search'] ?? null)
            ->withRating(isset($filters['rating']) ? (int) $filters['rating'] : null)
            ->publishedFrom($filters['date_from'] ?? null)
            ->publishedTo($filters['date_to'] ?? null);

        if ($sort === 'oldest') {
            $reviewsQuery->oldest();
        } elseif ($sort === 'rating_desc') {
            $reviewsQuery->orderByDesc('rating')->orderByDesc('published_at');
        } elseif ($sort === 'rating_asc') {
            $reviewsQuery->orderBy('rating')->orderByDesc('published_at');
        } else {
            $reviewsQuery->newest();
        }

        $reviews = $reviewsQuery
            ->paginate(10, ['*'], 'reviews_page')
            ->withQueryString();

        $syncRuns = $place->syncRuns()
            ->latest('started_at')
            ->paginate(10, ['*'], 'sync_runs_page')
            ->withQueryString();

        return Inertia::render('Places/Show', [
            'place' => (new PlaceResource($place))->resolve(),
            'reviews' => ReviewResource::collection($reviews),
            'syncRuns' => SyncRunResource::collection($syncRuns),
            'filters' => [
                'search' => $filters['search'] ?? null,
                'rating' => isset($filters['rating']) ? (int) $filters['rating'] : null,
                'date_from' => $filters['date_from'] ?? null,
                'date_to' => $filters['date_to'] ?? null,
                'sort' => $sort,
            ],
        ]);
    }

    public function edit(Place $place): Response
    {
        $this->authorize('update', $place);

        return Inertia::render('Places/Edit', [
            'place' => (new PlaceResource($place))->resolve(),
        ]);
    }

    public function update(UpdatePlaceRequest $request, Place $place): RedirectResponse
    {
        $this->authorize('update', $place);

        $data = $request->validated();

        $place->fill([
            'name' => $data['name'],
            'source_url' => $data['source_url'],
            'is_active' => $data['is_active'] ?? true,
        ])->save();

        return redirect()
            ->route('places.show', $place)
            ->with('success', 'Organization updated successfully.');
    }

    public function destroy(Place $place): RedirectResponse
    {
        $this->authorize('delete', $place);

        $place->delete();

        return redirect()
            ->route('places.index')
            ->with('success', 'Organization deleted successfully.');
    }
}
