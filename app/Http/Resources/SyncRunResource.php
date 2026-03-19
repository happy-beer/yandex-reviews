<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SyncRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'started_at' => optional($this->started_at)?->toISOString(),
            'finished_at' => optional($this->finished_at)?->toISOString(),
            'reviews_fetched' => $this->reviews_fetched,
            'reviews_created' => $this->reviews_created,
            'reviews_updated' => $this->reviews_updated,
            'error_message' => $this->error_message,
        ];
    }
}
