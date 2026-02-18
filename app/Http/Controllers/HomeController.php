<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;
use App\Models\Setting;
use Nette\Utils\Arrays;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $setting = Setting::where('user_id', $userId)
            ->where('key', 'yandex_url')
            ->first();

        $session = $request->session();

        $businessId = $session->get('yandex.businessId') ?? '';
        $csrfToken = $session->get('yandex.csrfToken') ?? '';
        $sessionId = $session->get('yandex.sessionId') ?? '';
        $reqId = $session->get('yandex.reqId') ?? '';
        $rating = $session->get('yandex.rating') ?? '';
        $name = $session->get('yandex.name') ?? '';
        $reviewCount = $session->get('yandex.reviewCount') ?? '';

        if (!$businessId || !$csrfToken || !$sessionId || !$reqId) {
            if ($setting && $setting->value) {
                $url = $setting->value;

                try {

                    $mock = env('YANDEX_MOCK', false);

                    $html = $mock
                        ? file_get_contents(storage_path('app/mock/yandex_reviews.html'))
                        : Http::withHeaders([
                            'User-Agent' => $request->header('User-Agent'),
                            'Accept' => 'application/json,text/javascript,*/*;q=0.01',
                            'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                            'X-Requested-With' => 'XMLHttpRequest',
                            'Origin' => 'https://yandex.com.ge/',
                            'Referer' => 'https://yandex.com.ge/maps/',

                        ])->get($url)->body();

                    if (preg_match('/<script type="application\/json" class="state-view">(.*?)<\/script>/s', $html, $matches)) {
                        $json = $matches[1];
                        $data = json_decode($json, true);
                        if (isset($data['config']['landing']['orgId'])) {
                            $session->put('yandex.businessId', $data['config']['landing']['orgId']);
                            $businessId = $data['config']['landing']['orgId'];
                        } else {
                            \Log::error('Отутствует параметр orgId для ссылки' . $url);
                        }
                        if (isset($data['config']['csrfToken'])) {
                            $session->put('yandex.csrfToken', $data['config']['csrfToken']);
                            $csrfToken = $data['config']['csrfToken'];
                        } else {
                            \Log::error('Отутствует параметр csrfToken для ссылки' . $url);
                        }
                        if (isset($data['config']['counters']['analytics']['sessionId'])) {
                            $session->put('yandex.sessionId', $data['config']['counters']['analytics']['sessionId']);
                            $sessionId = $data['config']['counters']['analytics']['sessionId'];
                        } else {
                            \Log::error('Отутствует параметр sessionId для ссылки' . $url);
                        }
                        if (isset($data['config']['requestId'])) {
                            $session->put('yandex.reqId', $data['config']['requestId']);
                            $reqId = $data['config']['requestId'];
                        } else {
                            \Log::error('Отутствует параметр requestId для ссылки' . $url);
                        }
                        if (isset($data['stack'][0]['meta']['breadcrumbs'])) {
                            $name = Arrays::last($data['stack'][0]['meta']['breadcrumbs'])['name'];
                            $session->put('yandex.name', $name);
                        } else {
                            \Log::error('Отутствует параметр name для ссылки' . $url);
                        }
                        if (isset($data['stack'][0]['results']['items'][0]['modularPin']['subtitleHints'])) {
                            $rating = Arrays::filter($data['stack'][0]['results']['items'][0]['modularPin']['subtitleHints'],
                                function ($value, $key) {
                                    return $value['type'] == 'RATING';
                                }
                            );
                            $rating = $rating[0]['text'];
                            $session->put('yandex.rating', $rating ?? '');
                        } else {
                            \Log::error('Отутствует параметр rating для ссылки' . $url);
                        }

                        if (isset($data['stack'][0]['results']['items'][0]['ratingData']['reviewCount'])) {
                            $reviewCount = $data['stack'][0]['results']['items'][0]['ratingData']['reviewCount'];
                            $session->put('yandex.reviewCount', $reviewCount);
                        } else {
                            \Log::error('Отутствует параметр reviewCount для ссылки' . $url);
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Ошибка получения параметров запроса: ' . $e->getMessage());
                }
            }
        }

        return Inertia::render('Home', [
            'settings' => $setting,
            'reqData' => [
                'businessId' => $businessId,
                'csrfToken' => $csrfToken,
                'sessionId' => $sessionId,
                'reqId' => $reqId,
            ],
            'pageSize' => config('reviews.per_page'),
            'reviewCount'  => $reviewCount,
            'rating' => $rating ?? '',
            'name' => $name,
        ]);
    }
}
