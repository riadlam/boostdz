<?php

namespace App\Http\Requests\Api\Auth;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            try {
                $this->merge([
                    'phone' => PhoneNumber::normalize((string) $this->input('phone')),
                ]);
            } catch (\InvalidArgumentException) {
                // Leave raw value; validation rule will fail.
            }
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => [
                'required',
                'string',
                'max:32',
                Rule::unique('users', 'phone'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! PhoneNumber::isValid((string) $value)) {
                        $fail(__('api.sofizpay.phone_invalid'));
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ];
    }
}
