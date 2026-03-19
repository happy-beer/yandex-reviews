<?php

namespace Tests\Unit;

use App\Http\Resources\PlaceResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\SyncRunResource;
use App\Models\Place;
use App\Models\Review;
use App\Models\SyncRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_place_resource_contains_epic_six_fields(): void
    {
        $user = User::factory()->create();
        $place = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Resource place',
            'source_url' => 'https://yandex.ru/maps/org/resource/1/reviews/',
            'rating' => 4.8,
            'reviews_count' => 20,
            'last_synced_at' => now(),
            'is_active' => true,
        ]);

        $payload = (new PlaceResource($place))->toArray(new Request());

        $this->assertSame($place->id, $payload['id']);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('source_url', $payload);
        $this->assertArrayHasKey('rating', $payload);
        $this->assertArrayHasKey('reviews_count', $payload);
        $this->assertArrayHasKey('last_synced_at', $payload);
        $this->assertArrayHasKey('is_active', $payload);
        $this->assertArrayHasKey('created_at', $payload);
    }

    public function test_review_resource_contains_epic_six_fields(): void
    {
        $user = User::factory()->create();
        $place = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Review place',
            'source_url' => 'https://yandex.ru/maps/org/review/2/reviews/',
        ]);

        $review = Review::query()->create([
            'place_id' => $place->id,
            'external_id' => 'r-1',
            'author_name' => 'Anna',
            'rating' => 5,
            'text' => 'Great',
            'published_at' => now(),
            'has_owner_reply' => true,
            'owner_reply_text' => 'Thanks',
            'owner_replied_at' => now(),
        ])->load('place');

        $payload = (new ReviewResource($review))->toArray(new Request());

        $this->assertSame($review->id, $payload['id']);
        $this->assertSame($place->name, $payload['place_name']);
        $this->assertArrayHasKey('place_id', $payload);
        $this->assertArrayHasKey('author_name', $payload);
        $this->assertArrayHasKey('rating', $payload);
        $this->assertArrayHasKey('text', $payload);
        $this->assertArrayHasKey('published_at', $payload);
        $this->assertArrayHasKey('has_owner_reply', $payload);
        $this->assertArrayHasKey('owner_reply_text', $payload);
        $this->assertArrayHasKey('owner_replied_at', $payload);
    }

    public function test_sync_run_resource_contains_epic_six_fields(): void
    {
        $user = User::factory()->create();
        $place = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Sync place',
            'source_url' => 'https://yandex.ru/maps/org/sync/3/reviews/',
        ]);

        $syncRun = SyncRun::query()->create([
            'place_id' => $place->id,
            'status' => 'success',
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
            'reviews_fetched' => 10,
            'reviews_created' => 3,
            'reviews_updated' => 7,
            'error_message' => null,
        ]);

        $payload = (new SyncRunResource($syncRun))->toArray(new Request());

        $this->assertSame($syncRun->id, $payload['id']);
        $this->assertArrayHasKey('status', $payload);
        $this->assertArrayHasKey('started_at', $payload);
        $this->assertArrayHasKey('finished_at', $payload);
        $this->assertArrayHasKey('reviews_fetched', $payload);
        $this->assertArrayHasKey('reviews_created', $payload);
        $this->assertArrayHasKey('reviews_updated', $payload);
        $this->assertArrayHasKey('error_message', $payload);
    }
}
