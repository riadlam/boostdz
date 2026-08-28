<?php

namespace App\Filament\Resources\CatalogSyncEvents;

use App\Filament\Resources\CatalogSyncEvents\Pages\ListCatalogSyncEvents;
use App\Filament\Resources\CatalogSyncEvents\Pages\ViewCatalogSyncEvent;
use App\Filament\Resources\CatalogSyncEvents\Schemas\CatalogSyncEventInfolist;
use App\Filament\Resources\CatalogSyncEvents\Tables\CatalogSyncEventsTable;
use App\Models\CatalogSyncEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CatalogSyncEventResource extends Resource
{
    protected static ?string $model = CatalogSyncEvent::class;

    protected static ?string $navigationLabel = 'Sync events';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return CatalogSyncEventInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CatalogSyncEventsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCatalogSyncEvents::route('/'),
            'view' => ViewCatalogSyncEvent::route('/{record}'),
        ];
    }
}
