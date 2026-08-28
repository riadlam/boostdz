<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderRefillResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderStatusLogResource;
use App\Http\Resources\ProviderSyncLogResource;
use App\Http\Resources\WebhookEventResource;
use App\Models\Order;
use App\Models\OrderRefill;
use App\Models\OrderStatusLog;
use App\Models\ProviderSyncLog;
use App\Models\WebhookEvent;
use App\Services\Orders\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpsController extends Controller
{
    public function syncLogs(Request $request): JsonResponse
    {
        $logs = ProviderSyncLog::query()
            ->with('provider')
            ->when($request->filled('provider_id'), fn ($q) => $q->where('provider_id', $request->integer('provider_id')))
            ->latest()
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return ProviderSyncLogResource::collection($logs)->response();
    }

    public function webhookEvents(Request $request): JsonResponse
    {
        $events = WebhookEvent::query()
            ->when($request->filled('provider_id'), fn ($q) => $q->where('provider_id', $request->integer('provider_id')))
            ->latest()
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return WebhookEventResource::collection($events)->response();
    }

    public function webhookEvent(WebhookEvent $webhookEvent): JsonResponse
    {
        return response()->json([
            'webhook_event' => WebhookEventResource::make($webhookEvent),
            'payload' => $webhookEvent->payload,
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['user', 'service'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return OrderResource::collection($orders)->response();
    }

    public function order(Order $order, OrderService $orderService): JsonResponse
    {
        if ($order->isOpen()) {
            $order = $orderService->syncStatus($order);
        }

        $order->load(['user', 'service', 'statusLogs', 'refills']);

        return response()->json([
            'order' => OrderResource::make($order),
            'status_logs' => OrderStatusLogResource::collection($order->statusLogs),
            'refills' => OrderRefillResource::collection($order->refills),
        ]);
    }

    public function orderStatusLogs(Request $request): JsonResponse
    {
        $logs = OrderStatusLog::query()
            ->with('order')
            ->when($request->filled('order_id'), fn ($q) => $q->where('order_id', $request->integer('order_id')))
            ->latest('created_at')
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return OrderStatusLogResource::collection($logs)->response();
    }

    public function orderRefills(Request $request): JsonResponse
    {
        $refills = OrderRefill::query()
            ->with('order')
            ->when($request->filled('order_id'), fn ($q) => $q->where('order_id', $request->integer('order_id')))
            ->latest()
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return OrderRefillResource::collection($refills)->response();
    }
}
