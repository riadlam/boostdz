<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderRefillResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderRefill;
use App\Services\Orders\RefillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RefillController extends Controller
{
    public function __construct(private readonly RefillService $refills) {}

    /**
     * Orders the current user can request refill for (refill area list).
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $this->refills->refillableOrdersForUser($request->user()->id);

        return response()->json([
            'orders' => OrderResource::collection($orders),
        ]);
    }

    public function forOrder(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $order->load(['service', 'refills']);

        return response()->json([
            'order' => OrderResource::make($order),
            'can_request_refill' => $order->canRequestRefill(),
            'refill_block_reason' => $order->refillBlockReason(),
            'refills' => OrderRefillResource::collection($order->refills()->latest()->get()),
        ]);
    }

    /**
     * Place refill request: BuzzerPanel action=refill, id=provider_order_id.
     */
    public function store(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        try {
            $refill = $this->refills->request($order);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Refill request submitted. It is now pending.',
            'refill' => OrderRefillResource::make($refill),
            'order' => OrderResource::make($order->fresh(['service', 'refills'])),
        ], 201);
    }

    public function show(Request $request, OrderRefill $refill): JsonResponse
    {
        $refill->loadMissing('order');
        abort_unless($refill->order?->user_id === $request->user()->id, 404);

        if ($refill->status?->isOpen()) {
            $refill = $this->refills->syncStatus($refill);
        }

        return response()->json([
            'refill' => OrderRefillResource::make($refill),
        ]);
    }

    /**
     * Poll refill: BuzzerPanel action=refill_status, id=provider_refill_id.
     */
    public function syncStatus(Request $request, OrderRefill $refill): JsonResponse
    {
        $refill->loadMissing('order');
        abort_unless($refill->order?->user_id === $request->user()->id, 404);

        $refill = $this->refills->syncStatus($refill);

        return response()->json([
            'refill' => OrderRefillResource::make($refill),
        ]);
    }
}
