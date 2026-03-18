<?php

namespace App\Services;

use App\Exceptions\YandexProviderException;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\RequestException;
use InvalidArgumentException;
use Throwable;

class YandexMapsClient
{
    private const MAX_RETRY_COUNT = 2;
    private string $reviewsApiUrl = 'https://yandex.ru/maps/api/business/fetchReviews';

    public function extractFromOrgPage(string $orgUrl, Request $request): array
    {
        try {
            $validator = Validator::make(
                [
                    'orgUrl' => $orgUrl,
                ],
                [
                    'orgUrl' => ['required', 'url'],
                ],
                [
                    'orgUrl.required' => 'URL ir required.',
                    'orgUrl.url' => 'URL must be valid URL.',
                ]
            );
            if ($validator->fails()) {
                throw new InvalidArgumentException($validator->errors()->first());
            }

            $mock = config('yandex.mock');

            if ($mock) {
                $html = file_get_contents(storage_path('app/mock/yandex_reviews.html'));
                $jarArray = [];
            } else {
                $jar = new CookieJar();

                $resp = Http::timeout(10)->withOptions(['cookies' => $jar])
                    ->withHeaders($this->baseHeaders($request) + [
                            'Referer' => 'https://yandex.com.ge/maps/',
                        ])
                    ->get($orgUrl);

                $resp->throw();

                $html = $resp->body();

                $jarArray = $jar->toArray();
            }

            $state = $this->extractStateViewJson($html);

            return [
                'businessId' => data_get($state, 'config.landing.orgId', ''),
                'csrfToken' => data_get($state, 'config.csrfToken', ''),
                'sessionId' => data_get($state, 'config.counters.analytics.sessionId', ''),
                'reqId' => data_get($state, 'config.requestId', ''),
                'name' => $this->extractName($state),
                'rating' => $this->extractRatingText($state),
                'reviewCount' => data_get($state, 'stack.0.results.items.0.ratingData.reviewCount', ''),
                'cookies' => $jarArray,
            ];
        } catch (Throwable $e) {
            $this->rethrowAsYandexException($e);
        }
    }

    public function fetchReviews(array $params, array $cookies, Request $request, YandexSessionStore $sessionStore, int $try = 0): array
    {
        try {
            $validator = Validator::make($params, [
                'csrfToken'  => ['required','string'],
                'sessionId'  => ['required','string'],
                'businessId' => ['required'],
                'page'       => ['required','integer','min:1'],
                'pageSize'   => ['required','integer','min:1'],
                'reqId'      => ['required','string'],
                'ranking'    => ['required','string'],
                'locale'     => ['required','string'],
                'ajax'       => ['required'],
            ]);

            if ($validator->fails()) {
                throw new InvalidArgumentException($validator->errors()->first());
            }

            $mock = config('yandex.mock');

            $params['s'] = $this->makeHash($params);

            if ($mock) {
                return $this->mockReviews($params, $cookies);
            }

            $jar = new CookieJar(false, $cookies);

            $resp = Http::timeout(10)->withOptions(['cookies' => $jar])
                ->withHeaders($this->baseHeaders($request) + [
                        'Referer' => 'https://yandex.com.ge/maps/org/' . ($params['businessId'] ?? '') . '/reviews/',
                    ])
                ->get($this->reviewsApiUrl, $params);

            $resp->throw();

            $newCookies = $jar->toArray();
            $data = json_decode($resp->body(), true) ?: [];

            $sessionStore->putPartial($request, $params);

            if (isset($data['csrfToken']) && $try < self::MAX_RETRY_COUNT) {
                $params['csrfToken'] = $data['csrfToken'];
                unset($params['s']);
                return $this->fetchReviews($params, $newCookies, $request, $sessionStore, $try + 1);
            }


            return [
                'reviews' => $data['data']['reviews'] ?? [],
                'params' => $data['data']['params'] ?? [],
            ];
        } catch (Throwable $e) {
            $this->rethrowAsYandexException($e);
        }
    }

    private function rethrowAsYandexException(Throwable $e): never
    {
        Log::error($e);

        if ($e instanceof InvalidArgumentException) {
            throw new YandexProviderException($e->getMessage(), 0, $e);
        }

        if ($e instanceof RequestException) {
            throw new YandexProviderException('Yandex provider request failed.', 0, $e);
        }

        throw new YandexProviderException('Internal Yandex integration error.', 0, $e);
    }

    private function baseHeaders(Request $request): array
    {
        return [
            'User-Agent' => $request->header('User-Agent', ''),
            'Accept' => 'application/json,text/javascript,*/*;q=0.01',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            'X-Requested-With' => 'XMLHttpRequest',
            'Origin' => 'https://yandex.com.ge/',
        ];
    }

    private function extractStateViewJson(string $html): array
    {
        if (preg_match('/<script type="application\/json" class="state-view">(.*?)<\/script>/s', $html, $m)) {
            $json = $m[1];
            $data = json_decode($json, true);
            return is_array($data) ? $data : [];
        }
        return [];
    }

    private function extractName(array $state): string
    {
        $crumbs = data_get($state, 'stack.0.meta.breadcrumbs', []);
        if (!is_array($crumbs) || empty($crumbs)) return '';
        $last = end($crumbs);
        return is_array($last) ? ($last['name'] ?? '') : '';
    }

    private function extractRatingText(array $state): string
    {
        $hints = data_get($state, 'stack.0.results.items.0.modularPin.subtitleHints', []);
        if (!is_array($hints)) return '';
        foreach ($hints as $hint) {
            if (($hint['type'] ?? null) === 'RATING') {
                return (string)($hint['text'] ?? '');
            }
        }
        return '';
    }

    private function makeHash(array $params): string
    {
        unset($params['s']);

        foreach ($params as $k => $v) {
            $params[$k] = (string)$v;
        }

        ksort($params, SORT_STRING);

        $qs = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        if ($qs === '') return '';

        return (string)$this->djb2XorHash32($qs);
    }

    private function djb2XorHash32(string $s): int
    {
        $n = 5381;
        $len = strlen($s);

        for ($i = 0; $i < $len; $i++) {
            $n = (($n * 33) ^ ord($s[$i])) & 0xFFFFFFFF;
        }

        return $n < 0 ? $n + 4294967296 : $n;
    }

    private function mockReviews(array $params, array $cookies): array
    {
        $totalReviews = 50;
        $page = (int)($params['page'] ?? 1);
        $pageSize = (int)($params['pageSize'] ?? 50);
        $offset = ($page - 1) * $pageSize;

        $all = [];
        for ($i = 1; $i <= $totalReviews; $i++) {
            $all[] = [
                'reviewId' => "mock-$i",
                'author' => ['name' => "Пользователь $i"],
                'text' => "Это тестовый отзыв номер $i...",
                'rating' => rand(3, 5),
                'updatedTime' => now()->subDays($i)->toISOString(),
                'photos' => [],
                'videos' => [],
            ];
        }

        $pageItems = array_slice($all, $offset, $pageSize);

        return [
            'reviews' => $pageItems,
            'params' => [
                'page' => $page,
                'totalPages' => (int)ceil($totalReviews / $pageSize),
                'count' => $totalReviews,
                'pageSize' => $pageSize,
                'loadedReviewsCount' => count($pageItems),
            ],
        ];
    }
}
