<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'place_id' => $this->place_id,
            'place_name' => optional($this->place)->name,
            'author_name' => $this->author_name,
            'rating' => $this->rating,
            'text' => $this->text,
            'published_at' => optional($this->published_at)?->toISOString(),
            'has_owner_reply' => $this->has_owner_reply,
            'owner_reply_text' => $this->owner_reply_text,
            'owner_replied_at' => optional($this->owner_replied_at)?->toISOString(),
        ];
    }
}
