<?php

namespace App\Http\Controllers;

use App\Exceptions\YandexProviderException;
use App\Http\Requests\IndexReviewsRequest;
use App\Services\YandexMapsClient;
use App\Services\YandexSessionStore;

class ReviewsController extends Controller
{
    public function index(IndexReviewsRequest $request, YandexMapsClient $yandex, YandexSessionStore $sessionStore)
    {
        $data = $request->validated();

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
