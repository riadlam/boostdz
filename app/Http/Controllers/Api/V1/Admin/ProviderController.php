<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderResource;
use App\Http\Resources\ProviderServiceResource;
use App\Http\Resources\ProviderSyncLogResource;
use App\Models\Provider;
use App\Services\BuzzerPanel\BuzzerPanelClient;
use App\Services\Catalog\CatalogSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function index(): JsonResponse
    {
        $providers = Provider::query()->orderBy('name')->get();

        return response()->json([
            'providers' => ProviderResource::collection($providers),
        ]);
    }

    public function show(Provider $provider): JsonResponse
    {
        return response()->json([
            'provider' => ProviderResource::make($provider),
        ]);
    }

    public function services(Provider $provider): JsonResponse
    {
        $services = $provider->providerServices()
            ->with('service')
            ->latest('synced_at')
            ->paginate(50);

        return ProviderServiceResource::collection($services)->response();
    }

    public function syncCatalog(Provider $provider, CatalogSyncService $catalogSync): JsonResponse
    {
        $log = $catalogSync->sync($provider);

        return response()->json([
            'sync_log' => ProviderSyncLogResource::make($log),
        ]);
    }

    public function syncBalance(Provider $provider): JsonResponse
    {
        $client = BuzzerPanelClient::fromProvider($provider);
        $profile = $client->profile();

        $provider->update([
            'cached_balance' => $profile['balance'] ?? $profile['funds'] ?? null,
            'balance_synced_at' => now(),
        ]);

        return response()->json([
            'provider' => ProviderResource::make($provider->fresh()),
            'profile' => $profile,
        ]);
    }
}
