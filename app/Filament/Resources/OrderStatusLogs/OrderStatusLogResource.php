<?php

namespace App\Filament\Resources\OrderStatusLogs;

use App\Filament\Resources\OrderStatusLogs\Pages\ListOrderStatusLogs;
use App\Filament\Resources\OrderStatusLogs\Pages\ViewOrderStatusLog;
use App\Filament\Resources\OrderStatusLogs\Schemas\OrderStatusLogInfolist;
use App\Filament\Resources\OrderStatusLogs\Tables\OrderStatusLogsTable;
use App\Models\OrderStatusLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OrderStatusLogResource extends Resource
{
    protected static ?string $model = OrderStatusLog::class;

    protected static ?string $navigationLabel = 'Order status logs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderStatusLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderStatusLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderStatusLogs::route('/'),
            'view' => ViewOrderStatusLog::route('/{record}'),
        ];
    }
}
