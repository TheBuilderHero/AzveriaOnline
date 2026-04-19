<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'theme' => ['nullable', 'in:light,dark'],
            'color_blind_mode' => ['nullable', 'in:none,protanopia,deuteranopia,tritanopia'],
            'dog_bark_enabled' => ['nullable', 'boolean'],
            'font_mode' => ['nullable', 'in:normal,fun,cool_person'],
            'show_unread_chat_badge' => ['nullable', 'boolean'],
            'alliance_color_overrides' => ['nullable', 'array'],
            'alliance_color_overrides.*' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
