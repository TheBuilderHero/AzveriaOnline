<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UploadMapLayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'image_path' => ['required_without:image_file', 'nullable', 'string', 'max:255'],
            'image_file' => ['required_without:image_path', 'nullable', 'image', 'max:10240'],
        ];
    }
}
