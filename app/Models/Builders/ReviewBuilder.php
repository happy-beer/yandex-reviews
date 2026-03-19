<?php

namespace App\Models\Builders;

use Illuminate\Database\Eloquent\Builder;

class ReviewBuilder extends Builder
{
    public function oldest($column = 'published_at'): static
    {
        return parent::oldest($column)->orderBy('id');
    }

    public function newest($column = 'published_at'): static
    {
        return $this->orderByDesc($column)->orderByDesc('id');
    }
}
