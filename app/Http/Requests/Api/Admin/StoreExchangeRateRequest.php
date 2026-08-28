<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_currency' => ['required', 'string', 'size:3'],
            'to_currency' => ['required', 'string', 'size:3'],
            'rate' => ['required', 'numeric', 'gt:0'],
            'source' => ['nullable', 'string', 'max:255'],
            'fetched_at' => ['nullable', 'date'],
        ];
    }
}
