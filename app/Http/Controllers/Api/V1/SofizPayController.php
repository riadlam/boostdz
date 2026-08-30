<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\MinimumCheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InitSofizPayCheckoutRequest;
use App\Http\Requests\Api\InitSofizPayTopupRequest;
use App\Http\Resources\SofizPayTransactionResource;
use App\Services\SofizPay\SofizPayException;
use App\Services\SofizPay\SofizPayPaymentService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class SofizPayController extends Controller
{
    public function __construct(private readonly SofizPayPaymentService $payments) {}

    public function initCheckout(InitSofizPayCheckoutRequest $request): JsonResponse
    {
        try {
            $result = $this->payments->initCheckout($request->user(), $request->validated());
        } catch (MinimumCheckoutException $exception) {
            return response()->json($exception->toArray(), 422);
        } catch (SofizPayException|InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'transaction' => SofizPayTransactionResource::make($result['transaction'])->resolve(),
            'payment_url' => $result['payment_url'],
        ], 201);
    }

    public function initTopup(InitSofizPayTopupRequest $request): JsonResponse
    {
        try {
            $result = $this->payments->initTopup($request->user(), $request->validated());
        } catch (MinimumCheckoutException $exception) {
            return response()->json($exception->toArray(), 422);
        } catch (SofizPayException|InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'transaction' => SofizPayTransactionResource::make($result['transaction'])->resolve(),
            'payment_url' => $result['payment_url'],
        ], 201);
    }

    public function status(string $invoiceId): JsonResponse
    {
        try {
            $transaction = $this->payments->statusForUser(request()->user(), $invoiceId);
        } catch (\Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }

        return response()->json([
            'transaction' => SofizPayTransactionResource::make($transaction)->resolve(),
        ]);
    }
}
