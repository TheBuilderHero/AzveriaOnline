<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreatePlaceholderNationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', 'unique:nations,name'],
            'leader_name' => ['nullable', 'string', 'max:120'],
            'alliance_name' => ['nullable', 'string', 'max:120'],
        ];
    }
}
