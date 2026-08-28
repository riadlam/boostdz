<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\CatalogCategory;
use App\Models\CatalogPlatform;
use App\Models\Service;
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

    public function categories(string $slug): JsonResponse
    {
        $platform = CatalogPlatform::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $categories = CatalogCategory::query()
            ->where('platform_id', $platform->id)
            ->where('is_active', true)
            ->withCount([
                'services as services_count' => fn ($q) => $q->where('is_active', true),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (CatalogCategory $category) => [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->name,
                'sort_order' => $category->sort_order,
                'services_count' => (int) $category->services_count,
            ])
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

    public function services(Request $request, CatalogCategory $category): JsonResponse
    {
        abort_unless($category->is_active, 404);

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
        $parts = [
            ucfirst((string) ($service->quality_tier ?: 'standard')),
        ];

        $mode = (string) ($service->refill_mode ?: 'none');
        $days = $service->refill_days;

        $parts[] = match ($mode) {
            'auto' => $days ? "Auto refill {$days}d" : 'Auto refill',
            'manual' => $days ? "Refill {$days}d" : 'Refill',
            'lifetime' => 'Lifetime refill',
            default => 'No refill',
        };

        $start = (string) ($service->start_class ?: 'normal');
        $parts[] = match ($start) {
            'instant' => 'Instant',
            'fast' => 'Fast',
            'slow' => 'Slow',
            default => 'Normal start',
        };

        if ($service->dripfeed) {
            $parts[] = 'Drip-feed';
        }
        if ($service->is_hot) {
            $parts[] = 'Top';
        }
        if ($service->is_cheap) {
            $parts[] = 'Cheap';
        }

        return implode(' · ', $parts);
    }
}
