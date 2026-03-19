<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EpicThirteenCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_is_placeholder_and_does_not_require_legacy_settings_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Settings/Index'));
    }

    public function test_settings_update_endpoint_is_removed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/settings', [
                'settings' => [
                    ['key' => 'yandex_url', 'value' => 'https://yandex.ru/maps/org/test/reviews/'],
                ],
            ])
            ->assertStatus(405);
    }
}
