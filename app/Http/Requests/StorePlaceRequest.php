<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'source_url' => ['required', 'url', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
