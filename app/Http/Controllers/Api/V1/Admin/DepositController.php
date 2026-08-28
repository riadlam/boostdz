<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\ReviewDepositRequest;
use App\Http\Resources\DepositResource;
use App\Models\Deposit;
use App\Services\Deposits\DepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DepositController extends Controller
{
    public function __construct(private readonly DepositService $deposits) {}

    public function index(Request $request): JsonResponse
    {
        $deposits = Deposit::query()
            ->with(['user', 'reviewer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return DepositResource::collection($deposits)->response();
    }

    public function approve(ReviewDepositRequest $request, Deposit $deposit): JsonResponse
    {
        try {
            $deposit = $this->deposits->approve($deposit, $request->user(), $request->input('admin_note'));
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'deposit' => DepositResource::make($deposit),
        ]);
    }

    public function reject(ReviewDepositRequest $request, Deposit $deposit): JsonResponse
    {
        try {
            $deposit = $this->deposits->reject($deposit, $request->user(), $request->input('admin_note'));
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'deposit' => DepositResource::make($deposit),
        ]);
    }
}
