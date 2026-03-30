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
        $resolvedPageSize = max((int) ($pageSize ?? config('reviews.per_page_sync')), 1);

        $params = [
            'csrfToken' => $orgData['csrfToken'] ?? '',
            'sessionId' => $orgData['sessionId'] ?? '',
            'businessId' => $orgData['businessId'] ?? '',
            'page' => $page,
            'pageSize' => $resolvedPageSize,
            'reqId' => $orgData['reqId'] ?? '',
            'ranking' => 'by_time',
            'locale' => 'ru_UA',
            'ajax' => 1,
        ];

        $allReviews = [];
        $lastParams = [];
        $totalPages = null;
        $currentPage = max($page, 1);

        do {
            $params['page'] = $currentPage;
            $reviewsData = $this->client->fetchReviews($params, $orgData['cookies'] ?? [], $request);

            $pageReviews = $reviewsData['reviews'] ?? [];
            $allReviews = [...$allReviews, ...$pageReviews];

            $lastParams = $reviewsData['params'] ?? [];

            if ($totalPages === null) {
                $totalPages = $this->resolveTotalPages($lastParams, $resolvedPageSize, count($pageReviews));
            }

            $currentPage++;
        } while ($this->shouldContinuePaging($currentPage, $totalPages, $pageReviews, $resolvedPageSize));

        return [
            'place' => [
                'external_id' => $orgData['businessId'] ?? null,
                'name' => $orgData['name'] ?? null,
                'rating' => $orgData['rating'] ?? null,
                'reviews_count' => $orgData['reviewCount'] ?? null,
            ],
            'reviews' => $allReviews,
            'params' => $lastParams,
        ];
    }

    private function resolveTotalPages(array $params, int $pageSize, int $loadedCount): ?int
    {
        $totalPages = (int) ($params['totalPages'] ?? 0);
        if ($totalPages > 0) {
            return $totalPages;
        }

        $count = (int) ($params['count'] ?? 0);
        if ($count > 0) {
            return (int) ceil($count / $pageSize);
        }

        if ($loadedCount < $pageSize) {
            return 1;
        }

        return null;
    }

    private function shouldContinuePaging(int $nextPage, ?int $totalPages, array $pageReviews, int $pageSize): bool
    {
        if ($totalPages !== null) {
            return $nextPage <= $totalPages;
        }

        if (empty($pageReviews)) {
            return false;
        }

        return count($pageReviews) >= $pageSize;
    }
}
