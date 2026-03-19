<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\YandexMapsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_reviews_endpoint(): void
    {
        $response = $this->getJson('/api/reviews');

        $response->assertUnauthorized();
    }

    public function test_returns_422_when_yandex_session_data_is_missing(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/reviews');

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Yandex session data is missing.',
            ]);
    }

    public function test_returns_reviews_when_yandex_session_data_exists(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $mockResult = [
            'reviews' => [
                [
                    'reviewId' => 'r1',
                    'author' => ['name' => 'Test User'],
                    'text' => 'Test review',
                    'rating' => 5,
                ],
            ],
            'params' => [
                'page' => 1,
                'totalPages' => 3,
                'count' => 120,
                'pageSize' => 50,
                'loadedReviewsCount' => 1,
            ],
        ];

        $this->mock(YandexMapsClient::class, function ($mock) use ($mockResult) {
            $mock->shouldReceive('fetchReviews')
                ->once()
                ->andReturn($mockResult);
        });

        $response = $this
            ->withSession([
                'yandex.businessId' => '228563538814',
                'yandex.csrfToken' => 'token123',
                'yandex.sessionId' => 'session123',
                'yandex.reqId' => 'req123',
                'yandex.cookies' => [],
            ])
            ->getJson('/api/reviews?page=1&pageSize=50');

        $response
            ->assertOk()
            ->assertJson($mockResult);
    }
}
