<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $allowedKeys = Setting::allowedKeys();

        return [
            'settings' => ['sometimes', 'array'],
            'settings.*.key' => ['required_with:settings', 'string', Rule::in($allowedKeys)],
            'settings.*.value' => ['required_with:settings', 'string', 'max:255'],
        ];
    }
}
