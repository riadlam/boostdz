<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\WalletCheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\Payments\WalletCheckoutService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class WalletCheckoutController extends Controller
{
    public function __construct(private readonly WalletCheckoutService $walletCheckout) {}

    public function store(WalletCheckoutRequest $request): JsonResponse
    {
        try {
            $order = $this->walletCheckout->checkout($request->user(), $request->validated());
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'order' => OrderResource::make($order)->resolve(),
        ], 201);
    }
}
