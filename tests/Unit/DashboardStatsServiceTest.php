<?php

namespace Tests\Unit;

use App\Models\Place;
use App\Models\Review;
use App\Models\User;
use App\Services\Dashboard\DashboardStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_zero_stats_for_user_without_places(): void
    {
        $user = User::factory()->create();
        $service = new DashboardStatsService();

        $stats = $service->forUser($user->id);

        $this->assertSame(0, $stats['places_count']);
        $this->assertSame(0, $stats['reviews_count']);
        $this->assertNull($stats['average_rating']);
        $this->assertNull($stats['last_synced_at']);
    }

    public function test_returns_aggregates_only_for_current_user_places(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $firstPlace = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'First',
            'source_url' => 'https://yandex.ru/maps/org/first/1/reviews/',
            'rating' => 4.0,
            'last_synced_at' => now()->subDay(),
        ]);

        $secondPlace = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Second',
            'source_url' => 'https://yandex.ru/maps/org/second/2/reviews/',
            'rating' => 5.0,
            'last_synced_at' => now(),
        ]);

        $foreignPlace = Place::query()->create([
            'user_id' => $otherUser->id,
            'name' => 'Foreign',
            'source_url' => 'https://yandex.ru/maps/org/foreign/3/reviews/',
            'rating' => 1.0,
            'last_synced_at' => now()->addHour(),
        ]);

        Review::query()->create([
            'place_id' => $firstPlace->id,
            'external_id' => 'r-1',
            'rating' => 5,
            'text' => 'A',
        ]);

        Review::query()->create([
            'place_id' => $secondPlace->id,
            'external_id' => 'r-2',
            'rating' => 4,
            'text' => 'B',
        ]);

        Review::query()->create([
            'place_id' => $foreignPlace->id,
            'external_id' => 'r-3',
            'rating' => 1,
            'text' => 'C',
        ]);

        $service = new DashboardStatsService();
        $stats = $service->forUser($user->id);

        $this->assertSame(2, $stats['places_count']);
        $this->assertSame(2, $stats['reviews_count']);
        $this->assertEquals(4.5, (float) $stats['average_rating']);
        $this->assertSame($secondPlace->last_synced_at->toDateTimeString(), (string) $stats['last_synced_at']);
    }
}
