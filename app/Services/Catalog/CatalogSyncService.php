<?php

namespace App\Services\Catalog;

use App\Models\CatalogPlatform;
use App\Models\CatalogSyncEvent;
use App\Models\Provider;
use App\Models\ProviderService;
use App\Models\ProviderSyncLog;
use App\Models\Service;
use App\Services\BuzzerPanel\BuzzerPanelClient;
use App\Services\BuzzerPanel\BuzzerPanelException;
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
    ) {}

    public function sync(Provider $provider): ProviderSyncLog
    {
        $started = microtime(true);
        $client = BuzzerPanelClient::fromProvider($provider);

        try {
            $remoteServices = $client->services();
            $markup = $this->pricing->markupPercent();
            $now = now();
            $providerRows = $this->buildProviderRows($provider, $remoteServices, $now);
            $synced = count($providerRows);
            $stats = [
                'updated' => 0,
                'created' => 0,
                'new_skipped' => 0,
                'name_changes' => 0,
                'deactivated' => 0,
            ];

            /** @var Collection<int, CatalogSyncEvent> $events */
            $events = collect();

            DB::transaction(function () use ($provider, $providerRows, $markup, $now, &$stats, &$events): void {
                $this->upsertProviderServices($providerRows);

                $providerServiceMap = ProviderService::query()
                    ->where('provider_id', $provider->id)
                    ->pluck('id', 'external_id');

                if ($this->isUpdateOnlyMode()) {
                    [$stats, $events] = $this->syncExistingServicesOnly(
                        $provider,
                        $providerRows,
                        $providerServiceMap,
                        $markup,
                        $now,
                    );
                } else {
                    $stats = $this->syncAllServices(
                        $provider,
                        $providerRows,
                        $providerServiceMap,
                        $markup,
                        $now,
                    );
                }

                $this->deactivateStaleProviderServices($provider, $now);
                $stats['deactivated'] = $this->deactivateStaleServices($provider, $now);
            });

            CatalogClassifier::clearCache();
            $this->applyWebCatalogVisibility($provider);
            $this->notifySyncEvents($events);

            $mode = $this->isUpdateOnlyMode() ? 'update-only' : 'full';
            $message = sprintf(
                'Synced %d provider rows (%s). Updated %d, deactivated %d, skipped new %d, name changes %d.',
                $synced,
                $mode,
                $stats['updated'] + ($stats['created'] ?? 0),
                $stats['deactivated'],
                $stats['new_skipped'],
                $stats['name_changes'],
            );

            return ProviderSyncLog::query()->create([
                'provider_id' => $provider->id,
                'type' => 'catalog',
                'status' => 'success',
                'records_synced' => $synced,
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
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

    protected function isUpdateOnlyMode(): bool
    {
        return config('catalog.sync_mode', 'update_only') !== 'full';
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

            $rateIdr = $this->normalizeRateIdr($item['price'] ?? $item['rate'] ?? 0);

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
     * @return array{0: array<string, int>, 1: Collection<int, CatalogSyncEvent>}
     */
    protected function syncExistingServicesOnly(
        Provider $provider,
        array $providerRows,
        Collection $providerServiceMap,
        float $markup,
        \Illuminate\Support\Carbon $now,
    ): array {
        $stats = [
            'updated' => 0,
            'created' => 0,
            'new_skipped' => 0,
            'name_changes' => 0,
            'deactivated' => 0,
        ];

        $existingByExternal = DB::table('services')
            ->join('provider_services', 'services.provider_service_id', '=', 'provider_services.id')
            ->where('provider_services.provider_id', $provider->id)
            ->select([
                'services.id',
                'services.name',
                'services.description',
                'services.meta',
                'provider_services.external_id as external_id',
            ])
            ->get()
            ->keyBy('external_id');

        $events = collect();

        foreach ($providerRows as $row) {
            $externalId = (int) $row['external_id'];
            $providerServiceId = (int) ($providerServiceMap[$externalId] ?? 0);

            if ($providerServiceId <= 0) {
                continue;
            }

            $existing = $existingByExternal->get($externalId);

            if (! $existing) {
                $stats['new_skipped']++;
                if (! $this->hasRecentSyncEvent($provider->id, CatalogSyncEvent::TYPE_NEW_PROVIDER_SERVICE, $externalId)) {
                    $events->push($this->recordSyncEvent(
                        provider: $provider,
                        type: CatalogSyncEvent::TYPE_NEW_PROVIDER_SERVICE,
                        externalId: $externalId,
                        providerServiceId: $providerServiceId,
                        oldValue: null,
                        newValue: $row['name'],
                    ));
                }

                continue;
            }

            $item = $row['_item'];
            $meta = json_decode((string) ($existing->meta ?? ''), true);
            $meta = is_array($meta) ? $meta : [];
            $previousProviderName = (string) ($meta['provider_name'] ?? $existing->name);

            if ($row['name'] !== $previousProviderName && $row['name'] !== $existing->name) {
                if (! $this->hasRecentNameChangeEvent($provider->id, (int) $existing->id, $row['name'])) {
                    $stats['name_changes']++;
                    $events->push($this->recordSyncEvent(
                        provider: $provider,
                        type: CatalogSyncEvent::TYPE_NAME_CHANGED,
                        externalId: $externalId,
                        providerServiceId: $providerServiceId,
                        serviceId: (int) $existing->id,
                        oldValue: $existing->name,
                        newValue: $row['name'],
                    ));
                }
            }

            $meta = array_merge($meta, [
                'external_id' => $externalId,
                'cat_id' => $item['cat_id'] ?? ($meta['cat_id'] ?? null),
                'speed' => $item['speed'] ?? ($meta['speed'] ?? null),
                'jenis' => $item['jenis'] ?? $item['type'] ?? ($meta['jenis'] ?? null),
                'provider_name' => $row['name'],
            ]);

            Service::query()->whereKey((int) $existing->id)->update([
                'rate_idr' => $row['rate_idr'],
                'sell_rate_dzd' => $this->pricing->sellRateDzdPerThousand($row['rate_idr']),
                'markup_percent' => $markup,
                'min' => $row['min'],
                'max' => $row['max'],
                'refill' => (bool) $row['refill'],
                'dripfeed' => (bool) $row['dripfeed'],
                'is_active' => true,
                'description' => mb_substr((string) ($item['note'] ?? $item['desc'] ?? $item['description'] ?? $existing->description ?? ''), 0, 65535),
                'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                'updated_at' => $now,
            ]);
            $stats['updated']++;
        }

        return [$stats, $events];
    }

    /**
     * @param  \Illuminate\Support\Collection<int|string, int>  $providerServiceMap
     * @return array<string, int>
     */
    protected function syncAllServices(
        Provider $provider,
        array $providerRows,
        Collection $providerServiceMap,
        float $markup,
        \Illuminate\Support\Carbon $now,
    ): array {
        $serviceRows = [];

        foreach ($providerRows as $row) {
            $externalId = (int) $row['external_id'];
            $providerServiceId = $providerServiceMap[$externalId] ?? null;

            if (! $providerServiceId) {
                continue;
            }

            $item = $row['_item'];
            $platform = $this->detectPlatform((string) ($item['category'] ?? $item['name'] ?? ''));
            $name = $row['name'];

            $serviceRows[] = [
                'provider_service_id' => $providerServiceId,
                'slug' => Str::slug($platform.'-'.$name.'-'.$externalId),
                'platform' => $platform,
                'name' => $name,
                'description' => (string) ($item['note'] ?? $item['desc'] ?? $item['description'] ?? ''),
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
                'meta' => json_encode([
                    'external_id' => $externalId,
                    'cat_id' => $item['cat_id'] ?? null,
                    'speed' => $item['speed'] ?? null,
                    'jenis' => $item['jenis'] ?? $item['type'] ?? null,
                ], JSON_UNESCAPED_UNICODE),
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
            'updated' => count($serviceRows),
            'created' => 0,
            'new_skipped' => 0,
            'name_changes' => 0,
            'deactivated' => 0,
        ];
    }

    protected function deactivateStaleProviderServices(Provider $provider, \Illuminate\Support\Carbon $now): void
    {
        ProviderService::query()
            ->where('provider_id', $provider->id)
            ->where(function ($q) use ($now): void {
                $q->whereNull('synced_at')->orWhere('synced_at', '<', $now->copy()->subMinute());
            })
            ->update(['is_active' => false]);
    }

    protected function deactivateStaleServices(Provider $provider, \Illuminate\Support\Carbon $now): int
    {
        return Service::query()
            ->whereHas('providerService', fn ($q) => $q->where('provider_id', $provider->id))
            ->where('updated_at', '<', $now->copy()->subMinute())
            ->update(['is_active' => false]);
    }

    protected function recordSyncEvent(
        Provider $provider,
        string $type,
        ?int $externalId = null,
        ?int $providerServiceId = null,
        ?int $serviceId = null,
        ?string $oldValue = null,
        ?string $newValue = null,
    ): CatalogSyncEvent {
        return CatalogSyncEvent::query()->create([
            'provider_id' => $provider->id,
            'provider_service_id' => $providerServiceId,
            'service_id' => $serviceId,
            'external_id' => $externalId,
            'event_type' => $type,
            'old_value' => $oldValue ? mb_substr($oldValue, 0, 255) : null,
            'new_value' => $newValue ? mb_substr($newValue, 0, 255) : null,
            'status' => CatalogSyncEvent::STATUS_PENDING,
        ]);
    }

    protected function hasRecentSyncEvent(int $providerId, string $type, int $externalId): bool
    {
        return CatalogSyncEvent::query()
            ->where('provider_id', $providerId)
            ->where('event_type', $type)
            ->where('external_id', $externalId)
            ->where('created_at', '>=', now()->subDays(7))
            ->exists();
    }

    protected function hasRecentNameChangeEvent(int $providerId, int $serviceId, string $newName): bool
    {
        return CatalogSyncEvent::query()
            ->where('provider_id', $providerId)
            ->where('event_type', CatalogSyncEvent::TYPE_NAME_CHANGED)
            ->where('service_id', $serviceId)
            ->where('new_value', mb_substr($newName, 0, 255))
            ->where('created_at', '>=', now()->subDays(30))
            ->exists();
    }

    /**
     * @param  Collection<int, CatalogSyncEvent>  $events
     */
    protected function notifySyncEvents(Collection $events): void
    {
        if ($events->isEmpty() || ! config('catalog.notify_sync_events', true)) {
            return;
        }

        $this->telegram->notifyCatalogSyncEvents($events);
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
