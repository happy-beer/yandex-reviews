<?php

namespace App\Http\Controllers;

use App\Services\YandexMapsClient;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;
use App\Models\Setting;
use Nette\Utils\Arrays;

class HomeController extends Controller
{
    public function index(Request $request, YandexMapsClient $yandex)
    {
        $userId = auth()->id();
        $setting = Setting::where('user_id', $userId)
            ->where('key', 'yandex_url')
            ->first();

        $businessId = '';
        $csrfToken = '';
        $sessionId = '';
        $reqId = '';
        $rating = '';
        $name = '';
        $reviewCount = '';
        $jarArray = [];

        if ($setting && $setting->value) {

            $session = $request->session();
            $businessId = $session->get('yandex.businessId') ?? '';
            $csrfToken = $session->get('yandex.csrfToken') ?? '';
            $sessionId = $session->get('yandex.sessionId') ?? '';
            $reqId = $session->get('yandex.reqId') ?? '';
            $rating = $session->get('yandex.rating') ?? '';
            $name = $session->get('yandex.name') ?? '';
            $reviewCount = $session->get('yandex.reviewCount') ?? '';
            $jarArray = $session->get('yandex.cookies', []);

            if (!$businessId || !$csrfToken || !$sessionId || !$reqId) {

                $yData = $yandex->extractFromOrgPage($setting->value, $request);

                $businessId  = $yData['businessId'];
                $csrfToken   = $yData['csrfToken'];
                $sessionId   = $yData['sessionId'];
                $reqId       = $yData['reqId'];
                $name        = $yData['name'];
                $rating      = $yData['rating'];
                $reviewCount = $yData['reviewCount'];
                $jarArray    = $yData['cookies'];

                $session->put('yandex.businessId', $businessId);
                $session->put('yandex.csrfToken', $csrfToken);
                $session->put('yandex.sessionId', $sessionId);
                $session->put('yandex.reqId', $reqId);
                $session->put('yandex.name', $name);
                $session->put('yandex.rating', $rating);
                $session->put('yandex.reviewCount', $reviewCount);
                $session->put('yandex.cookies', $jarArray);
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
            'reviewCount' => $reviewCount,
            'rating' => $rating ?? '',
            'name' => $name,
            'cookie' => serialize($jarArray),
        ]);
    }
}
