<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Review;
use App\Models\SyncRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DomainModelEpicOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_places_and_sync_runs_tables_match_epic_one_schema(): void
    {
        $this->assertTrue(Schema::hasTable('places'));
        $this->assertTrue(Schema::hasTable('reviews'));
        $this->assertTrue(Schema::hasTable('sync_runs'));

        $this->assertTrue(Schema::hasColumns('places', [
            'user_id',
            'name',
            'source_url',
            'external_id',
            'rating',
            'reviews_count',
            'last_synced_at',
            'is_active',
        ]));

        $this->assertTrue(Schema::hasColumns('reviews', [
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
        ]));

        $this->assertTrue(Schema::hasColumns('sync_runs', [
            'place_id',
            'status',
            'started_at',
            'finished_at',
            'reviews_fetched',
            'reviews_created',
            'reviews_updated',
            'error_message',
        ]));
    }

    public function test_place_belongs_to_user_and_has_reviews_and_sync_runs(): void
    {
        $user = User::factory()->create();

        $place = Place::create([
            'user_id' => $user->id,
            'name' => 'Test place',
            'source_url' => 'https://yandex.ru/maps/org/test/123/reviews/',
            'external_id' => '123',
            'rating' => 4.8,
            'reviews_count' => 0,
            'is_active' => true,
        ]);

        Review::create([
            'place_id' => $place->id,
            'external_id' => 'review-1',
            'author_name' => 'Ivan',
            'text' => 'Great place',
            'rating' => 5,
        ]);

        SyncRun::create([
            'place_id' => $place->id,
            'status' => 'success',
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
            'reviews_fetched' => 1,
            'reviews_created' => 1,
            'reviews_updated' => 0,
        ]);

        $this->assertTrue($place->user->is($user));
        $this->assertCount(1, $user->places);
        $this->assertCount(1, $place->reviews);
        $this->assertCount(1, $place->syncRuns);
        $this->assertTrue($place->reviews->first()->place->is($place));
        $this->assertTrue($place->syncRuns->first()->place->is($place));
    }

    public function test_reviews_are_unique_per_place_and_external_id(): void
    {
        $user = User::factory()->create();

        $place = Place::create([
            'user_id' => $user->id,
            'name' => 'Unique test place',
            'source_url' => 'https://yandex.ru/maps/org/test-unique/456/reviews/',
            'external_id' => '456',
        ]);

        Review::create([
            'place_id' => $place->id,
            'external_id' => 'review-duplicate',
            'rating' => 5,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Review::create([
            'place_id' => $place->id,
            'external_id' => 'review-duplicate',
            'rating' => 4,
        ]);
    }
}
