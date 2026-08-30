<?php

namespace App\Filament\Pages;

use App\Models\CatalogCategory;
use App\Models\CatalogPlatform;
use App\Models\Service;
use App\Services\Catalog\FeaturedServiceHealth;
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

    protected static ?string $navigationLabel = 'Storefront defaults';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Fulfillment';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Storefront defaults';

    protected static ?string $slug = 'manage-featured-services';

    protected string $view = 'filament.pages.manage-featured-services';

    #[Url(as: 'platform')]
    public ?string $platformSlug = null;

    public bool $needsMigration = false;

    /** @var array<int, array<string, string>> */
    protected array $serviceOptionsByCategory = [];

    public function mount(): void
    {
        if (! Schema::hasColumn('catalog_categories', 'featured_service_id')) {
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
        $this->serviceOptionsByCategory = [];
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

        return $table
            ->query(fn (): Builder => $this->getCategoriesQuery())
            ->heading('Pick one default service per category for dashboard presets and pricing.')
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
                ViewColumn::make('featured_service_id')
                    ->label('Featured service')
                    ->view('filament.tables.columns.featured-service-select')
                    ->viewData(fn (CatalogCategory $record): array => [
                        'options' => $this->getServiceOptionsForCategory($record),
                    ]),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (CatalogCategory $record): string => match ($health->featuredServiceStatus($record)) {
                        FeaturedServiceHealth::STATUS_OK => 'OK',
                        FeaturedServiceHealth::STATUS_MISSING => 'Missing',
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

    public function updateFeaturedService(int $categoryId, mixed $serviceId): void
    {
        $record = CatalogCategory::query()->findOrFail($categoryId);
        $health = app(FeaturedServiceHealth::class);

        $this->saveFeaturedSelection(
            $record,
            filled($serviceId) ? $serviceId : null,
            $health,
        );

        $this->serviceOptionsByCategory = [];
        $this->resetTable();
    }

    protected function getCategoriesQuery(): Builder
    {
        return CatalogCategory::query()
            ->with(['platform', 'featuredService'])
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

    /** @return array<string, string> */
    protected function getServiceOptionsForCategory(CatalogCategory $category): array
    {
        $categoryId = $category->id;

        if (isset($this->serviceOptionsByCategory[$categoryId])) {
            return $this->serviceOptionsByCategory[$categoryId];
        }

        $options = Service::query()
            ->where('catalog_category_id', $categoryId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(250)
            ->get()
            ->mapWithKeys(fn (Service $service): array => [
                (string) $service->id => '#'.$service->id.' · '.$service->name,
            ])
            ->all();

        $selectedId = $category->featured_service_id;

        if ($selectedId !== null) {
            $selectedKey = (string) $selectedId;

            if (! array_key_exists($selectedKey, $options)) {
                $selected = $category->relationLoaded('featuredService')
                    ? $category->featuredService
                    : Service::query()->find($selectedId);

                if ($selected) {
                    $suffix = '';

                    if (! $selected->is_active) {
                        $suffix = ' (inactive)';
                    } elseif ($selected->catalog_category_id !== $categoryId) {
                        $suffix = ' (wrong category)';
                    }

                    $options[$selectedKey] = '#'.$selectedId.' · '.$selected->name.$suffix;
                }
            }
        }

        ksort($options, SORT_NUMERIC);

        $this->serviceOptionsByCategory[$categoryId] = $options;

        return $this->serviceOptionsByCategory[$categoryId];
    }

    protected function saveFeaturedSelection(CatalogCategory $record, mixed $state, FeaturedServiceHealth $health): void
    {
        $serviceId = filled($state) ? (int) $state : null;

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

        $record->forceFill([
            'featured_service_id' => $serviceId,
            'featured_alert_sent_at' => null,
        ])->save();

        $health->clearStorefrontCache();

        if ($serviceId === null) {
            $health->checkAndNotifyCategory($record->fresh(['platform', 'featuredService']));
        }

        Notification::make()
            ->title('Storefront default saved')
            ->success()
            ->send();
    }
}
