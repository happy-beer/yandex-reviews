<?php

namespace App\Services;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class YandexMapsClient
{
    private string $reviewsApiUrl = 'https://yandex.ru/maps/api/business/fetchReviews';

    public function extractFromOrgPage(string $orgUrl, Request $request): array
    {
        $mock = (bool) env('YANDEX_MOCK', false);

        if ($mock) {
            $html = file_get_contents(storage_path('app/mock/yandex_reviews.html'));
            $jarArray = [];
        } else {
            $jar = new CookieJar();

            $resp = Http::withOptions(['cookies' => $jar])
                ->withHeaders($this->baseHeaders($request) + [
                        'Referer' => 'https://yandex.com.ge/maps/',
                    ])
                ->get($orgUrl);

            $html = $resp->body();
            $jarArray = $jar->toArray();
        }

        $state = $this->extractStateViewJson($html);

        return [
            'businessId'  => data_get($state, 'config.landing.orgId', ''),
            'csrfToken'   => data_get($state, 'config.csrfToken', ''),
            'sessionId'   => data_get($state, 'config.counters.analytics.sessionId', ''),
            'reqId'       => data_get($state, 'config.requestId', ''),
            'name'        => $this->extractName($state),
            'rating'      => $this->extractRatingText($state),
            'reviewCount' => data_get($state, 'stack.0.results.items.0.ratingData.reviewCount', ''),
            'cookies'     => $jarArray,
        ];
    }

    public function fetchReviews(array $params, array $cookies, Request $request, int $try = 0): array
    {
        $mock = (bool) env('YANDEX_MOCK', false);

        $params['s'] = $this->makeHash($params);

        if ($mock) {
            return $this->mockReviews($params, $cookies);
        }

        $jar = new CookieJar(false, $cookies);

        $resp = Http::withOptions(['cookies' => $jar])
            ->withHeaders($this->baseHeaders($request) + [
                    'Referer' => 'https://yandex.com.ge/maps/org/' . ($params['businessId'] ?? '') . '/reviews/',
                ])
            ->get($this->reviewsApiUrl, $params);

        $newCookies = $jar->toArray();
        $data = json_decode($resp->body(), true) ?: [];


        if (isset($data['csrfToken']) && $try < 2) {
            $params['csrfToken'] = $data['csrfToken'];
            unset($params['s']);
            return $this->fetchReviews($params, $newCookies, $request, $try + 1);
        }

        return [
            'cookie'    => serialize($newCookies),
            'csrfToken' => $params['csrfToken'] ?? '',
            'reviews'   => $data['data']['reviews'] ?? [],
            'params'    => $data['data']['params'] ?? [],
        ];
    }

    private function baseHeaders(Request $request): array
    {
        return [
            'User-Agent'        => $request->header('User-Agent', ''),
            'Accept'            => 'application/json,text/javascript,*/*;q=0.01',
            'Accept-Language'   => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            'X-Requested-With'  => 'XMLHttpRequest',
            'Origin'            => 'https://yandex.com.ge/',
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
            'cookie'    => serialize($cookies),
            'csrfToken' => (string)($params['csrfToken'] ?? ''),
            'reviews'   => $pageItems,
            'params'    => [
                'page' => $page,
                'totalPages' => (int)ceil($totalReviews / $pageSize),
                'count' => $totalReviews,
                'pageSize' => $pageSize,
                'loadedReviewsCount' => count($pageItems),
            ],
        ];
    }
}
