<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Dashboard\DashboardStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_stats_from_service(): void
    {
        $user = User::factory()->create();

        $mockStats = [
            'places_count' => 3,
            'reviews_count' => 42,
            'average_rating' => 4.7,
            'last_synced_at' => now()->toDateTimeString(),
        ];

        $this->mock(DashboardStatsService::class, function (MockInterface $mock) use ($mockStats, $user) {
            $mock->shouldReceive('forUser')
                ->once()
                ->with($user->id)
                ->andReturn($mockStats);
        });

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.places_count', 3)
            ->where('stats.reviews_count', 42)
            ->where('stats.average_rating', 4.7)
        );
    }
}
