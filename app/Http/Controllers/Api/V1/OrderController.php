<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\MinimumCheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Resources\OrderRefillResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderStatusLogResource;
use App\Models\Order;
use App\Models\Service;
use App\Services\Orders\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['service.providerService', 'refills'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->boolean('repeat'), fn ($q) => $q->where('is_repeat', true))
            ->latest()
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return OrderResource::collection($orders)->response();
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $service = Service::query()->findOrFail($request->integer('service_id'));

        try {
            $order = $this->orders->place($request->user(), $service, $request->validated());
        } catch (MinimumCheckoutException $exception) {
            return response()->json($exception->toArray(), 422);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'order' => OrderResource::make($order)->resolve(),
        ], 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $order->load(['service', 'statusLogs', 'refills']);

        if ($order->isOpen()) {
            $order = $this->orders->syncStatus($order);
        }

        return response()->json([
            'order' => OrderResource::make($order),
            'status_logs' => OrderStatusLogResource::collection($order->statusLogs),
            'refills' => OrderRefillResource::collection($order->refills),
            'can_request_refill' => $order->canRequestRefill(),
            'refill_block_reason' => $order->refillBlockReason(),
        ]);
    }

    public function syncStatus(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $order = $this->orders->syncStatus($order->load(['service', 'statusLogs']), force: true);

        return response()->json([
            'order' => OrderResource::make($order),
            'delivery' => $order->delivery()->toArray(),
            'status_logs' => OrderStatusLogResource::collection($order->statusLogs),
        ]);
    }

    /**
     * Delivery snapshot for one order (polls provider if the order is still open).
     */
    public function delivery(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        if ($order->isOpen()) {
            $order = $this->orders->syncStatus($order, force: (bool) $request->boolean('force'));
        }

        return response()->json([
            'order_id' => $order->id,
            'status' => $order->status?->value,
            'delivery' => $this->orders->delivery($order)->toArray(),
            'last_status_check_at' => $order->last_status_check_at?->toIso8601String(),
        ]);
    }
}
