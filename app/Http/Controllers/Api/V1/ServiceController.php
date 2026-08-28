<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\Pricing\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ServiceController extends Controller
{
    public function __construct(private readonly PricingService $pricing) {}

    public function platforms(): JsonResponse
    {
        $platforms = Cache::remember('services.active_platforms', 300, function () {
            return Service::query()
                ->where('is_active', true)
                ->select('platform')
                ->distinct()
                ->orderBy('platform')
                ->pluck('platform')
                ->values();
        });

        return response()->json([
            'platforms' => $platforms,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Service::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($platform = $request->string('platform')->toString()) {
            $query->where('platform', $platform);
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('refill')) {
            $query->where('refill', true);
        }

        $services = $query->paginate(min((int) $request->input('per_page', 40), 100));

        return ServiceResource::collection($services)->response();
    }

    public function show(Service $service): JsonResponse
    {
        abort_unless($service->is_active, 404);

        return response()->json([
            'service' => ServiceResource::make($service),
        ]);
    }

    public function quote(Request $request, Service $service): JsonResponse
    {
        abort_unless($service->is_active, 404);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $quantity = (int) $validated['quantity'];

        if ($quantity < $service->min || $quantity > $service->max) {
            return response()->json([
                'message' => "Quantity must be between {$service->min} and {$service->max}.",
            ], 422);
        }

        $quote = $this->pricing->quote($service, $quantity);

        return response()->json(array_merge($quote->toArray(), [
            'service_id' => $service->id,
            'currency' => 'DZD',
            'min' => $service->min,
            'max' => $service->max,
            'sell_rate_dzd' => $quote->sell_rate_dzd_per_1k,
        ]));
    }
}
