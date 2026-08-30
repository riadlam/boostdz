<?php

namespace App\Http\Requests\Api;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class WalletCheckoutRequest extends FormRequest
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
            'is_repeat' => ['sometimes', 'boolean'],
            'idempotency_key' => ['required', 'uuid'],
            'expected_charge_dzd' => ['required', 'numeric', 'min:0'],
            'country' => ['sometimes', 'nullable', 'string', 'max:8'],
            'quality' => ['sometimes', 'nullable', 'string', 'max:32'],
            'platform_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'category_slug' => ['sometimes', 'nullable', 'string', 'max:64'],
            'comments' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $service = Service::query()->find($this->integer('service_id'));

            if (! $service) {
                return;
            }

            if ($service->requiresCustomComments() && trim((string) $this->input('comments')) === '') {
                $validator->errors()->add('comments', __('api.comments.required_for_service'));

                return;
            }

            try {
                $service->validateComments(
                    $this->input('comments'),
                    (int) $this->input('quantity'),
                );
            } catch (\InvalidArgumentException $exception) {
                $validator->errors()->add('comments', $exception->getMessage());
            }
        });
    }
}
