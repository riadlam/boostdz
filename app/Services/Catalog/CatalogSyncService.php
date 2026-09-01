<?php

namespace App\Services\Catalog;

use App\Models\CatalogPlatform;
use App\Models\Provider;
use App\Models\ProviderService;
use App\Models\ProviderSyncLog;
use App\Models\Service;
use App\Services\BuzzerPanel\BuzzerPanelClient;
use App\Services\BuzzerPanel\BuzzerPanelException;
use App\Services\Catalog\FeaturedServiceHealth;
use App\Services\Pricing\PricingService;
use App\Services\Telegram\TelegramClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogSyncService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly TelegramClient $telegram,
        private readonly FeaturedServiceHealth $featuredHealth,
        private readonly CatalogClassifier $classifier,
    ) {}

    public function sync(Provider $provider): ProviderSyncLog
    {
        $started = microtime(true);
        $client = BuzzerPanelClient::fromProvider($provider);

        try {
            $remoteServices = $client->services();
            $markup = $this->pricing->markupPercent();
            $now = now();
            $seenExternalIds = $this->extractSeenExternalIds($remoteServices);
            $providerRows = $this->buildProviderRows($provider, $remoteServices, $now);
            $synced = count($providerRows);
            $stats = [
                'updated' => 0,
                'created' => 0,
                'reactivated' => 0,
                'reactivated_names' => [],
                'classified' => 0,
                'rate_skipped' => max(0, count($seenExternalIds) - $synced),
                'deactivated' => 0,
                'deactivated_names' => [],
            ];

            DB::transaction(function () use ($provider, $providerRows, $seenExternalIds, $markup, $now, &$stats): void {
                $this->markProviderServicesPendingSync($provider);
                $this->upsertProviderServices($providerRows);
                $this->touchSeenProviderServices($provider, $seenExternalIds, $now);

                $providerServiceMap = ProviderService::query()
                    ->where('provider_id', $provider->id)
                    ->pluck('id', 'external_id');

                $syncStats = $this->syncAllServices(
                    $provider,
                    $providerRows,
                    $providerServiceMap,
                    $markup,
                    $now,
                );
                $stats = array_merge($stats, $syncStats);

                $this->reactivateLinkedServices($provider);
                $this->deactivateStaleProviderServices($provider);
                $deactivated = $this->deactivateStaleServices($provider);
                $stats['deactivated'] = $deactivated['count'];
                $stats['deactivated_names'] = $deactivated['names'];
            });

            $stats['classified'] = $this->classifyUnassignedServices($provider);

            CatalogClassifier::clearCache();
            $this->applyWebCatalogVisibility($provider);

            $durationMs = (int) round((microtime(true) - $started) * 1000);

            try {
                $this->telegram->notifyCatalogSyncSummary([
                    'provider_rows' => $synced,
                    'updated' => $stats['updated'],
                    'created' => $stats['created'],
                    'reactivated' => $stats['reactivated'],
                    'reactivated_names' => $stats['reactivated_names'],
                    'deactivated' => $stats['deactivated'],
                    'deactivated_names' => $stats['deactivated_names'],
                    'classified' => $stats['classified'],
                    'rate_skipped' => $stats['rate_skipped'],
                    'duration_ms' => $durationMs,
                ]);
            } catch (\Throwable) {
                // Sync should succeed even when Telegram is down.
            }

            $this->featuredHealth->clearStorefrontCache();
            $this->featuredHealth->checkAndNotifyAll();

            $message = sprintf(
                'Synced %d provider rows. Updated %d, created %d, classified %d, reactivated %d, deactivated %d, rate skipped %d.',
                $synced,
                $stats['updated'],
                $stats['created'],
                $stats['classified'],
                $stats['reactivated'],
                $stats['deactivated'],
                $stats['rate_skipped'],
            );

            return ProviderSyncLog::query()->create([
                'provider_id' => $provider->id,
                'type' => 'catalog',
                'status' => 'success',
                'records_synced' => $synced,
                'duration_ms' => $durationMs,
                'message' => $message,
                'meta' => $stats,
            ]);
        } catch (BuzzerPanelException $exception) {
            return ProviderSyncLog::query()->create([
                'provider_id' => $provider->id,
                'type' => 'catalog',
                'status' => 'failed',
                'records_synced' => 0,
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'message' => $exception->getMessage(),
                'meta' => ['response' => $exception->response()],
            ]);
        }
    }

    protected function classifyUnassignedServices(Provider $provider): int
    {
        $classified = 0;

        Service::query()
            ->whereNull('catalog_category_id')
            ->whereHas('providerService', fn ($query) => $query->where('provider_id', $provider->id))
            ->with('providerService:id,category,provider_id')
            ->chunkById(250, function ($services) use (&$classified): void {
                foreach ($services as $service) {
                    $this->classifier->classifyService($service, $service->providerService?->category);
                    $classified++;
                }
            });

        return $classified;
    }

    /**
     * @param  list<array<string, mixed>>  $remoteServices
     * @return list<int>
     */
    protected function extractSeenExternalIds(array $remoteServices): array
    {
        $ids = [];

        foreach ($remoteServices as $item) {
            if (! is_array($item)) {
                continue;
            }

            $externalId = (int) ($item['id'] ?? $item['service'] ?? 0);

            if ($externalId > 0) {
                $ids[] = $externalId;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<array<string, mixed>>  $remoteServices
     * @return list<array<string, mixed>>
     */
    protected function buildProviderRows(Provider $provider, array $remoteServices, \Illuminate\Support\Carbon $now): array
    {
        $providerRows = [];

        foreach ($remoteServices as $item) {
            if (! is_array($item)) {
                continue;
            }

            $externalId = (int) ($item['id'] ?? $item['service'] ?? 0);

            if ($externalId <= 0) {
                continue;
            }

            $rateIdr = $this->normalizeRateIdr(
                $item['price'] ?? $item['rate'] ?? $item['cost'] ?? $item['harga'] ?? 0,
            );

            if ((float) $rateIdr <= 0 || (float) $rateIdr > 100_000_000) {
                continue;
            }

            $providerRows[] = [
                'provider_id' => $provider->id,
                'external_id' => $externalId,
                'name' => mb_substr((string) ($item['name'] ?? 'Service '.$externalId), 0, 255),
                'category' => mb_substr((string) ($item['category'] ?? ''), 0, 255),
                'type' => $this->normalizeServiceType($item),
                'rate_idr' => $rateIdr,
                'min' => (int) ($item['min'] ?? 1),
                'max' => (int) ($item['max'] ?? 100000),
                'refill' => filter_var($item['refill'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'dripfeed' => filter_var($item['dripfeed'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'is_active' => 1,
                'raw_payload' => json_encode($item, JSON_UNESCAPED_UNICODE),
                'synced_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
                '_item' => $item,
            ];
        }

        return $providerRows;
    }

    /**
     * @param  list<array<string, mixed>>  $providerRows
     */
    protected function upsertProviderServices(array $providerRows): void
    {
        foreach (array_chunk($providerRows, 250) as $chunk) {
            $upsert = array_map(static function (array $row): array {
                unset($row['_item']);

                return $row;
            }, $chunk);

            ProviderService::query()->upsert(
                $upsert,
                ['provider_id', 'external_id'],
                ['name', 'category', 'type', 'rate_idr', 'min', 'max', 'refill', 'dripfeed', 'is_active', 'raw_payload', 'synced_at', 'updated_at'],
            );
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int|string, int>  $providerServiceMap
     * @return array<string, int|list<string>>
     */
    protected function syncAllServices(
        Provider $provider,
        array $providerRows,
        Collection $providerServiceMap,
        float $markup,
        \Illuminate\Support\Carbon $now,
    ): array {
        $providerServiceIds = $providerServiceMap->values()->filter()->all();

        $existingByProviderServiceId = Service::query()
            ->whereIn('provider_service_id', $providerServiceIds)
            ->get(['id', 'provider_service_id', 'name', 'description', 'meta', 'is_active'])
            ->keyBy('provider_service_id');

        $serviceRows = [];
        $created = 0;
        $updated = 0;
        $reactivated = 0;
        $reactivatedNames = [];

        foreach ($providerRows as $row) {
            $externalId = (int) $row['external_id'];
            $providerServiceId = (int) ($providerServiceMap[$externalId] ?? 0);

            if ($providerServiceId <= 0) {
                continue;
            }

            $item = $row['_item'];
            $platform = $this->detectPlatform((string) ($item['category'] ?? $item['name'] ?? ''));
            $name = $row['name'];
            $existing = $existingByProviderServiceId->get($providerServiceId);

            if ($existing) {
                $updated++;

                if (! (bool) $existing->is_active) {
                    $reactivated++;

                    if (count($reactivatedNames) < 6) {
                        $reactivatedNames[] = (string) $existing->name;
                    }
                }
            } else {
                $created++;
            }

            $prevMeta = $existing?->meta;

            if (is_string($prevMeta)) {
                $prevMeta = json_decode($prevMeta, true);
            }

            $prevMeta = is_array($prevMeta) ? $prevMeta : [];
            $meta = array_merge($prevMeta, [
                'external_id' => $externalId,
                'cat_id' => $item['cat_id'] ?? ($prevMeta['cat_id'] ?? null),
                'speed' => $item['speed'] ?? ($prevMeta['speed'] ?? null),
                'jenis' => $item['jenis'] ?? $item['type'] ?? ($prevMeta['jenis'] ?? null),
                'provider_name' => $name,
            ]);

            $description = (string) ($item['note'] ?? $item['desc'] ?? $item['description'] ?? $existing?->description ?? '');

            $serviceRows[] = [
                'provider_service_id' => $providerServiceId,
                'slug' => Str::slug($platform.'-'.$name.'-'.$externalId),
                'platform' => $platform,
                'name' => $name,
                'description' => mb_substr($description, 0, 65535),
                'type' => $row['type'],
                'min' => $row['min'],
                'max' => $row['max'],
                'rate_idr' => $row['rate_idr'],
                'sell_rate_dzd' => $this->pricing->sellRateDzdPerThousand($row['rate_idr']),
                'markup_percent' => $markup,
                'refill' => $row['refill'],
                'dripfeed' => $row['dripfeed'],
                'is_active' => 1,
                'sort_order' => 0,
                'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($serviceRows, 250) as $chunk) {
            Service::query()->upsert(
                $chunk,
                ['provider_service_id'],
                ['slug', 'platform', 'name', 'description', 'type', 'min', 'max', 'rate_idr', 'sell_rate_dzd', 'markup_percent', 'refill', 'dripfeed', 'is_active', 'meta', 'updated_at'],
            );
        }

        return [
            'updated' => $updated,
            'created' => $created,
            'reactivated' => $reactivated,
            'reactivated_names' => $reactivatedNames,
        ];
    }

    protected function markProviderServicesPendingSync(Provider $provider): void
    {
        ProviderService::query()
            ->where('provider_id', $provider->id)
            ->update(['synced_at' => null]);
    }

    /**
     * Keep provider rows alive when BuzzerPanel still lists the service, even if rate parsing skipped an upsert row.
     *
     * @param  list<int>  $seenExternalIds
     */
    protected function touchSeenProviderServices(Provider $provider, array $seenExternalIds, \Illuminate\Support\Carbon $now): void
    {
        if ($seenExternalIds === []) {
            return;
        }

        foreach (array_chunk($seenExternalIds, 500) as $chunk) {
            ProviderService::query()
                ->where('provider_id', $provider->id)
                ->whereIn('external_id', $chunk)
                ->update([
                    'is_active' => true,
                    'synced_at' => $now,
                    'updated_at' => $now,
                ]);
        }
    }

    protected function reactivateLinkedServices(Provider $provider): void
    {
        Service::query()
            ->where('is_active', false)
            ->whereHas('providerService', function ($query) use ($provider): void {
                $query
                    ->where('provider_id', $provider->id)
                    ->where('is_active', true);
            })
            ->update(['is_active' => true]);
    }

    protected function deactivateStaleProviderServices(Provider $provider): void
    {
        ProviderService::query()
            ->where('provider_id', $provider->id)
            ->whereNull('synced_at')
            ->update(['is_active' => false]);
    }

    protected function deactivateStaleServices(Provider $provider): array
    {
        $ids = Service::query()
            ->where('is_active', true)
            ->whereHas('providerService', function ($query) use ($provider): void {
                $query
                    ->where('provider_id', $provider->id)
                    ->where('is_active', false);
            })
            ->pluck('id');

        $names = Service::query()
            ->whereIn('id', $ids)
            ->limit(6)
            ->pluck('name')
            ->all();

        $count = Service::query()
            ->whereIn('id', $ids)
            ->update(['is_active' => false]);

        return [
            'count' => $count,
            'names' => $names,
        ];
    }

    protected function detectPlatform(string $label): string
    {
        $value = strtolower($label);

        foreach ([
            'instagram' => 'instagram',
            'tiktok' => 'tiktok',
            'facebook' => 'facebook',
            'youtube' => 'youtube',
            'twitter' => 'twitter',
            'threads' => 'threads',
            'telegram' => 'telegram',
            'spotify' => 'spotify',
            'linkedin' => 'linkedin',
        ] as $needle => $platform) {
            if (str_contains($value, $needle)) {
                return $platform;
            }
        }

        return 'other';
    }

    protected function normalizeRateIdr(mixed $value): string
    {
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }

        $rate = (float) $value;

        if (! is_finite($rate) || $rate < 0) {
            $rate = 0;
        }

        $rate = min($rate, 999999999999999999999999.9999);

        return number_format($rate, 4, '.', '');
    }

    protected function normalizeServiceType(array $item): string
    {
        foreach (['type', 'jenis', 'service_type'] as $key) {
            $value = trim((string) ($item[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return 'default';
    }

    protected function applyWebCatalogVisibility(Provider $provider): void
    {
        $disabled = config('catalog.web_disabled_platforms', []);

        if ($disabled === []) {
            return;
        }

        Service::query()
            ->whereIn('platform', $disabled)
            ->whereHas('providerService', fn ($q) => $q->where('provider_id', $provider->id))
            ->update(['is_active' => false]);

        CatalogPlatform::query()
            ->whereIn('slug', $disabled)
            ->update(['is_active' => false]);

        CatalogPlatform::query()
            ->whereNotIn('slug', $disabled)
            ->update(['is_active' => true]);

        Cache::forget('catalog.platforms_with_counts');
    }
}
