<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\SyncRun;
use App\Models\User;
use App\Services\Yandex\YandexReviewMapper;
use App\Services\Yandex\YandexReviewsFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class PlaceSyncControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_route_is_accessible_to_owner(): void
    {
        $user = User::factory()->create();

        $place = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Owned place',
            'source_url' => 'https://yandex.ru/maps/org/owned/20/reviews/',
        ]);

        $this->mock(YandexReviewsFetcher::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetch')
                ->once()
                ->andReturn([
                    'place' => [
                        'external_id' => 'biz-20',
                        'name' => 'Owned place',
                        'rating' => '4.7',
                        'reviews_count' => 99,
                    ],
                    'reviews' => [
                        ['reviewId' => 'raw-1'],
                    ],
                    'params' => [
                        'count' => 99,
                    ],
                ]);
        });

        $this->mock(YandexReviewMapper::class, function (MockInterface $mock) use ($place) {
            $mock->shouldReceive('mapMany')
                ->once()
                ->andReturn([
                    [
                        'place_id' => $place->id,
                        'external_id' => 'raw-1',
                        'author_name' => 'Ivan',
                        'text' => 'Great',
                        'rating' => 5,
                        'published_at' => now()->subHour()->toDateTimeString(),
                        'has_owner_reply' => false,
                        'owner_reply_text' => null,
                        'owner_replied_at' => null,
                        'raw_payload' => ['reviewId' => 'raw-1'],
                    ],
                ]);
        });

        $response = $this->actingAs($user)->post(route('places.sync', $place));

        $response
            ->assertRedirect()
            ->assertSessionHas('success', 'Sync completed successfully.');
    }

    public function test_non_owner_cannot_trigger_sync(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $place = Place::query()->create([
            'user_id' => $owner->id,
            'name' => 'Protected place',
            'source_url' => 'https://yandex.ru/maps/org/protected/30/reviews/',
        ]);

        $response = $this->actingAs($otherUser)->post(route('places.sync', $place));

        $response->assertForbidden();
    }

    public function test_successful_sync_creates_sync_run_and_updates_place_metrics(): void
    {
        $user = User::factory()->create();

        $place = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Sync place',
            'source_url' => 'https://yandex.ru/maps/org/owned/21/reviews/',
            'rating' => null,
            'reviews_count' => 0,
            'last_synced_at' => null,
        ]);

        $this->mock(YandexReviewsFetcher::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetch')
                ->once()
                ->andReturn([
                    'place' => [
                        'external_id' => 'biz-21',
                        'name' => 'Sync place',
                        'rating' => '4.8',
                        'reviews_count' => 42,
                    ],
                    'reviews' => [
                        ['reviewId' => 'r-1'],
                        ['reviewId' => 'r-2'],
                    ],
                    'params' => [
                        'count' => 42,
                    ],
                ]);
        });

        $this->mock(YandexReviewMapper::class, function (MockInterface $mock) use ($place) {
            $mock->shouldReceive('mapMany')
                ->once()
                ->andReturn([
                    [
                        'place_id' => $place->id,
                        'external_id' => 'r-1',
                        'author_name' => 'Anna',
                        'text' => 'Excellent',
                        'rating' => 5,
                    ],
                    [
                        'place_id' => $place->id,
                        'external_id' => 'r-2',
                        'author_name' => 'Boris',
                        'text' => 'Great',
                        'rating' => 4,
                    ],
                ]);
        });

        $this->actingAs($user)
            ->post(route('places.sync', $place))
            ->assertRedirect()
            ->assertSessionHas('success', 'Sync completed successfully.');

        $this->assertDatabaseHas('sync_runs', [
            'place_id' => $place->id,
            'status' => 'success',
            'reviews_fetched' => 2,
            'reviews_created' => 2,
            'reviews_updated' => 0,
            'error_message' => null,
        ]);

        $place->refresh();
        $this->assertSame('biz-21', $place->external_id);
        $this->assertSame(4.8, (float) $place->rating);
        $this->assertSame(42, $place->reviews_count);
        $this->assertNotNull($place->last_synced_at);
    }

    public function test_failed_sync_saves_failed_sync_run(): void
    {
        $user = User::factory()->create();

        $place = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Sync place',
            'source_url' => 'https://yandex.ru/maps/org/owned/22/reviews/',
            'rating' => 4.1,
            'reviews_count' => 8,
        ]);

        $this->mock(YandexReviewsFetcher::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetch')
                ->once()
                ->andThrow(new \RuntimeException('Provider timeout'));
        });

        $this->mock(YandexReviewMapper::class, function (MockInterface $mock) {
            $mock->shouldReceive('mapMany')->never();
        });

        $this->actingAs($user)
            ->post(route('places.sync', $place))
            ->assertRedirect()
            ->assertSessionHas('error', 'Provider timeout');

        /** @var SyncRun|null $failedRun */
        $failedRun = SyncRun::query()
            ->where('place_id', $place->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($failedRun);
        $this->assertSame('failed', $failedRun->status);
        $this->assertSame('Provider timeout', $failedRun->error_message);
        $this->assertNotNull($failedRun->finished_at);

        $place->refresh();
        $this->assertSame(4.1, (float) $place->rating);
        $this->assertSame(8, $place->reviews_count);
    }
}
