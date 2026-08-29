<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\MinimumCheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCcpReceiptRequest;
use App\Http\Resources\PaymentSubmissionResource;
use App\Services\Checkout\CheckoutPolicy;
use App\Services\Payments\PaymentSubmissionService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly PaymentSubmissionService $payments,
        private readonly CheckoutPolicy $checkoutPolicy,
    ) {}

    public function settings(): JsonResponse
    {
        return response()->json($this->checkoutPolicy->publicSettings());
    }

    public function storeCcpReceipt(StoreCcpReceiptRequest $request): JsonResponse
    {
        try {
            $submission = $this->payments->submitCcpReceipt(
                $request->user(),
                $request->validated(),
                $request->file('receipt'),
            );
        } catch (MinimumCheckoutException $exception) {
            return response()->json($exception->toArray(), 422);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'submission' => PaymentSubmissionResource::make($submission)->resolve(),
            'message' => $submission->status?->value === 'pending'
                ? __('api.checkout.receipt_pending')
                : __('api.checkout.receipt_processed'),
        ], 201);
    }
}
