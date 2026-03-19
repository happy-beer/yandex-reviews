<?php

namespace Tests\Unit;

use App\Models\Place;
use App\Models\User;
use App\Policies\PlacePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlacePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_update_delete_and_sync_place(): void
    {
        $user = User::factory()->create();
        $place = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Owned place',
            'source_url' => 'https://yandex.ru/maps/org/owned/1/reviews/',
        ]);

        $policy = new PlacePolicy();

        $this->assertTrue($policy->view($user, $place));
        $this->assertTrue($policy->update($user, $place));
        $this->assertTrue($policy->delete($user, $place));
        $this->assertTrue($policy->sync($user, $place));
    }

    public function test_non_owner_cannot_view_update_delete_or_sync_place(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $place = Place::query()->create([
            'user_id' => $owner->id,
            'name' => 'Protected place',
            'source_url' => 'https://yandex.ru/maps/org/protected/2/reviews/',
        ]);

        $policy = new PlacePolicy();

        $this->assertFalse($policy->view($otherUser, $place));
        $this->assertFalse($policy->update($otherUser, $place));
        $this->assertFalse($policy->delete($otherUser, $place));
        $this->assertFalse($policy->sync($otherUser, $place));
    }
}
