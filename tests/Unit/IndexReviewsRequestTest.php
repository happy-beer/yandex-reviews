<?php

namespace Tests\Unit;

use App\Http\Requests\IndexReviewsRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexReviewsRequestTest extends TestCase
{
    public function test_index_reviews_request_accepts_valid_filters(): void
    {
        $request = new IndexReviewsRequest();

        $validator = Validator::make([
            'place_id' => 1,
            'rating' => 5,
            'search' => 'coffee',
            'date_from' => now()->subDays(5)->toDateString(),
            'date_to' => now()->toDateString(),
            'sort' => 'rating_desc',
            'page' => 2,
            'pageSize' => 50,
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_index_reviews_request_rejects_invalid_rating_and_sort(): void
    {
        $request = new IndexReviewsRequest();

        $validator = Validator::make([
            'rating' => 7,
            'sort' => 'random',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('rating', $validator->errors()->toArray());
        $this->assertArrayHasKey('sort', $validator->errors()->toArray());
    }
}
