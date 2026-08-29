<?php

namespace App\Services\Catalog;

use App\Models\CatalogCategory;
use App\Models\Service;
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

    public function __construct(private readonly TelegramClient $telegram) {}

    public function featuredServiceStatus(CatalogCategory $category): string
    {
        if (! $category->featured_service_id) {
            return self::STATUS_MISSING;
        }

        $service = $category->relationLoaded('featuredService')
            ? $category->featuredService
            : $category->featuredService()->first();

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
        if (! Schema::hasColumn('catalog_categories', 'featured_service_id')) {
            return CatalogCategory::query()->whereRaw('0 = 1');
        }

        return CatalogCategory::query()
            ->with(['platform', 'featuredService'])
            ->where('catalog_categories.is_active', true)
            ->where(function (Builder $query): void {
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

    public function checkAndNotifyCategory(CatalogCategory $category): void
    {
        $category->loadMissing(['platform', 'featuredService']);

        $status = $this->featuredServiceStatus($category);

        if ($status === self::STATUS_OK) {
            if ($category->featured_alert_sent_at !== null) {
                $category->forceFill(['featured_alert_sent_at' => null])->save();
            }

            $this->clearStorefrontCache();

            return;
        }

        if (! $this->shouldSendAlert($category)) {
            return;
        }

        if (! config('catalog.notify_sync_events', true)) {
            return;
        }

        try {
            $this->telegram->notifyFeaturedServiceIssue($category, $this->describeIssue($category));
        } catch (\Throwable) {
            // Do not break admin UI or sync jobs when Telegram is misconfigured.
        }

        $category->forceFill(['featured_alert_sent_at' => now()])->save();
        $this->clearStorefrontCache();
    }

    public function checkAndNotifyAll(): void
    {
        foreach ($this->issuesQuery()->cursor() as $category) {
            $this->checkAndNotifyCategory($category);
        }
    }

    public function checkAndNotifyForService(Service $service): void
    {
        $categories = CatalogCategory::query()
            ->with(['platform', 'featuredService'])
            ->where('featured_service_id', $service->id)
            ->get();

        foreach ($categories as $category) {
            $this->checkAndNotifyCategory($category);
        }
    }

    protected function shouldSendAlert(CatalogCategory $category): bool
    {
        if ($category->featured_alert_sent_at === null) {
            return true;
        }

        return $category->featured_alert_sent_at->lt(now()->subHour());
    }
}

