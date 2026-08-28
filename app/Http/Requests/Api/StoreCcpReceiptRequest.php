<?php

namespace App\Http\Requests\Api;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCcpReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'link' => ['required', 'string', 'max:2048'],
            'quantity' => ['required', 'integer', 'min:1'],
            'amount_dzd' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:255'],
            'is_repeat' => ['nullable', 'boolean'],
            'idempotency_key' => ['nullable', 'uuid'],
            'country' => ['nullable', 'string', 'max:64'],
            'quality' => ['nullable', 'string', 'max:32'],
            'platform_slug' => ['nullable', 'string', 'max:64'],
            'category_slug' => ['nullable', 'string', 'max:64'],
            'comments' => ['nullable', 'string', 'max:10000'],
            'receipt' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_repeat')) {
            $this->merge([
                'is_repeat' => filter_var($this->input('is_repeat'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }
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
                $validator->errors()->add('comments', 'Comments are required for this service (one per line).');

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
