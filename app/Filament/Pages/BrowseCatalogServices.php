<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Services\ServiceResource;
use App\Filament\Resources\Services\Tables\ServicesTable;
use App\Models\CatalogCategory;
use App\Models\CatalogPlatform;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use UnitEnum;

class BrowseCatalogServices extends Page implements HasTable
{
    use InteractsWithTable {
        makeTable as makeBaseTable;
    }

    protected static ?string $navigationLabel = 'Services browser';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Services by platform & category';

    protected string $view = 'filament.pages.browse-catalog-services';

    #[Url(as: 'platform')]
    public ?string $platformSlug = null;

    #[Url(as: 'category')]
    public ?string $categorySlug = null;

    public function mount(): void
    {
        if (blank($this->platformSlug)) {
            $this->platformSlug = CatalogPlatform::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->value('slug');
        }

        $this->ensureCategorySlug();
    }

    public function selectPlatform(string $slug): void
    {
        $this->platformSlug = $slug;
        $this->categorySlug = null;
        $this->ensureCategorySlug();
        $this->resetTable();
    }

    public function selectCategory(?string $slug): void
    {
        $this->categorySlug = $slug;
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

    /** @return Collection<int, CatalogCategory> */
    public function getCategoriesProperty(): Collection
    {
        if (blank($this->platformSlug)) {
            return collect();
        }

        return CatalogCategory::query()
            ->where('is_active', true)
            ->whereHas('platform', fn (Builder $query) => $query->where('slug', $this->platformSlug))
            ->withCount('services')
            ->orderBy('sort_order')
            ->get();
    }

    public function getActivePlatformProperty(): ?CatalogPlatform
    {
        if (blank($this->platformSlug)) {
            return null;
        }

        return $this->platforms->firstWhere('slug', $this->platformSlug);
    }

    public function getFilteredCountProperty(): int
    {
        return $this->getFilteredServicesQuery()->count();
    }

    protected function ensureCategorySlug(): void
    {
        if (filled($this->categorySlug)) {
            $exists = $this->categories->contains('slug', $this->categorySlug);

            if ($exists) {
                return;
            }
        }

        $this->categorySlug = $this->categories->first()?->slug;
    }

    protected function getFilteredServicesQuery(): Builder
    {
        return Service::query()
            ->with('catalogCategory')
            ->when(
                filled($this->platformSlug),
                fn (Builder $query) => $query->where('platform', $this->platformSlug),
            )
            ->when(
                filled($this->categorySlug),
                fn (Builder $query) => $query->whereHas(
                    'catalogCategory',
                    fn (Builder $category) => $category->where('slug', $this->categorySlug),
                ),
            )
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function table(Table $table): Table
    {
        return $table;
    }

    protected function makeTable(): Table
    {
        $table = $this->makeBaseTable()
            ->query(fn (): Builder => $this->getFilteredServicesQuery())
            ->heading(fn (): string => $this->buildTableHeading());

        $table = ServicesTable::configure($table);

        return $table->recordActions([
            EditAction::make()
                ->url(fn (Service $record): string => ServiceResource::getUrl('edit', ['record' => $record])),
        ]);
    }

    protected function buildTableHeading(): string
    {
        $platform = $this->activePlatform?->name ?? 'All platforms';
        $category = $this->categorySlug
            ? ($this->categories->firstWhere('slug', $this->categorySlug)?->name ?? $this->categorySlug)
            : 'All categories';

        return "{$platform} · {$category} · {$this->filteredCount} services";
    }
}
