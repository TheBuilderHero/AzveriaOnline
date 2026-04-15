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
            'image_file' => ['required_without:image_path', 'nullable', 'image', 'max:51200'],
        ];
    }

    public function messages(): array
    {
        return [
            'image_file.image' => 'The selected file is not a valid image. Allowed formats are jpg, jpeg, png, gif, webp, and bmp.',
            'image_file.max' => 'The image is too large. The maximum upload size is 50 MB.',
            'image_file.required_without' => 'Provide either an image file upload or an image path.',
            'image_path.required_without' => 'Provide either an image path or an image file upload.',
        ];
    }
}
