<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EpicSevenRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_routes_require_authentication(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('reviews.index'))->assertRedirect(route('login'));
        $this->get(route('places.index'))->assertRedirect(route('login'));
    }

    public function test_core_routes_require_verified_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/')->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('reviews.index'))->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->get(route('places.index'))->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_can_access_core_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertRedirect('/dashboard');
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->get(route('reviews.index'))->assertOk();
        $this->actingAs($user)->get(route('places.index'))->assertOk();
    }

    public function test_legacy_reviews_api_endpoint_is_not_available(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/reviews')
            ->assertNotFound();
    }
}
