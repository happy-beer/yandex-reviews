<?php

namespace Tests\Unit;

use App\Models\Place;
use App\Models\Review;
use App\Models\User;
use App\Services\Reviews\ReviewImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_creates_new_reviews_and_returns_stats(): void
    {
        $user = User::factory()->create();
        $place = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Import place',
            'source_url' => 'https://yandex.ru/maps/org/import/1/reviews/',
        ]);

        $service = new ReviewImportService();

        $result = $service->import($place, [
            [
                'external_id' => 'r-1',
                'author_name' => 'Ivan',
                'text' => 'Nice',
                'rating' => 5,
                'published_at' => now()->subDay()->toDateTimeString(),
                'has_owner_reply' => false,
                'owner_reply_text' => null,
                'owner_replied_at' => null,
                'raw_payload' => ['a' => 1],
            ],
            [
                'external_id' => 'r-2',
                'author_name' => 'Anna',
                'text' => 'Good',
                'rating' => 4,
            ],
        ]);

        $this->assertSame(2, $result['reviews_fetched']);
        $this->assertSame(2, $result['reviews_created']);
        $this->assertSame(0, $result['reviews_updated']);
        $this->assertSame(2, Review::query()->where('place_id', $place->id)->count());
    }

    public function test_import_updates_existing_review_and_does_not_duplicate(): void
    {
        $user = User::factory()->create();
        $place = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Import place',
            'source_url' => 'https://yandex.ru/maps/org/import/2/reviews/',
        ]);

        Review::query()->create([
            'place_id' => $place->id,
            'external_id' => 'r-1',
            'author_name' => 'Old',
            'text' => 'Old text',
            'rating' => 3,
        ]);

        $service = new ReviewImportService();

        $result = $service->import($place, [
            [
                'external_id' => 'r-1',
                'author_name' => 'Updated',
                'text' => 'Updated text',
                'rating' => 5,
                'has_owner_reply' => true,
            ],
        ]);

        $review = Review::query()
            ->where('place_id', $place->id)
            ->where('external_id', 'r-1')
            ->firstOrFail();

        $this->assertSame(1, $result['reviews_fetched']);
        $this->assertSame(0, $result['reviews_created']);
        $this->assertSame(1, $result['reviews_updated']);
        $this->assertSame(1, Review::query()->where('place_id', $place->id)->count());
        $this->assertSame('Updated', $review->author_name);
        $this->assertSame('Updated text', $review->text);
        $this->assertSame(5, $review->rating);
        $this->assertTrue($review->has_owner_reply);
    }
}
