<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Http\Resources\WalletTransactionResource;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $wallets) {}

    public function show(Request $request): JsonResponse
    {
        $wallet = $this->wallets->forUser($request->user());

        return response()->json([
            'wallet' => WalletResource::make($wallet),
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $wallet = $this->wallets->forUser($request->user());

        $transactions = $wallet->transactions()
            ->latest()
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return WalletTransactionResource::collection($transactions)->response();
    }
}
