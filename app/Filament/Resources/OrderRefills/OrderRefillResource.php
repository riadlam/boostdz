<?php

namespace App\Filament\Resources\OrderRefills;

use App\Filament\Resources\OrderRefills\Pages\ListOrderRefills;
use App\Filament\Resources\OrderRefills\Pages\ViewOrderRefill;
use App\Filament\Resources\OrderRefills\Schemas\OrderRefillInfolist;
use App\Filament\Resources\OrderRefills\Tables\OrderRefillsTable;
use App\Models\OrderRefill;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OrderRefillResource extends Resource
{
    protected static ?string $model = OrderRefill::class;

    protected static ?string $navigationLabel = 'Order refills';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = 'Fulfillment';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderRefillInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderRefillsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderRefills::route('/'),
            'view' => ViewOrderRefill::route('/{record}'),
        ];
    }
}
