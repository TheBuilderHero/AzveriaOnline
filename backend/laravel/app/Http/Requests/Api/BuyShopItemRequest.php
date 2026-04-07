<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class BuyShopItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:shop_items,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
