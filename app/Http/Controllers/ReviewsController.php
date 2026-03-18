<?php

namespace App\Http\Controllers;

use App\Exceptions\YandexProviderException;
use App\Services\YandexMapsClient;
use App\Services\YandexSessionStore;
use Illuminate\Http\Request;

class ReviewsController extends Controller
{
    public function index(Request $request, YandexMapsClient $yandex, YandexSessionStore $sessionStore)
    {
        $data = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'pageSize' => 'sometimes|integer|min:1',
        ]);

        $yData = $sessionStore->get($request);

        if (!$sessionStore->hasRequiredData($yData)) {
            return response()->json([
                'message' => 'Yandex session data is missing.',
            ], 422);
        }

        $params = [
            "csrfToken" => $yData['csrfToken'],
            "sessionId" => $yData['sessionId'],
            "businessId" => $yData['businessId'],
            "page" => $data['page'] ?? 1,
            "pageSize" => $data['pageSize'] ?? config('reviews.per_page'),
            "reqId" => $yData['reqId'],
            "ranking" => 'by_time',
            "locale" => 'ru_UA',
            "ajax" => 1,
        ];
        try {
            $result = $yandex->fetchReviews($params, $yData['cookies'], $request);

            $sessionStore->putPartial($request, $result);

            return response()->json($result);
        } catch (YandexProviderException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 502);
        }
    }
}
