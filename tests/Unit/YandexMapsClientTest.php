<?php

namespace Tests\Unit;

use App\Exceptions\YandexProviderException;
use App\Services\YandexMapsClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YandexMapsClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        cache()->clear();
        config([
            'yandex.reviews_cache_lifetime' => 0,
            'yandex.mock' => false,
        ]);
    }

    private function makeRequest(): Request
    {
        $request = Request::create('/');
        $request->headers->set('User-Agent', 'PHPUnit');

        return $request;
    }

    public function test_extract_from_org_page_returns_expected_data(): void
    {
        $html = <<<'HTML'
<html>
<head></head>
<body>
<script type="application/json" class="state-view">
{
  "config": {
    "landing": {
      "orgId": "228563538814"
    },
    "csrfToken": "csrf-token",
    "counters": {
      "analytics": {
        "sessionId": "session-id"
      }
    },
    "requestId": "req-id"
  },
  "stack": [
    {
      "meta": {
        "breadcrumbs": [
          {"name": "Главная"},
          {"name": "Smoke BBQ"}
        ]
      },
      "results": {
        "items": [
          {
            "modularPin": {
              "subtitleHints": [
                {"type": "RATING", "text": "4.8"}
              ]
            },
            "ratingData": {
              "reviewCount": "245"
            }
          }
        ]
      }
    }
  ]
}
</script>
</body>
</html>
HTML;

        Http::fake([
            '*' => Http::response($html, 200),
        ]);

        $service = new YandexMapsClient();

        $result = $service->extractFromOrgPage(
            'https://yandex.com.ge/maps/org/smoke_bbq/228563538814/reviews/',
            $this->makeRequest()
        );

        $this->assertSame('228563538814', $result['businessId']);
        $this->assertSame('csrf-token', $result['csrfToken']);
        $this->assertSame('session-id', $result['sessionId']);
        $this->assertSame('req-id', $result['reqId']);
        $this->assertSame('Smoke BBQ', $result['name']);
        $this->assertSame('4.8', $result['rating']);
        $this->assertSame('245', $result['reviewCount']);
        $this->assertIsArray($result['cookies']);
    }

    public function test_extract_from_org_page_throws_exception_for_invalid_url(): void
    {
        $service = new YandexMapsClient();

        $this->expectException(YandexProviderException::class);
        $this->expectExceptionMessage('URL must be valid URL.');

        $service->extractFromOrgPage(
            'not-a-valid-url',
            $this->makeRequest()
        );
    }

    public function test_fetch_reviews_returns_reviews_data(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    'reviews' => [
                        [
                            'reviewId' => 'r1',
                            'author' => ['name' => 'Test User'],
                            'text' => 'Great place',
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
                ],
            ], 200),
        ]);

        $service = new YandexMapsClient();

        $params = [
            'csrfToken' => 'csrf-token',
            'sessionId' => 'session-id',
            'businessId' => '228563538814',
            'page' => 1,
            'pageSize' => 50,
            'reqId' => 'req-id',
            'ranking' => 'by_time',
            'locale' => 'ru_UA',
            'ajax' => 1,
        ];

        $result = $service->fetchReviews($params, [], $this->makeRequest());

        $this->assertCount(1, $result['reviews']);
        $this->assertSame('r1', $result['reviews'][0]['reviewId']);
        $this->assertSame(1, $result['params']['page']);
        $this->assertSame(120, $result['params']['count']);
    }

    public function test_fetch_reviews_retries_when_api_returns_new_csrf_token(): void
    {
        Http::fakeSequence()
            ->push([
                'csrfToken' => 'new-csrf-token',
            ], 200)
            ->push([
                'data' => [
                    'reviews' => [
                        [
                            'reviewId' => 'r2',
                            'author' => ['name' => 'Retry User'],
                            'text' => 'Retry worked',
                            'rating' => 4,
                        ],
                    ],
                    'params' => [
                        'page' => 1,
                        'totalPages' => 1,
                        'count' => 1,
                        'pageSize' => 50,
                        'loadedReviewsCount' => 1,
                    ],
                ],
            ], 200);

        $service = new YandexMapsClient();

        $params = [
            'csrfToken' => 'old-csrf-token',
            'sessionId' => 'session-id',
            'businessId' => '228563538814',
            'page' => 1,
            'pageSize' => 50,
            'reqId' => 'req-id',
            'ranking' => 'by_time',
            'locale' => 'ru_UA',
            'ajax' => 1,
        ];

        $result = $service->fetchReviews($params, [], $this->makeRequest());

        Http::assertSentCount(2);

        $this->assertSame('r2', $result['reviews'][0]['reviewId']);
        $this->assertSame(1, $result['params']['count']);
    }
}
