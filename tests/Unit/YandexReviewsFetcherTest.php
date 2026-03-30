<?php

namespace Tests\Unit;

use App\Models\Place;
use App\Services\Yandex\YandexReviewsFetcher;
use App\Services\YandexMapsClient;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use Tests\TestCase;

class YandexReviewsFetcherTest extends TestCase
{
    public function test_fetch_loads_all_pages_and_calls_extract_once(): void
    {
        $request = Request::create('/sync', 'POST');
        $place = new Place([
            'source_url' => 'https://yandex.ru/maps/org/test/123/reviews/',
        ]);

        $capturedPages = [];

        $client = $this->mock(YandexMapsClient::class, function (MockInterface $mock) use (&$capturedPages, $request) {
            $mock->shouldReceive('extractFromOrgPage')
                ->once()
                ->andReturn([
                    'csrfToken' => 'csrf',
                    'sessionId' => 'session',
                    'businessId' => 'biz-1',
                    'reqId' => 'req-1',
                    'name' => 'Test Place',
                    'rating' => '4.7',
                    'reviewCount' => '5',
                    'cookies' => [['Name' => 'cookie', 'Value' => '1']],
                ]);

            $mock->shouldReceive('fetchReviews')
                ->times(3)
                ->andReturnUsing(function (array $params, array $cookies, Request $incomingRequest) use (&$capturedPages, $request) {
                    $capturedPages[] = (int) $params['page'];
                    $this->assertSame('csrf', $params['csrfToken']);
                    $this->assertSame('session', $params['sessionId']);
                    $this->assertSame('biz-1', $params['businessId']);
                    $this->assertSame($request, $incomingRequest);
                    $this->assertNotEmpty($cookies);

                    if ($params['page'] === 1) {
                        return [
                            'reviews' => [['reviewId' => 'r-1'], ['reviewId' => 'r-2']],
                            'params' => [
                                'page' => 1,
                                'totalPages' => 3,
                                'count' => 5,
                                'pageSize' => 2,
                            ],
                        ];
                    }

                    if ($params['page'] === 2) {
                        return [
                            'reviews' => [['reviewId' => 'r-3'], ['reviewId' => 'r-4']],
                            'params' => [
                                'page' => 2,
                                'totalPages' => 3,
                                'count' => 5,
                                'pageSize' => 2,
                            ],
                        ];
                    }

                    return [
                        'reviews' => [['reviewId' => 'r-5']],
                        'params' => [
                            'page' => 3,
                            'totalPages' => 3,
                            'count' => 5,
                            'pageSize' => 2,
                        ],
                    ];
                });
        });

        $fetcher = new YandexReviewsFetcher($client);
        $result = $fetcher->fetch($place, $request, 1, 2);

        $this->assertSame([1, 2, 3], $capturedPages);
        $this->assertSame('biz-1', $result['place']['external_id']);
        $this->assertCount(5, $result['reviews']);
        $this->assertSame('r-1', $result['reviews'][0]['reviewId']);
        $this->assertSame('r-5', $result['reviews'][4]['reviewId']);
        $this->assertSame(3, $result['params']['totalPages']);
    }
}
