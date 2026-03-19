<?php

namespace App\Services\Places;

use App\Models\Place;
use App\Models\SyncRun;
use App\Services\Reviews\ReviewImportService;
use App\Services\Yandex\YandexReviewMapper;
use App\Services\Yandex\YandexReviewsFetcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlaceSyncService
{
    public function __construct(
        private readonly YandexReviewsFetcher $fetcher,
        private readonly YandexReviewMapper $mapper,
        private readonly ReviewImportService $importService
    ) {
    }

    public function sync(Place $place, Request $request): array
    {
        $syncRun = SyncRun::query()->create([
            'place_id' => $place->id,
            'status' => 'pending',
            'started_at' => now(),
            'reviews_fetched' => 0,
            'reviews_created' => 0,
            'reviews_updated' => 0,
        ]);

        try {
            $result = DB::transaction(function () use ($place, $request, $syncRun) {
                $fetched = $this->fetcher->fetch($place, $request);
                $mapped = $this->mapper->mapMany($fetched['reviews'] ?? [], $place->id);
                $stats = $this->importService->import($place, $mapped);

                $rating = $this->normalizeRating(data_get($fetched, 'place.rating'));
                $reviewsCount = (int) (data_get($fetched, 'place.reviews_count') ?? data_get($fetched, 'params.count', 0));

                $place->fill([
                    'external_id' => data_get($fetched, 'place.external_id') ?? $place->external_id,
                    'name' => data_get($fetched, 'place.name') ?: $place->name,
                    'rating' => $rating,
                    'reviews_count' => $reviewsCount,
                    'last_synced_at' => now(),
                ])->save();

                $syncRun->fill([
                    'status' => 'success',
                    'finished_at' => now(),
                    'reviews_fetched' => $stats['reviews_fetched'],
                    'reviews_created' => $stats['reviews_created'],
                    'reviews_updated' => $stats['reviews_updated'],
                    'error_message' => null,
                ])->save();

                return [
                    'status' => 'success',
                    'reviews_fetched' => $stats['reviews_fetched'],
                    'reviews_created' => $stats['reviews_created'],
                    'reviews_updated' => $stats['reviews_updated'],
                    'rating' => $place->rating,
                    'reviews_count' => $place->reviews_count,
                ];
            });

            return $result;
        } catch (\Throwable $e) {
            $syncRun->fill([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $e->getMessage(),
            ])->save();

            return [
                'status' => 'failed',
                'reviews_fetched' => 0,
                'reviews_created' => 0,
                'reviews_updated' => 0,
                'rating' => $place->rating,
                'reviews_count' => $place->reviews_count,
                'error_message' => $e->getMessage(),
            ];
        }
    }

    private function normalizeRating(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace(',', '.', (string) $value);

        if (!is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized, 2);
    }
}
