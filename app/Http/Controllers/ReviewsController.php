<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReviewsController extends Controller
{
    public function index(Request $request)
    {

        $data = $request->validate([
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
            "page" => $data['page'],
            "pageSize" => $data['pageSize'],
            "reqId" => $data['reqId'],
            "ranking" => 'by_time',
            "locale" => 'ru_UA',
            "ajax" => 1,
        ];

        $reviews = $this->loadReviews($params);

        return response()->json($reviews);
    }

    protected function loadReviews($params)
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
            $response = Http::get($url, $params)->body();
            $data = json_decode($response, true);
        }


        return [
            'reviews' => $data['data']['reviews']??[],
            'params' => $data['data']['params']??[],
        ];
    }

    protected function makeHash(array $params): string
    {
        ksort($params);

        $query = http_build_query($params);

        $hash = 5381;
        for ($i = 0, $len = strlen($query); $i < $len; $i++) {
            $hash = (($hash << 5) + $hash) ^ ord($query[$i]); // n * 33 ^ charCode
        }

        return (string) ($hash & 0xFFFFFFFF);
    }
}
