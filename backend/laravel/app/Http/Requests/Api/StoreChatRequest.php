<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        $type = (string) $this->input('type', 'group');

        return [
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', 'in:group,dm,global'],
            'member_ids' => [$type === 'global' ? 'sometimes' : 'required', 'array', $type === 'global' ? 'min:0' : 'min:1'],
            'member_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('type') === 'global' && $this->user()?->role !== 'admin') {
                $validator->errors()->add('type', 'Only admins can create chats that automatically include everyone.');
            }
        });
    }
}
