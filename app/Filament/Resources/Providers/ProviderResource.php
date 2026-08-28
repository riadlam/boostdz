<?php

namespace App\Filament\Resources\Providers;

use App\Filament\Resources\Providers\Pages\ListProviders;
use App\Filament\Resources\Providers\Pages\ViewProvider;
use App\Filament\Resources\Providers\Schemas\ProviderInfolist;
use App\Filament\Resources\Providers\Tables\ProvidersTable;
use App\Models\Provider;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|UnitEnum|null $navigationGroup = 'Providers';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProviderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProvidersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProviders::route('/'),
            'view' => ViewProvider::route('/{record}'),
        ];
    }
}
