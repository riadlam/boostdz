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

use Filament\Tables\Columns\SelectColumn;

use Filament\Tables\Columns\TextColumn;

use Filament\Tables\Concerns\InteractsWithTable;

use Filament\Tables\Contracts\HasTable;

use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Collection;

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



    /** @var array<int, array<string, string>>|null */

    protected ?array $serviceOptionsByCategory = null;



    public function mount(): void

    {

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

                SelectColumn::make('featured_service_id')

                    ->label('Featured service')

                    ->placeholder('— None —')

                    ->options(fn (CatalogCategory $record): array => $this->getServiceOptionsForCategory($record->id))

                    ->searchable()

                    ->afterStateUpdated(function (mixed $state, CatalogCategory $record) use ($health): void {

                        $this->saveFeaturedSelection($record, $state, $health);

                    }),

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



    protected function getCategoriesQuery(): Builder

    {

        return CatalogCategory::query()

            ->with(['platform', 'featuredService'])

            ->where('catalog_categories.is_active', true)

            ->join('catalog_platforms', 'catalog_platforms.id', '=', 'catalog_categories.platform_id')

            ->where('catalog_platforms.is_active', true)

            ->when(

                filled($this->platformSlug),

                fn (Builder $query) => $query->where('catalog_platforms.slug', $this->platformSlug),

            )

            ->withCount([

                'services as active_services_count' => fn (Builder $query) => $query->where('is_active', true),

            ])

            ->orderBy('catalog_platforms.sort_order')

            ->orderBy('catalog_categories.sort_order')

            ->orderBy('catalog_categories.name')

            ->select('catalog_categories.*');

    }



    /**

     * @return array<string, string>

     */

    protected function getServiceOptionsForCategory(int $categoryId): array

    {

        $this->loadServiceOptions();



        return $this->serviceOptionsByCategory[$categoryId] ?? [];

    }



    protected function loadServiceOptions(): void

    {

        if ($this->serviceOptionsByCategory !== null) {

            return;

        }



        $this->serviceOptionsByCategory = Service::query()

            ->where('is_active', true)

            ->orderBy('sort_order')

            ->orderBy('name')

            ->get(['id', 'catalog_category_id', 'name'])

            ->groupBy('catalog_category_id')

            ->map(fn (Collection $services) => $services

                ->mapWithKeys(fn (Service $service): array => [

                    (string) $service->id => '#'.$service->id.' · '.$service->name,

                ])

                ->all()

            )

            ->all();

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


