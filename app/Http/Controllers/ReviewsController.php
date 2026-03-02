<?php

namespace App\Http\Controllers;

use App\Services\YandexMapsClient;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReviewsController extends Controller
{
    public function index(Request $request, YandexMapsClient $yandex)
    {

        $data = $request->validate([
            'cookie' => 'sometimes|string',
            'businessId' => 'required|string',
            'csrfToken' => 'required|string',
            'sessionId' => 'required|string',
            'reqId' => 'required|string',
            'page' => 'sometimes|integer|min:1',
            'pageSize' => 'sometimes|integer|min:1',
        ]);


        $params = [
            "csrfToken" => $data['csrfToken'],
            "sessionId" => $data['sessionId'],
            "businessId" => $data['businessId'],
            "page" => $data['page']??1,
            "pageSize" => $data['pageSize']??config('reviews.per_page'),
            "reqId" => $data['reqId'],
            "ranking" => 'by_time',
            "locale" => 'ru_UA',
            "ajax" => 1,
        ];

        $cookies = !empty($data['cookie']) ? (unserialize($data['cookie']) ?: []) : [];
        $result = $yandex->fetchReviews($params, $cookies, $request);

        return response()->json($result);
    }

    protected function loadReviews($params, $cookies, Request $request, $try = 0)
    {
        $params['s'] = $this->makeHash($params);

        $url = 'https://yandex.ru/maps/api/business/fetchReviews';

        $mock = env('YANDEX_MOCK', false);

        if ($mock) {
            $totalReviews = 50; // общее количество фиктивных отзывов
            $page = $params['page'] ?? 1;
            $pageSize = $params['pageSize'];
            $offset = ($page - 1) * $pageSize;

            $allReviews = [];
            for ($i = 1; $i <= $totalReviews; $i++) {
                $allReviews[] = [
                    'reviewId' => "mock-$i",
                    'author' => ['name' => "Пользователь $i"],
                    'text' => "Это тестовый отзыв номер $i. Так, с чего начать... Разнообразная алкогольная продукция, множество закусок и обычных блюд. Кухня вкусная и
Разнообразная, от супа и салатов до мясных продуктов. Персонал молодые девушки, общительная и доброжелательные,
всегда подскажут, вовремя принесут и вызовут такси. Отдыхали на летней веранде, свежо и тепло, в общем самое то в
жаркую погоду. Сами залы не сильно рассмотрел, но видел что они удобные и просторные. ",
                    'rating' => rand(3, 5),
                    'updatedTime' => now()->subDays($i)->toISOString(),
                    'photos' => [],
                    'videos' => [],
                ];
            }

            $reviewsPage = array_slice($allReviews, $offset, $pageSize);

            $data = [
                'data' => [
                    'reviews' => $reviewsPage,
                    'params' => [
                        'page' => $page,
                        'totalPages' => ceil($totalReviews / $pageSize),
                        'count' => $totalReviews,
                        'pageSize' => $pageSize,
                        'loadedReviewsCount' => count($reviewsPage),
                    ]
                ]
            ];
        } else {
            $jar = new CookieJar(false, $cookies);

            $resp = Http::withOptions(['cookies' => $jar])->withHeaders([
                'User-Agent' => $request->header('User-Agent'),
                'Accept' => 'application/json,text/javascript,*/*;q=0.01',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'X-Requested-With' => 'XMLHttpRequest',
                'Origin' => 'https://yandex.com.ge/',
                'Referer' => 'https://yandex.com.ge/maps/org/'.$params['businessId'].'/reviews/',
            ])->get($url, $params);

            $cookies = $jar->toArray();

            $response = $resp->body();
            $data = json_decode($response, true);
            if(isset($data['csrfToken']) && $try < 2) {
                unset($params['s']);
                $params['csrfToken'] = $data['csrfToken'];
                $try++;
                return $this->loadReviews($params, $cookies, $request, $try);
            }
        }


        return [
            'cookie' => serialize($cookies),
            'csrfToken' => $params['csrfToken'],
            'reviews' => $data['data']['reviews']??[],
            'params' => $data['data']['params']??[],
        ];
    }

    private function makeHash(array $params): string
    {
        unset($params['s']);

        foreach ($params as $k => $v) {
            $params[$k] = (string)$v;
        }

        ksort($params, SORT_STRING);

        $qs = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        if ($qs === '') {
            return '';
        }

        return (string)$this->djb2XorHash32($qs);
    }

    private function djb2XorHash32(string $s): int
    {
        $n = 5381;
        $len = strlen($s);

        for ($i = 0; $i < $len; $i++) {
            $n = (($n * 33) ^ ord($s[$i])) & 0xFFFFFFFF;
        }

        // unsigned like >>> 0
        return $n < 0 ? $n + 4294967296 : $n;
    }
}
