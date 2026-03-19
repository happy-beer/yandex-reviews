<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Review;
use App\Models\SyncRun;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainModelEpicTwoTest extends TestCase
{
    use RefreshDatabase;

    public function test_place_model_supports_owned_by_and_active_scopes_and_casts(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $activePlace = Place::create([
            'user_id' => $owner->id,
            'name' => 'Active place',
            'source_url' => 'https://yandex.ru/maps/org/active/1/reviews/',
            'is_active' => true,
            'last_synced_at' => now(),
        ]);

        Place::create([
            'user_id' => $owner->id,
            'name' => 'Inactive place',
            'source_url' => 'https://yandex.ru/maps/org/inactive/2/reviews/',
            'is_active' => false,
        ]);

        Place::create([
            'user_id' => $otherUser->id,
            'name' => 'Other user place',
            'source_url' => 'https://yandex.ru/maps/org/other/3/reviews/',
            'is_active' => true,
        ]);

        $ownedAndActive = Place::query()
            ->ownedBy($owner->id)
            ->active()
            ->get();

        $this->assertCount(1, $ownedAndActive);
        $this->assertTrue($ownedAndActive->first()->is($activePlace));
        $this->assertIsBool($activePlace->fresh()->is_active);
        $this->assertInstanceOf(CarbonInterface::class, $activePlace->fresh()->last_synced_at);
    }

    public function test_review_model_scopes_filter_and_sort_reviews(): void
    {
        $user = User::factory()->create();

        $firstPlace = Place::create([
            'user_id' => $user->id,
            'name' => 'First place',
            'source_url' => 'https://yandex.ru/maps/org/first/11/reviews/',
        ]);

        $secondPlace = Place::create([
            'user_id' => $user->id,
            'name' => 'Second place',
            'source_url' => 'https://yandex.ru/maps/org/second/22/reviews/',
        ]);

        Review::create([
            'place_id' => $firstPlace->id,
            'external_id' => 'r-old',
            'author_name' => 'Anna',
            'text' => 'Old but great coffee',
            'rating' => 5,
            'published_at' => now()->subDays(3),
            'has_owner_reply' => true,
            'owner_replied_at' => now()->subDays(2),
            'raw_payload' => ['source' => 'legacy'],
        ]);

        Review::create([
            'place_id' => $firstPlace->id,
            'external_id' => 'r-new',
            'author_name' => 'Boris',
            'text' => 'Fresh review with issue',
            'rating' => 2,
            'published_at' => now()->subDay(),
        ]);

        Review::create([
            'place_id' => $secondPlace->id,
            'external_id' => 'r-other-place',
            'author_name' => 'Chris',
            'text' => 'Other place review',
            'rating' => 5,
            'published_at' => now(),
        ]);

        $filtered = Review::query()
            ->forPlace($firstPlace->id)
            ->search('coffee')
            ->withRating(5)
            ->publishedFrom(now()->subDays(4))
            ->publishedTo(now()->subDays(2))
            ->newest()
            ->get();

        $this->assertCount(1, $filtered);
        $this->assertSame('r-old', $filtered->first()->external_id);
        $this->assertInstanceOf(CarbonInterface::class, $filtered->first()->published_at);
        $this->assertInstanceOf(CarbonInterface::class, $filtered->first()->owner_replied_at);
        $this->assertIsBool($filtered->first()->has_owner_reply);
        $this->assertIsArray($filtered->first()->raw_payload);

        $sortedIds = Review::query()
            ->forPlace($firstPlace->id)
            ->oldest()
            ->pluck('external_id')
            ->all();

        $this->assertSame(['r-old', 'r-new'], $sortedIds);
    }

    public function test_sync_run_model_casts_dates_and_calculates_duration(): void
    {
        $user = User::factory()->create();

        $place = Place::create([
            'user_id' => $user->id,
            'name' => 'Sync place',
            'source_url' => 'https://yandex.ru/maps/org/sync/33/reviews/',
        ]);

        $syncRun = SyncRun::create([
            'place_id' => $place->id,
            'status' => 'success',
            'started_at' => now()->subSeconds(90),
            'finished_at' => now(),
            'reviews_fetched' => 10,
            'reviews_created' => 4,
            'reviews_updated' => 6,
        ])->fresh();

        $this->assertInstanceOf(CarbonInterface::class, $syncRun->started_at);
        $this->assertInstanceOf(CarbonInterface::class, $syncRun->finished_at);
        $this->assertNotNull($syncRun->durationSeconds());
        $this->assertGreaterThanOrEqual(90, $syncRun->durationSeconds());
    }
}
