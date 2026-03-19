<?php

namespace Tests\Unit;

use App\Http\Requests\StorePlaceRequest;
use App\Http\Requests\UpdatePlaceRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PlaceRequestsValidationTest extends TestCase
{
    public function test_store_place_request_validates_required_fields(): void
    {
        $request = new StorePlaceRequest();

        $validator = Validator::make([], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('source_url', $validator->errors()->toArray());
    }

    public function test_store_place_request_accepts_valid_payload(): void
    {
        $request = new StorePlaceRequest();

        $validator = Validator::make([
            'name' => 'Place name',
            'source_url' => 'https://yandex.ru/maps/org/name/1/reviews/',
            'is_active' => true,
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_update_place_request_has_same_rules_and_rejects_invalid_url(): void
    {
        $request = new UpdatePlaceRequest();

        $validator = Validator::make([
            'name' => 'Place name',
            'source_url' => 'not-url',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('source_url', $validator->errors()->toArray());
    }
}
