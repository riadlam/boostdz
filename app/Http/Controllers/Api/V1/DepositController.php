<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\MinimumCheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDepositRequest;
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
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return DepositResource::collection($deposits)->response();
    }

    public function store(StoreDepositRequest $request): JsonResponse
    {
        try {
            $deposit = $this->deposits->create(
                $request->user(),
                $request->validated(),
                $request->file('proof'),
            );
        } catch (MinimumCheckoutException $exception) {
            return response()->json($exception->toArray(), 422);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'deposit' => DepositResource::make($deposit),
        ], 201);
    }

    public function show(Request $request, Deposit $deposit): JsonResponse
    {
        abort_unless($deposit->user_id === $request->user()->id, 404);

        return response()->json([
            'deposit' => DepositResource::make($deposit),
        ]);
    }
}
