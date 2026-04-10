<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShopItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'display_name' => ['sometimes', 'string', 'max:160'],
            'cost_json' => ['sometimes', 'array'],
            'effect_json' => ['sometimes', 'nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'visibility_json' => ['sometimes', 'nullable', 'array'],
            'visibility_json.*' => ['integer', 'exists:users,id'],
        ];
    }
}
