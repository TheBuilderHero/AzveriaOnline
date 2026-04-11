<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'leader_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'alliance_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'about_text' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'resources' => ['sometimes', 'array'],
            'resources.cow' => ['nullable', 'numeric'],
            'resources.wood' => ['nullable', 'numeric'],
            'resources.ore' => ['nullable', 'numeric'],
            'resources.food' => ['nullable', 'numeric'],
            'refined_resources' => ['sometimes', 'nullable', 'array'],
            'refined_resources.*' => ['numeric'],
            'currencies' => ['sometimes', 'nullable', 'array'],
            'currencies.*' => ['numeric'],
            'income' => ['sometimes', 'nullable', 'array'],
            'income.*' => ['numeric'],
            'terrain_square_miles' => ['sometimes', 'nullable', 'array'],
            'terrain_square_miles.*' => ['numeric', 'min:0'],
        ];
    }
}
