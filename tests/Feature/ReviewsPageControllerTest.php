<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReviewsPageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviews_page_returns_only_reviews_for_current_user_places(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $userPlace = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'User place',
            'source_url' => 'https://yandex.ru/maps/org/user/10/reviews/',
            'is_active' => true,
        ]);

        $otherPlace = Place::query()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other place',
            'source_url' => 'https://yandex.ru/maps/org/other/11/reviews/',
            'is_active' => true,
        ]);

        $userReview = Review::query()->create([
            'place_id' => $userPlace->id,
            'external_id' => 'u-1',
            'author_name' => 'User author',
            'text' => 'Coffee is good',
            'rating' => 5,
            'published_at' => now()->subHour(),
        ]);

        Review::query()->create([
            'place_id' => $otherPlace->id,
            'external_id' => 'o-1',
            'author_name' => 'Other author',
            'text' => 'Should not be visible',
            'rating' => 1,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reviews.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reviews/Index')
            ->has('places', 1)
            ->has('reviews.data', 1)
            ->where('reviews.data.0.id', $userReview->id)
        );
    }

    public function test_reviews_page_applies_filters_from_query_string(): void
    {
        $user = User::factory()->create();

        $place = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Filter place',
            'source_url' => 'https://yandex.ru/maps/org/filter/12/reviews/',
            'is_active' => true,
        ]);

        Review::query()->create([
            'place_id' => $place->id,
            'external_id' => 'r-1',
            'author_name' => 'Anna',
            'text' => 'Excellent coffee',
            'rating' => 5,
            'published_at' => now()->subDays(3),
        ]);

        $matchingReview = Review::query()->create([
            'place_id' => $place->id,
            'external_id' => 'r-2',
            'author_name' => 'Boris',
            'text' => 'Excellent service',
            'rating' => 4,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('reviews.index', [
            'place_id' => $place->id,
            'rating' => 4,
            'search' => 'service',
            'sort' => 'newest',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reviews/Index')
            ->where('filters.place_id', $place->id)
            ->where('filters.rating', 4)
            ->where('filters.search', 'service')
            ->has('reviews.data', 1)
            ->where('reviews.data.0.id', $matchingReview->id)
        );
    }

    public function test_reviews_page_applies_rating_sorting_modes(): void
    {
        $user = User::factory()->create();

        $place = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Sort place',
            'source_url' => 'https://yandex.ru/maps/org/sort/13/reviews/',
            'is_active' => true,
        ]);

        $high = Review::query()->create([
            'place_id' => $place->id,
            'external_id' => 'sort-high',
            'author_name' => 'A',
            'text' => 'High rating',
            'rating' => 5,
            'published_at' => now()->subDays(2),
        ]);

        $low = Review::query()->create([
            'place_id' => $place->id,
            'external_id' => 'sort-low',
            'author_name' => 'B',
            'text' => 'Low rating',
            'rating' => 2,
            'published_at' => now()->subDay(),
        ]);

        $descResponse = $this->actingAs($user)->get(route('reviews.index', [
            'sort' => 'rating_desc',
        ]));

        $descResponse->assertOk();
        $descResponse->assertInertia(fn (Assert $page) => $page
            ->component('Reviews/Index')
            ->where('reviews.data.0.id', $high->id)
        );

        $ascResponse = $this->actingAs($user)->get(route('reviews.index', [
            'sort' => 'rating_asc',
        ]));

        $ascResponse->assertOk();
        $ascResponse->assertInertia(fn (Assert $page) => $page
            ->component('Reviews/Index')
            ->where('reviews.data.0.id', $low->id)
        );
    }

    public function test_reviews_pagination_links_keep_filters_in_query_string(): void
    {
        $user = User::factory()->create();

        $place = Place::query()->create([
            'user_id' => $user->id,
            'name' => 'Pagination place',
            'source_url' => 'https://yandex.ru/maps/org/pagination/14/reviews/',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 18; $i++) {
            Review::query()->create([
                'place_id' => $place->id,
                'external_id' => 'p-' . $i,
                'author_name' => 'Author ' . $i,
                'text' => 'Service filter ' . $i,
                'rating' => 4,
                'published_at' => now()->subMinutes($i),
            ]);
        }

        $response = $this->actingAs($user)->get(route('reviews.index', [
            'place_id' => $place->id,
            'rating' => 4,
            'search' => 'Service',
            'sort' => 'newest',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reviews/Index')
            ->where('reviews.links.next', function ($url) {
                $nextUrl = (string) $url;
                return str_contains($nextUrl, 'place_id=')
                    && str_contains($nextUrl, 'rating=4')
                    && str_contains($nextUrl, 'search=Service')
                    && str_contains($nextUrl, 'sort=newest');
            })
        );
    }
}
