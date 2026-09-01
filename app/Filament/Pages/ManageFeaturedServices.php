<?php

namespace App\Filament\Pages;

use App\Models\CatalogCategory;
use App\Models\CatalogPlatform;
use App\Models\Service;
use App\Services\Catalog\FeaturedServiceHealth;
use App\Support\CatalogTier;
use App\Support\FormatMoney;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Url;
use UnitEnum;

class ManageFeaturedServices extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Category packages';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Fulfillment';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Category packages';

    protected static ?string $slug = 'manage-featured-services';

    protected string $view = 'filament.pages.manage-featured-services';

    #[Url(as: 'platform')]
    public ?string $platformSlug = null;

    public bool $needsMigration = false;

    public function mount(): void
    {
        if (! Schema::hasColumn('catalog_categories', 'featured_service_id')
            || ! Schema::hasColumn('catalog_categories', 'basic_service_id')) {
            $this->needsMigration = true;

            return;
        }

        if (blank($this->platformSlug)) {
            $this->platformSlug = CatalogPlatform::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->value('slug');
        }
    }

    public function selectPlatform(string $slug): void
    {
        $this->platformSlug = $slug;
        $this->resetTable();
    }

    /** @return Collection<int, CatalogPlatform> */
    public function getPlatformsProperty(): Collection
    {
        return CatalogPlatform::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function table(Table $table): Table
    {
        if ($this->needsMigration) {
            return $table
                ->query(fn (): Builder => CatalogCategory::query()->whereRaw('0 = 1'))
                ->columns([])
                ->emptyStateHeading('Database update required')
                ->emptyStateDescription('Run php artisan migrate --force on the server to enable storefront defaults.');
        }

        $health = app(FeaturedServiceHealth::class);

        $platformName = CatalogPlatform::query()
            ->where('slug', $this->platformSlug)
            ->value('name') ?? 'Platform';

        return $table
            ->query(fn (): Builder => $this->getCategoriesQuery())
            ->heading("{$platformName} — assign a service for each package tier per category")
            ->description('Example: for Followers, pick which service runs when a customer chooses Basic, Gold, or Premium. Repeat for Comments, Likes, and every other category.')
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([10, 25, 50])
            ->columns([
                TextColumn::make('name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('active_services_count')
                    ->label('Active services')
                    ->numeric(),
                ViewColumn::make('basic_service_id')
                    ->label('Basic')
                    ->extraCellAttributes(['class' => 'align-top'])
                    ->view('filament.tables.columns.featured-service-select')
                    ->viewData(fn (CatalogCategory $record): array => [
                        'tier' => CatalogTier::BASIC,
                        'options' => $this->getServiceOptionsForCategory($record, CatalogTier::BASIC),
                    ]),
                ViewColumn::make('gold_service_id')
                    ->label('Gold')
                    ->extraCellAttributes(['class' => 'align-top'])
                    ->view('filament.tables.columns.featured-service-select')
                    ->viewData(fn (CatalogCategory $record): array => [
                        'tier' => CatalogTier::GOLD,
                        'options' => $this->getServiceOptionsForCategory($record, CatalogTier::GOLD),
                    ]),
                ViewColumn::make('premium_service_id')
                    ->label('Premium')
                    ->extraCellAttributes(['class' => 'align-top'])
                    ->view('filament.tables.columns.featured-service-select')
                    ->viewData(fn (CatalogCategory $record): array => [
                        'tier' => CatalogTier::PREMIUM,
                        'options' => $this->getServiceOptionsForCategory($record, CatalogTier::PREMIUM),
                    ]),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (CatalogCategory $record): string => match ($health->featuredServiceStatus($record)) {
                        FeaturedServiceHealth::STATUS_OK => 'OK',
                        FeaturedServiceHealth::STATUS_MISSING => 'Missing basic',
                        FeaturedServiceHealth::STATUS_INACTIVE => 'Inactive',
                        FeaturedServiceHealth::STATUS_WRONG_CATEGORY => 'Mismatch',
                        default => 'Issue',
                    })
                    ->color(fn (CatalogCategory $record): string => match ($health->featuredServiceStatus($record)) {
                        FeaturedServiceHealth::STATUS_OK => 'success',
                        default => 'danger',
                    }),
            ]);
    }

    public function updateTierService(int $categoryId, string $tier, mixed $serviceId): void
    {
        if (! CatalogTier::isValid($tier)) {
            return;
        }

        $record = CatalogCategory::query()->findOrFail($categoryId);
        $health = app(FeaturedServiceHealth::class);

        $this->saveTierSelection(
            $record,
            $tier,
            filled($serviceId) ? $serviceId : null,
            $health,
        );
    }

    /** @deprecated Use updateTierService */
    public function updateFeaturedService(int $categoryId, mixed $serviceId): void
    {
        $this->updateTierService($categoryId, CatalogTier::BASIC, $serviceId);
    }

    /** @return array<string, string> */
    protected function getServiceOptionsForCategory(CatalogCategory $category, string $tier): array
    {
        $categoryId = $category->id;
        $column = CatalogTier::serviceColumn($tier);

        $options = Service::query()
            ->where('catalog_category_id', $categoryId)
            ->where('is_active', true)
            ->orderBy('sell_rate_dzd')
            ->orderBy('name')
            ->limit(250)
            ->get(['id', 'name', 'sell_rate_dzd'])
            ->mapWithKeys(fn (Service $service): array => [
                (string) $service->id => $this->formatServiceOptionLabel($service),
            ])
            ->all();

        $selectedId = $category->{$column};

        if ($tier === CatalogTier::BASIC && ! $selectedId && $category->featured_service_id) {
            $selectedId = $category->featured_service_id;
        }

        if ($selectedId !== null) {
            $selectedKey = (string) $selectedId;

            if (! array_key_exists($selectedKey, $options)) {
                $selected = Service::query()->find($selectedId);

                if ($selected) {
                    $suffix = ! $selected->is_active ? ' (inactive)' : '';

                    if ($selected->catalog_category_id !== $categoryId) {
                        $suffix = ' (wrong category)';
                    }

                    $options[$selectedKey] = $this->formatServiceOptionLabel($selected).$suffix;
                }
            }
        }

        ksort($options, SORT_NUMERIC);

        return $options;
    }

    protected function formatServiceOptionLabel(Service $service): string
    {
        return '#'.$service->id.' · '.FormatMoney::dzdPerThousand($service->sell_rate_dzd).' · '.$service->name;
    }

    protected function getCategoriesQuery(): Builder
    {
        return CatalogCategory::query()
            ->with(['platform', 'featuredService', 'basicService', 'goldService', 'premiumService'])
            ->where('catalog_categories.is_active', true)
            ->whereHas('platform', function (Builder $query): void {
                $query->where('is_active', true);

                if (filled($this->platformSlug)) {
                    $query->where('slug', $this->platformSlug);
                }
            })
            ->withCount([
                'services as active_services_count' => fn (Builder $query) => $query
                    ->where('services.is_active', true),
            ])
            ->join('catalog_platforms', 'catalog_platforms.id', '=', 'catalog_categories.platform_id')
            ->orderBy('catalog_platforms.sort_order')
            ->orderBy('catalog_categories.sort_order')
            ->orderBy('catalog_categories.name')
            ->select('catalog_categories.*');
    }

    protected function saveTierSelection(
        CatalogCategory $record,
        string $tier,
        mixed $state,
        FeaturedServiceHealth $health,
    ): void {
        $serviceId = filled($state) ? (int) $state : null;
        $column = CatalogTier::serviceColumn($tier);

        if ($serviceId) {
            $valid = Service::query()
                ->where('id', $serviceId)
                ->where('catalog_category_id', $record->id)
                ->where('is_active', true)
                ->exists();

            if (! $valid) {
                Notification::make()
                    ->title('Invalid service selection')
                    ->body('Choose an active service from this category.')
                    ->danger()
                    ->send();

                return;
            }
        }

        $payload = [
            $column => $serviceId,
            'featured_alert_sent_at' => null,
        ];

        if ($tier === CatalogTier::BASIC) {
            $payload['featured_service_id'] = $serviceId;
        }

        $record->forceFill($payload)->save();

        $health->clearStorefrontCache();

        if ($tier === CatalogTier::BASIC && $serviceId === null) {
            $health->checkAndNotifyCategory($record->fresh(['platform', 'featuredService', 'basicService']));
        }

        $label = ucfirst($tier);

        Notification::make()
            ->title("{$label} tier saved")
            ->success()
            ->send();
    }
}
