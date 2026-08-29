<?php

namespace App\Http\Requests\Api;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
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
            'idempotency_key' => ['nullable', 'uuid'],
            'runs' => ['nullable', 'integer', 'min:1'],
            'interval' => ['nullable', 'integer', 'min:1'],
            'comments' => ['nullable', 'string', 'max:10000'],
            'usernames' => ['nullable', 'string'],
            'hashtag' => ['nullable', 'string', 'max:255'],
            'posts' => ['nullable', 'integer', 'min:1'],
            'delay' => ['nullable', 'integer', 'min:0'],
            'expiry' => ['nullable', 'date'],
            'answer_number' => ['nullable', 'integer'],
            'country' => ['nullable', 'string', 'max:64'],
            'quality' => ['nullable', 'string', 'max:32'],
            'is_repeat' => ['nullable', 'boolean'],
            'meta' => ['nullable', 'array'],
            'expected_charge_dzd' => ['nullable', 'numeric', 'min:0'],
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
