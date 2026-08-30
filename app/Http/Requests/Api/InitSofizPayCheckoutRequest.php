<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class InitSofizPayCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'link' => ['required', 'string', 'max:2048'],
            'phone' => ['required', 'string', 'max:32'],
            'is_repeat' => ['sometimes', 'boolean'],
            'idempotency_key' => ['sometimes', 'uuid'],
            'country' => ['sometimes', 'nullable', 'string', 'max:8'],
            'quality' => ['sometimes', 'nullable', 'string', 'max:32'],
            'platform_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'category_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'comments' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
