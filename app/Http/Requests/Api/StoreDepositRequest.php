<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $minimum = max(0, (int) config('checkout.minimum_amount_dzd', 0));
        $amountRules = ['required', 'numeric', 'min:0.01'];

        if ($minimum > 0) {
            $amountRules[] = 'min:'.$minimum;
        }

        return [
            'amount_dzd' => $amountRules,
            'method' => ['required', 'in:ccp'],
            'wired_amount_dzd' => ['nullable', 'numeric', 'min:0'],
            'provider_reference' => ['nullable', 'string', 'max:255'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('method') === 'ccp' && ! $this->hasFile('proof')) {
                $validator->errors()->add('proof', 'Proof of payment is required for CCP deposits.');
            }
        });
    }
}
