<?php

namespace Tests\Feature;

use App\Exceptions\YandexProviderException;
use App\Models\User;
use App\Services\YandexMapsClient;
use App\Services\YandexSessionStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_without_yandex_setting(): void
    {
        $user = User::factory()->create([
            'id' => 1,
        ]);

        $this->actingAs($user);

        $this->mock(\App\Models\Setting::class, function ($mock) {
            $mock->shouldReceive('where->where->first')
                ->andReturn(null);
        });

        $response = $this->get('/');

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('success', true)
            ->where('message', '')
        );
    }

    public function test_fetches_yandex_data_when_setting_exists(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        \App\Models\Setting::create([
            'user_id' => $user->id,
            'key' => 'yandex_url',
            'value' => 'https://yandex.com.ge/maps/org/test/reviews/',
        ]);

        $this->mock(YandexSessionStore::class, function ($mock) {
            $mock->shouldReceive('get')->once()->andReturn([
                'businessId' => '',
                'csrfToken' => '',
                'sessionId' => '',
                'reqId' => '',
                'rating' => '',
                'name' => '',
                'reviewCount' => '',
                'cookies' => [],
            ]);

            $mock->shouldReceive('hasRequiredData')->once()->andReturn(false);
            $mock->shouldReceive('put')->once();
        });

        $mockData = [
            'businessId' => '123',
            'csrfToken' => 'token',
            'sessionId' => 'session',
            'reqId' => 'req',
            'name' => 'Test Org',
            'rating' => '4.9',
            'reviewCount' => '120',
            'cookies' => [],
        ];

        $this->mock(YandexMapsClient::class, function ($mock) use ($mockData) {
            $mock->shouldReceive('extractFromOrgPage')
                ->once()
                ->andReturn($mockData);
        });

        $response = $this->get('/');

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('name', 'Test Org')
            ->where('rating', '4.9')
            ->where('reviewCount', '120')
        );
    }

    public function test_returns_error_when_yandex_service_fails(): void
    {

        $user = User::factory()->create();

        $this->actingAs($user);

        \App\Models\Setting::create([
            'user_id' => $user->id,
            'key' => 'yandex_url',
            'value' => 'https://yandex.com.ge/maps/org/test/reviews/',
        ]);

        $this->mock(YandexMapsClient::class, function ($mock) {
            $mock->shouldReceive('extractFromOrgPage')
                ->once()
                ->andThrow(new YandexProviderException('Yandex failed'));
        });

        $response = $this->withSession([])->get('/');

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('success', false)
            ->where('message', 'Yandex failed')
        );
    }
}
