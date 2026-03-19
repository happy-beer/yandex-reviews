<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'source_url' => $this->source_url,
            'rating' => $this->rating,
            'reviews_count' => $this->reviews_count,
            'last_synced_at' => optional($this->last_synced_at)?->toISOString(),
            'is_active' => $this->is_active,
            'created_at' => optional($this->created_at)?->toISOString(),
        ];
    }
}
