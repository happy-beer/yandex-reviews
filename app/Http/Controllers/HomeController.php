<?php

namespace App\Http\Controllers;

use App\Exceptions\YandexProviderException;
use App\Services\YandexMapsClient;
use App\Services\YandexSessionStore;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\Setting;
use InvalidArgumentException;
use Throwable;

class HomeController extends Controller
{

    public function index(Request $request, YandexMapsClient $yandex, YandexSessionStore $sessionStore)
    {
        $setting = $this->getYandexSetting(auth()->id());

        $data = [
            'success' => true,
            'message' => '',
            'businessId' => '',
            'csrfToken' => '',
            'sessionId' => '',
            'reqId' => '',
            'rating' => '',
            'name' => '',
            'reviewCount' => '',
            'cookies' => [],
        ];

        if ($setting && $setting->value) {
            $data = array_merge($data, $sessionStore->get($request));

            if (!$sessionStore->hasRequiredData($data)) {
                $data = $this->fetchAndStoreYandexData($request, $yandex, $setting->value, $data, $sessionStore);
            }
        }

        return Inertia::render('Home', [
            'success' => $data['success'],
            'message' => $data['message'],
            'settings' => $setting,
            'pageSize' => config('reviews.per_page'),
            'reviewCount' => $data['reviewCount'],
            'rating' => $data['rating'],
            'name' => $data['name'],
        ]);
    }

    private function getYandexSetting(?int $userId): ?Setting
    {
        return Setting::where('user_id', $userId)
            ->where('key', 'yandex_url')
            ->first();
    }

    private function fetchAndStoreYandexData(
        Request          $request,
        YandexMapsClient $yandex,
        string           $orgUrl,
        array            $currentData,
        YandexSessionStore $sessionStore
    ): array
    {
        try {
            $yData = $yandex->extractFromOrgPage($orgUrl, $request);

            $mergedData = array_merge($currentData, [
                'businessId' => $yData['businessId'] ?? '',
                'csrfToken' => $yData['csrfToken'] ?? '',
                'sessionId' => $yData['sessionId'] ?? '',
                'reqId' => $yData['reqId'] ?? '',
                'name' => $yData['name'] ?? '',
                'rating' => $yData['rating'] ?? '',
                'reviewCount' => $yData['reviewCount'] ?? '',
                'cookies' => $yData['cookies'] ?? [],
            ]);

            $sessionStore->put($request, $mergedData);

            return $mergedData;
        } catch (YandexProviderException $e) {
            return array_merge($currentData, [
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

}
