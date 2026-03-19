<?php

namespace App\Services\Reviews;

use App\Models\Place;
use App\Models\Review;

class ReviewImportService
{
    public function import(Place $place, array $mappedReviews): array
    {
        $created = 0;
        $updated = 0;

        foreach ($mappedReviews as $reviewData) {
            $attributes = [
                'place_id' => $place->id,
                'external_id' => $reviewData['external_id'],
            ];

            $values = [
                'author_name' => $reviewData['author_name'] ?? null,
                'text' => $reviewData['text'] ?? null,
                'rating' => $reviewData['rating'] ?? 1,
                'published_at' => $reviewData['published_at'] ?? null,
                'has_owner_reply' => $reviewData['has_owner_reply'] ?? false,
                'owner_reply_text' => $reviewData['owner_reply_text'] ?? null,
                'owner_replied_at' => $reviewData['owner_replied_at'] ?? null,
                'raw_payload' => $reviewData['raw_payload'] ?? null,
            ];

            $review = Review::query()->where($attributes)->first();

            if ($review) {
                $review->fill($values)->save();
                $updated++;
                continue;
            }

            Review::query()->create($attributes + $values);
            $created++;
        }

        return [
            'reviews_fetched' => count($mappedReviews),
            'reviews_created' => $created,
            'reviews_updated' => $updated,
        ];
    }
}
