<?php

namespace App\Filament\Resources\ProviderServices;

use App\Filament\Resources\ProviderServices\Pages\ListProviderServices;
use App\Filament\Resources\ProviderServices\Pages\ViewProviderService;
use App\Filament\Resources\ProviderServices\Schemas\ProviderServiceInfolist;
use App\Filament\Resources\ProviderServices\Tables\ProviderServicesTable;
use App\Models\ProviderService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProviderServiceResource extends Resource
{
    protected static ?string $model = ProviderService::class;

    protected static ?string $navigationLabel = 'Provider services';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|UnitEnum|null $navigationGroup = 'Providers';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProviderServiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProviderServicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProviderServices::route('/'),
            'view' => ViewProviderService::route('/{record}'),
        ];
    }
}
