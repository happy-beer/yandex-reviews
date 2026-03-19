<?php

namespace App\Services\Yandex;

use App\Models\Place;
use App\Services\YandexMapsClient;
use Illuminate\Http\Request;

class YandexReviewsFetcher
{
    public function __construct(
        private readonly YandexMapsClient $client
    ) {
    }

    public function fetch(Place $place, Request $request, int $page = 1, ?int $pageSize = null): array
    {
        $orgData = $this->client->extractFromOrgPage($place->source_url, $request);

        $params = [
            'csrfToken' => $orgData['csrfToken'] ?? '',
            'sessionId' => $orgData['sessionId'] ?? '',
            'businessId' => $orgData['businessId'] ?? '',
            'page' => $page,
            'pageSize' => $pageSize ?? config('reviews.per_page'),
            'reqId' => $orgData['reqId'] ?? '',
            'ranking' => 'by_time',
            'locale' => 'ru_UA',
            'ajax' => 1,
        ];

        $reviewsData = $this->client->fetchReviews($params, $orgData['cookies'] ?? [], $request);

        return [
            'place' => [
                'external_id' => $orgData['businessId'] ?? null,
                'name' => $orgData['name'] ?? null,
                'rating' => $orgData['rating'] ?? null,
                'reviews_count' => $orgData['reviewCount'] ?? null,
            ],
            'reviews' => $reviewsData['reviews'] ?? [],
            'params' => $reviewsData['params'] ?? [],
        ];
    }
}
