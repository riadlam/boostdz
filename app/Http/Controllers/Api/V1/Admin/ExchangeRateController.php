<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreExchangeRateRequest;
use App\Http\Resources\ExchangeRateResource;
use App\Models\ExchangeRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rates = ExchangeRate::query()
            ->when($request->filled('from'), fn ($q) => $q->where('from_currency', strtoupper($request->string('from')->toString())))
            ->when($request->filled('to'), fn ($q) => $q->where('to_currency', strtoupper($request->string('to')->toString())))
            ->latest('fetched_at')
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return ExchangeRateResource::collection($rates)->response();
    }

    public function store(StoreExchangeRateRequest $request): JsonResponse
    {
        $rate = ExchangeRate::query()->create([
            'from_currency' => strtoupper($request->string('from_currency')->toString()),
            'to_currency' => strtoupper($request->string('to_currency')->toString()),
            'rate' => $request->input('rate'),
            'source' => $request->input('source', 'manual'),
            'fetched_at' => $request->input('fetched_at', now()),
        ]);

        return response()->json([
            'exchange_rate' => ExchangeRateResource::make($rate),
        ], 201);
    }

    public function latest(): JsonResponse
    {
        $rate = ExchangeRate::query()
            ->where('from_currency', 'IDR')
            ->where('to_currency', 'DZD')
            ->orderByDesc('fetched_at')
            ->first();

        abort_unless($rate, 404);

        return response()->json([
            'exchange_rate' => ExchangeRateResource::make($rate),
        ]);
    }
}
