<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\CatalogCategory;
use App\Models\CatalogPlatform;
use App\Models\Service;
use App\Services\Catalog\FeaturedServiceHealth;
use App\Services\Pricing\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CatalogController extends Controller
{
    public function platforms(): JsonResponse
    {
        $platforms = Cache::remember('catalog.platforms_with_counts', 300, function () {
            return CatalogPlatform::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->withCount([
                    'categories as categories_count' => fn ($q) => $q->where('is_active', true),
                ])
                ->get()
                ->map(function (CatalogPlatform $platform) {
                    $servicesCount = Service::query()
                        ->where('is_active', true)
                        ->where('platform', $platform->slug)
                        ->whereNotNull('catalog_category_id')
                        ->count();

                    return [
                        'id' => $platform->id,
                        'slug' => $platform->slug,
                        'name' => $platform->name,
                        'icon_key' => $platform->icon_key,
                        'sort_order' => $platform->sort_order,
                        'categories_count' => (int) $platform->categories_count,
                        'services_count' => $servicesCount,
                    ];
                })
                ->values()
                ->all();
        });

        return response()->json([
            'platforms' => $platforms,
        ]);
    }

    public function categories(string $slug, FeaturedServiceHealth $featuredHealth): JsonResponse
    {
        $platform = CatalogPlatform::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $categories = CatalogCategory::query()
            ->where('platform_id', $platform->id)
            ->where('is_active', true)
            ->with('featuredService')
            ->withCount([
                'services as services_count' => fn ($q) => $q->where('is_active', true),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (CatalogCategory $category) use ($featuredHealth) {
                $status = $featuredHealth->featuredServiceStatus($category);

                return [
                    'id' => $category->id,
                    'slug' => $category->slug,
                    'name' => $category->name,
                    'sort_order' => $category->sort_order,
                    'services_count' => (int) $category->services_count,
                    'default_service_id' => $status === FeaturedServiceHealth::STATUS_OK
                        ? (int) $category->featured_service_id
                        : null,
                ];
            })
            ->values();

        return response()->json([
            'platform' => [
                'id' => $platform->id,
                'slug' => $platform->slug,
                'name' => $platform->name,
                'icon_key' => $platform->icon_key,
            ],
            'categories' => $categories,
        ]);
    }

    public function storefront(PricingService $pricing, FeaturedServiceHealth $featuredHealth): JsonResponse
    {
        $items = Cache::remember(FeaturedServiceHealth::CACHE_KEY, 300, function () use ($pricing, $featuredHealth) {
            return CatalogCategory::query()
                ->with(['platform', 'featuredService'])
                ->where('catalog_categories.is_active', true)
                ->whereNotNull('catalog_categories.featured_service_id')
                ->join('catalog_platforms', 'catalog_platforms.id', '=', 'catalog_categories.platform_id')
                ->where('catalog_platforms.is_active', true)
                ->orderBy('catalog_platforms.sort_order')
                ->orderBy('catalog_categories.sort_order')
                ->orderBy('catalog_categories.name')
                ->select('catalog_categories.*')
                ->get()
                ->filter(fn (CatalogCategory $category): bool => $featuredHealth->featuredServiceStatus($category) === FeaturedServiceHealth::STATUS_OK)
                ->map(function (CatalogCategory $category) use ($pricing) {
                    $service = $category->featuredService;
                    $startingAmount = max(1, (int) $service->min);
                    $pricePer1k = (int) $pricing->quote($service, 1000)->charge_dzd;
                    $startingPrice = (int) $pricing->quote($service, $startingAmount)->charge_dzd;

                    return [
                        'platform' => [
                            'slug' => $category->platform->slug,
                            'name' => $category->platform->name,
                        ],
                        'category' => [
                            'id' => $category->id,
                            'slug' => $category->slug,
                            'name' => $category->name,
                        ],
                        'service' => ServiceResource::make($service)->resolve(),
                        'price_per_1k_dzd' => $pricePer1k,
                        'starting_price_dzd' => $startingPrice,
                        'min' => (int) $service->min,
                        'max' => (int) $service->max,
                    ];
                })
                ->values()
                ->all();
        });

        return response()->json([
            'items' => $items,
            'meta' => [
                'total' => count($items),
            ],
        ]);
    }

    public function services(Request $request, CatalogCategory $category, FeaturedServiceHealth $featuredHealth): JsonResponse
    {
        abort_unless($category->is_active, 404);

        $category->loadMissing('featuredService');
        $defaultServiceId = $featuredHealth->featuredServiceStatus($category) === FeaturedServiceHealth::STATUS_OK
            ? (int) $category->featured_service_id
            : null;

        $query = Service::query()
            ->where('is_active', true)
            ->where('catalog_category_id', $category->id);

        if ($tier = $request->string('quality_tier')->toString()) {
            $query->where('quality_tier', $tier);
        }

        if ($request->has('refill')) {
            $query->where('refill', $request->boolean('refill'));
        }

        if ($request->has('has_refill')) {
            $query->where('refill', $request->boolean('has_refill'));
        }

        if ($request->has('dripfeed')) {
            $query->where('dripfeed', $request->boolean('dripfeed'));
        }

        if ($request->has('is_hot')) {
            $query->where('is_hot', $request->boolean('is_hot'));
        }

        if ($request->has('is_cheap')) {
            $query->where('is_cheap', $request->boolean('is_cheap'));
        }

        if ($startClass = $request->string('start_class')->toString()) {
            $classes = array_values(array_filter(array_map('trim', explode(',', $startClass))));
            if ($classes !== []) {
                $query->whereIn('start_class', $classes);
            }
        }

        if ($refillMode = $request->string('refill_mode')->toString()) {
            $modes = array_values(array_filter(array_map('trim', explode(',', $refillMode))));
            if ($modes !== []) {
                $query->whereIn('refill_mode', $modes);
            }
        }

        if ($countryCode = $request->string('country_code')->toString()) {
            $codes = array_values(array_filter(array_map(
                static fn (string $code): string => strtolower(trim($code)),
                explode(',', $countryCode),
            )));
            if ($codes !== []) {
                $query->whereIn('country_code', $codes);
            }
        }

        if ($audienceGender = $request->string('audience_gender')->toString()) {
            $genders = array_values(array_filter(array_map(
                static fn (string $gender): string => strtolower(trim($gender)),
                explode(',', $audienceGender),
            )));
            if ($genders !== []) {
                $query->whereIn('audience_gender', $genders);
            }
        }

        if ($reactionType = $request->string('reaction_type')->toString()) {
            $reactions = array_values(array_filter(array_map(
                static fn (string $reaction): string => strtolower(trim($reaction)),
                explode(',', $reactionType),
            )));
            if ($reactions !== []) {
                $query->whereIn('reaction_type', $reactions);
            }
        }

        if ($request->filled('refill_days_min')) {
            $query->where('refill_days', '>=', (int) $request->input('refill_days_min'));
        }

        if ($refillDays = $request->string('refill_days')->toString()) {
            $days = array_values(array_filter(array_map(
                static fn (string $day): int => (int) trim($day),
                explode(',', $refillDays),
            ), static fn (int $day): bool => $day > 0));
            if ($days !== []) {
                $query->whereIn('refill_days', $days);
            }
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $limit = min(max((int) $request->input('per_page', 200), 1), 400);

        $services = $query
            ->orderByDesc('is_hot')
            ->orderByRaw("FIELD(quality_tier, 'premium', 'standard', 'economy')")
            ->orderByRaw("FIELD(COALESCE(start_class, 'normal'), 'instant', 'fast', 'normal', 'slow')")
            ->orderByRaw("FIELD(COALESCE(refill_mode, 'none'), 'lifetime', 'auto', 'manual', 'none')")
            ->orderByDesc('refill_days')
            ->orderBy('sell_rate_dzd')
            ->orderBy('name')
            ->limit($limit)
            ->get();

        if ($defaultServiceId && ! $services->contains(fn (Service $service): bool => (int) $service->id === $defaultServiceId)) {
            if ((clone $query)->where('id', $defaultServiceId)->exists()) {
                $featured = Service::query()
                    ->where('id', $defaultServiceId)
                    ->where('is_active', true)
                    ->where('catalog_category_id', $category->id)
                    ->first();

                if ($featured) {
                    $services = $services->prepend($featured)->unique('id')->values();
                }
            }
        }

        $groups = [];

        foreach ($services as $service) {
            $label = $this->groupLabel($service);
            $key = strtolower(str_replace([' · ', ' '], ['_', '_'], $label));

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'label' => $label,
                    'items' => [],
                ];
            }

            $groups[$key]['items'][] = ServiceResource::make($service)->resolve();
        }

        return response()->json([
            'category' => [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->name,
                'platform_id' => $category->platform_id,
                'default_service_id' => $defaultServiceId,
            ],
            'groups' => array_values($groups),
            'meta' => [
                'total' => $services->count(),
                'limit' => $limit,
            ],
        ]);
    }

    protected function groupLabel(Service $service): string
    {
        $tier = (string) ($service->quality_tier ?: 'standard');
        $parts = [
            __('api.catalog.quality.'.$tier),
        ];

        $mode = (string) ($service->refill_mode ?: 'none');
        $days = $service->refill_days;

        $parts[] = match ($mode) {
            'auto' => $days
                ? __('api.catalog.refill.auto_days', ['days' => $days])
                : __('api.catalog.refill.auto'),
            'manual' => $days
                ? __('api.catalog.refill.manual_days', ['days' => $days])
                : __('api.catalog.refill.manual'),
            'lifetime' => __('api.catalog.refill.lifetime'),
            default => __('api.catalog.refill.none'),
        };

        $start = (string) ($service->start_class ?: 'normal');
        $parts[] = match ($start) {
            'instant' => __('api.catalog.start.instant'),
            'fast' => __('api.catalog.start.fast'),
            'slow' => __('api.catalog.start.slow'),
            default => __('api.catalog.start.normal'),
        };

        if ($service->dripfeed) {
            $parts[] = __('api.catalog.drip_feed');
        }
        if ($service->is_hot) {
            $parts[] = __('api.catalog.top');
        }
        if ($service->is_cheap) {
            $parts[] = __('api.catalog.cheap');
        }

        return implode(' · ', $parts);
    }
}
