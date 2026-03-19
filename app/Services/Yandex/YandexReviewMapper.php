<?php

namespace App\Services\Yandex;

use Carbon\Carbon;

class YandexReviewMapper
{
    public function mapMany(array $reviews, int $placeId): array
    {
        $mapped = [];

        foreach ($reviews as $review) {
            $mapped[] = $this->map($review, $placeId);
        }

        return $mapped;
    }

    public function map(array $review, int $placeId): array
    {
        return [
            'place_id' => $placeId,
            'external_id' => (string) ($review['reviewId'] ?? ''),
            'author_name' => data_get($review, 'author.name'),
            'text' => $review['text'] ?? null,
            'rating' => $this->normalizeRating($review['rating'] ?? null),
            'published_at' => $this->parseDate($review['updatedTime'] ?? null),
            'has_owner_reply' => !empty($review['businessComment']) || !empty($review['ownerReply']),
            'owner_reply_text' => data_get($review, 'businessComment.text')
                ?? data_get($review, 'ownerReply.text'),
            'owner_replied_at' => $this->parseDate(
                data_get($review, 'businessComment.updatedTime')
                    ?? data_get($review, 'ownerReply.updatedTime')
            ),
            'raw_payload' => $review,
        ];
    }

    private function parseDate(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeRating(mixed $value): int
    {
        $rating = (int) $value;

        return max(1, min(5, $rating));
    }
}
