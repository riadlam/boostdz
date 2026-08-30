<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class InitSofizPayTopupRequest extends FormRequest
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
            'amount_dzd' => ['required', 'numeric', 'min:1'],
            'phone' => ['required', 'string', 'max:32'],
        ];
    }
}
