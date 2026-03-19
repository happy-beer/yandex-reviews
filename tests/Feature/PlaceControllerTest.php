<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Review;
use App\Models\SyncRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PlaceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_only_own_places_in_index(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownPlace = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Own place',
            'source_url' => 'https://yandex.ru/maps/org/own/1/reviews/',
        ]);

        Place::query()->create([
            'user_id' => $otherUser->id,
            'name' => 'Foreign place',
            'source_url' => 'https://yandex.ru/maps/org/foreign/2/reviews/',
        ]);

        $response = $this->actingAs($user)->get(route('places.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Places/Index')
            ->has('places.data', 1)
            ->where('places.data.0.id', $ownPlace->id)
        );
    }

    public function test_show_returns_place_with_reviews_and_sync_history(): void
    {
        $user = User::factory()->create();
        $place = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Show place',
            'source_url' => 'https://yandex.ru/maps/org/show/3/reviews/',
        ]);

        Review::query()->create([
            'place_id' => $place->id,
            'external_id' => 'r-1',
            'author_name' => 'Nika',
            'text' => 'Nice place',
            'rating' => 5,
            'published_at' => now(),
        ]);

        SyncRun::query()->create([
            'place_id' => $place->id,
            'status' => 'success',
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
            'reviews_fetched' => 1,
            'reviews_created' => 1,
            'reviews_updated' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('places.show', $place));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Places/Show')
            ->where('place.id', $place->id)
            ->has('reviews.data', 1)
            ->has('syncRuns.data', 1)
        );
    }

    public function test_user_can_store_update_and_delete_own_place(): void
    {
        $user = User::factory()->create();

        $storeResponse = $this->actingAs($user)->post(route('places.store'), [
            'name' => 'Created place',
            'source_url' => 'https://yandex.ru/maps/org/create/4/reviews/',
            'is_active' => true,
        ]);

        $place = Place::query()->where('user_id', $user->id)->firstOrFail();

        $storeResponse
            ->assertRedirect(route('places.show', $place))
            ->assertSessionHas('success', 'Organization created successfully.');

        $updateResponse = $this->actingAs($user)->put(route('places.update', $place), [
            'name' => 'Updated place',
            'source_url' => 'https://yandex.ru/maps/org/update/5/reviews/',
            'is_active' => false,
        ]);

        $updateResponse
            ->assertRedirect(route('places.show', $place))
            ->assertSessionHas('success', 'Organization updated successfully.');

        $place->refresh();
        $this->assertSame('Updated place', $place->name);
        $this->assertFalse($place->is_active);

        $deleteResponse = $this->actingAs($user)->delete(route('places.destroy', $place));
        $deleteResponse
            ->assertRedirect(route('places.index'))
            ->assertSessionHas('success', 'Organization deleted successfully.');

        $this->assertDatabaseMissing('places', ['id' => $place->id]);
    }

    public function test_user_cannot_edit_or_delete_foreign_place(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $place = Place::query()->create([
            'user_id' => $owner->id,
            'name' => 'Foreign place',
            'source_url' => 'https://yandex.ru/maps/org/foreign/6/reviews/',
        ]);

        $this->actingAs($otherUser)
            ->get(route('places.edit', $place))
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->delete(route('places.destroy', $place))
            ->assertForbidden();
    }

    public function test_user_cannot_update_foreign_place(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $place = Place::query()->create([
            'user_id' => $owner->id,
            'name' => 'Owner place',
            'source_url' => 'https://yandex.ru/maps/org/owner/7/reviews/',
            'is_active' => true,
        ]);

        $this->actingAs($otherUser)
            ->put(route('places.update', $place), [
                'name' => 'Hacked name',
                'source_url' => 'https://yandex.ru/maps/org/hacked/7/reviews/',
                'is_active' => false,
            ])
            ->assertForbidden();

        $place->refresh();
        $this->assertSame('Owner place', $place->name);
        $this->assertTrue($place->is_active);
    }
}
