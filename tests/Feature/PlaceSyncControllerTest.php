<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\User;
use App\Services\Places\PlaceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class PlaceSyncControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_trigger_sync_and_get_success_flash_message(): void
    {
        $user = User::factory()->create();

        $place = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Owned place',
            'source_url' => 'https://yandex.ru/maps/org/owned/20/reviews/',
        ]);

        $this->mock(PlaceSyncService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sync')
                ->once()
                ->andReturn([
                    'status' => 'success',
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
}
