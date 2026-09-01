<?php

namespace App\Services\Catalog;

use App\Models\CatalogCategory;
use App\Models\Service;
use App\Support\CatalogTier;
use App\Services\Telegram\TelegramClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class FeaturedServiceHealth
{
    public const STATUS_OK = 'ok';

    public const STATUS_MISSING = 'missing';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_WRONG_CATEGORY = 'wrong_category';

    public const CACHE_KEY = 'catalog.storefront';

    public const ISSUES_COUNT_CACHE_KEY = 'catalog.featured_issues_count';

    public const BATCH_ALERT_CACHE_KEY = 'catalog.featured_issues_batch_alert_at';

    /** Platforms/categories that do not require Basic/Gold/Premium package picks. */
    private const EXCLUDED_PLATFORM_SLUG = 'other';

    private const EXCLUDED_CATEGORY_SLUG = 'other';

    public function __construct(private readonly TelegramClient $telegram) {}

    public function featuredServiceStatus(CatalogCategory $category): string
    {
        return $this->tierServiceStatus($category, CatalogTier::BASIC);
    }

    public function tierServiceStatus(CatalogCategory $category, string $tier): string
    {
        $serviceId = $this->tierServiceId($category, $tier);

        if (! $serviceId) {
            return self::STATUS_MISSING;
        }

        $service = $this->resolveTierService($category, $tier);

        if (! $service) {
            return self::STATUS_MISSING;
        }

        if (! $service->is_active) {
            return self::STATUS_INACTIVE;
        }

        if ((int) $service->catalog_category_id !== (int) $category->id) {
            return self::STATUS_WRONG_CATEGORY;
        }

        return self::STATUS_OK;
    }

    public function tierServiceId(CatalogCategory $category, string $tier): ?int
    {
        if (! CatalogTier::isValid($tier)) {
            return null;
        }

        $column = CatalogTier::serviceColumn($tier);
        $serviceId = $category->{$column};

        if ($serviceId) {
            return (int) $serviceId;
        }

        if ($tier === CatalogTier::BASIC && $category->featured_service_id) {
            return (int) $category->featured_service_id;
        }

        return null;
    }

    public function resolveTierService(CatalogCategory $category, string $tier): ?Service
    {
        $serviceId = $this->tierServiceId($category, $tier);

        if (! $serviceId) {
            return null;
        }

        $relation = match ($tier) {
            CatalogTier::GOLD => 'goldService',
            CatalogTier::PREMIUM => 'premiumService',
            default => 'basicService',
        };

        if ($category->relationLoaded($relation)) {
            $service = $category->{$relation};

            if ($service && (int) $service->id === $serviceId) {
                return $service;
            }
        }

        if ($tier === CatalogTier::BASIC && $category->relationLoaded('featuredService')) {
            $service = $category->featuredService;

            if ($service && (int) $service->id === $serviceId) {
                return $service;
            }
        }

        return Service::query()->find($serviceId);
    }

    /**
     * @return list<array{tier: string, service_id: int, available: bool}>
     */
    public function availableTiers(CatalogCategory $category): array
    {
        $tiers = [];

        foreach (CatalogTier::all() as $tier) {
            $serviceId = $this->tierServiceId($category, $tier);

            if (! $serviceId) {
                continue;
            }

            $tiers[] = [
                'tier' => $tier,
                'service_id' => $serviceId,
                'available' => $this->tierServiceStatus($category, $tier) === self::STATUS_OK,
            ];
        }

        return $tiers;
    }

    public function defaultTier(CatalogCategory $category): ?string
    {
        foreach (CatalogTier::all() as $tier) {
            if ($this->tierServiceStatus($category, $tier) === self::STATUS_OK) {
                return $tier;
            }
        }

        return null;
    }

    public function defaultServiceId(CatalogCategory $category): ?int
    {
        $tier = $this->defaultTier($category);

        return $tier ? $this->tierServiceId($category, $tier) : null;
    }

    public function describeIssue(CatalogCategory $category): string
    {
        return match ($this->featuredServiceStatus($category)) {
            self::STATUS_MISSING => 'No storefront default selected.',
            self::STATUS_INACTIVE => 'Featured service is inactive.',
            self::STATUS_WRONG_CATEGORY => 'Featured service no longer belongs to this category.',
            default => 'Storefront default is healthy.',
        };
    }

    public function issuesQuery(): Builder
    {
        if (! Schema::hasColumn('catalog_categories', 'featured_service_id')
            && ! Schema::hasColumn('catalog_categories', 'basic_service_id')) {
            return CatalogCategory::query()->whereRaw('0 = 1');
        }

        $hasBasic = Schema::hasColumn('catalog_categories', 'basic_service_id');

        return CatalogCategory::query()
            ->with(['platform', 'featuredService', 'basicService'])
            ->where('catalog_categories.is_active', true)
            ->where($this->storefrontDefaultsRequiredScope(...))
            ->where(function (Builder $query) use ($hasBasic): void {
                if ($hasBasic) {
                    $query->where(function (Builder $query): void {
                        $query
                            ->whereNull('basic_service_id')
                            ->whereNull('featured_service_id');
                    })
                        ->orWhere(function (Builder $query): void {
                            $query
                                ->whereNotNull('basic_service_id')
                                ->whereDoesntHave('basicService');
                        })
                        ->orWhereHas('basicService', function (Builder $service): void {
                            $service
                                ->where('is_active', false)
                                ->orWhereColumn('services.catalog_category_id', '!=', 'catalog_categories.id');
                        })
                        ->orWhere(function (Builder $query): void {
                            $query
                                ->whereNull('basic_service_id')
                                ->whereNotNull('featured_service_id')
                                ->where(function (Builder $query): void {
                                    $query
                                        ->whereDoesntHave('featuredService')
                                        ->orWhereHas('featuredService', function (Builder $service): void {
                                            $service
                                                ->where('is_active', false)
                                                ->orWhereColumn('services.catalog_category_id', '!=', 'catalog_categories.id');
                                        });
                                });
                        });

                    return;
                }

                $query
                    ->whereNull('featured_service_id')
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->whereNotNull('featured_service_id')
                            ->whereDoesntHave('featuredService');
                    })
                    ->orWhereHas('featuredService', function (Builder $service): void {
                        $service
                            ->where('is_active', false)
                            ->orWhereColumn('services.catalog_category_id', '!=', 'catalog_categories.id');
                    });
            });
    }

    /**
     * @return Collection<int, CatalogCategory>
     */
    public function issues(): Collection
    {
        return $this->issuesQuery()->get()->values();
    }

    public function issueCount(): int
    {
        return Cache::remember(self::ISSUES_COUNT_CACHE_KEY, 60, fn (): int => $this->issuesQuery()->count());
    }

    public function clearStorefrontCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::ISSUES_COUNT_CACHE_KEY);
    }

    public function briefIssueLine(CatalogCategory $category): string
    {
        $category->loadMissing(['platform', 'featuredService']);
        $platform = $category->platform?->name ?? '?';
        $reason = match ($this->featuredServiceStatus($category)) {
            self::STATUS_MISSING => 'missing',
            self::STATUS_INACTIVE => 'inactive',
            self::STATUS_WRONG_CATEGORY => 'wrong category',
            default => 'issue',
        };

        return $platform.' · '.$category->name.' — '.$reason;
    }

    public function checkAndNotifyCategory(CatalogCategory $category): void
    {
        $category->loadMissing(['platform', 'featuredService']);

        if ($this->featuredServiceStatus($category) === self::STATUS_OK) {
            $category->forceFill(['featured_alert_sent_at' => null])->save();
            $this->clearStorefrontCache();
        }

        $this->checkAndNotifyAll();
    }

    public function checkAndNotifyAll(): void
    {
        $issues = $this->issues();

        if ($issues->isEmpty()) {
            Cache::forget(self::BATCH_ALERT_CACHE_KEY);
            $this->clearStorefrontCache();

            return;
        }

        if (! $this->shouldSendBatchAlert() || ! config('catalog.notify_sync_events', true)) {
            return;
        }

        try {
            $lines = $issues->take(12)->map(fn (CatalogCategory $category): string => $this->briefIssueLine($category))->all();
            $this->telegram->notifyFeaturedServiceIssuesBatch($issues->count(), $lines);
        } catch (\Throwable) {
            // Do not break admin UI or sync jobs when Telegram is misconfigured.
            return;
        }

        Cache::put(self::BATCH_ALERT_CACHE_KEY, now(), now()->addHour());

        CatalogCategory::query()
            ->whereIn('id', $issues->pluck('id'))
            ->update(['featured_alert_sent_at' => now()]);

        $this->clearStorefrontCache();
    }

    public function checkAndNotifyForService(Service $service): void
    {
        $this->checkAndNotifyAll();
    }

    protected function shouldSendBatchAlert(): bool
    {
        $sentAt = Cache::get(self::BATCH_ALERT_CACHE_KEY);

        if ($sentAt === null) {
            return true;
        }

        if ($sentAt instanceof \DateTimeInterface) {
            return $sentAt < now()->subHour();
        }

        return true;
    }

    protected function storefrontDefaultsRequiredScope(Builder $query): void
    {
        $query
            ->where('catalog_categories.slug', '!=', self::EXCLUDED_CATEGORY_SLUG)
            ->whereHas('platform', fn (Builder $platform): Builder => $platform->where('slug', '!=', self::EXCLUDED_PLATFORM_SLUG));
    }
}

