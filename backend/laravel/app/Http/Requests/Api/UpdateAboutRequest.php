<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAboutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'about_text' => ['nullable', 'string', 'max:5000'],
            'alliance_name' => ['nullable', 'string', 'max:120'],
        ];
    }
}
