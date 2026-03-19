<?php

namespace Tests\Unit;

use App\Models\Place;
use App\Models\Review;
use App\Models\SyncRun;
use App\Models\User;
use App\Services\Places\PlaceSyncService;
use App\Services\Reviews\ReviewImportService;
use App\Services\Yandex\YandexReviewMapper;
use App\Services\Yandex\YandexReviewsFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use Tests\TestCase;

class PlaceSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_successfully_imports_reviews_and_updates_sync_run_and_place(): void
    {
        $user = User::factory()->create();
        $place = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Sync place',
            'source_url' => 'https://yandex.ru/maps/org/sync/10/reviews/',
            'reviews_count' => 0,
        ]);

        $this->mock(YandexReviewsFetcher::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetch')
                ->once()
                ->andReturn([
                    'place' => [
                        'external_id' => 'ext-10',
                        'name' => 'Sync place name',
                        'rating' => '4.7',
                        'reviews_count' => '123',
                    ],
                    'reviews' => [
                        [
                            'reviewId' => 'r-1',
                            'author' => ['name' => 'Nika'],
                            'text' => 'Great!',
                            'rating' => 5,
                            'updatedTime' => now()->toISOString(),
                        ],
                    ],
                    'params' => [
                        'count' => 123,
                    ],
                ]);
        });

        $service = app(PlaceSyncService::class);
        $result = $service->sync($place, Request::create('/places/' . $place->id . '/sync', 'POST'));

        $place->refresh();
        $syncRun = SyncRun::query()->where('place_id', $place->id)->latest('id')->firstOrFail();

        $this->assertSame('success', $result['status']);
        $this->assertSame(1, $result['reviews_fetched']);
        $this->assertSame(1, $result['reviews_created']);
        $this->assertSame(0, $result['reviews_updated']);
        $this->assertSame('success', $syncRun->status);
        $this->assertSame(1, $syncRun->reviews_fetched);
        $this->assertSame(123, $place->reviews_count);
        $this->assertSame(4.7, (float) $place->rating);
        $this->assertNotNull($place->last_synced_at);
        $this->assertSame(1, Review::query()->where('place_id', $place->id)->count());
    }

    public function test_sync_marks_run_failed_when_fetcher_throws(): void
    {
        $user = User::factory()->create();
        $place = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Failing place',
            'source_url' => 'https://yandex.ru/maps/org/fail/11/reviews/',
        ]);

        $this->mock(YandexReviewsFetcher::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetch')
                ->once()
                ->andThrow(new \RuntimeException('Sync provider failed'));
        });

        $service = app(PlaceSyncService::class);
        $result = $service->sync($place, Request::create('/places/' . $place->id . '/sync', 'POST'));

        $syncRun = SyncRun::query()->where('place_id', $place->id)->latest('id')->firstOrFail();

        $this->assertSame('failed', $result['status']);
        $this->assertSame('failed', $syncRun->status);
        $this->assertSame('Sync provider failed', $syncRun->error_message);
    }
}
