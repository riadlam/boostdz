<?php

namespace App\Filament\Resources\PaymentSubmissions;

use App\Filament\Resources\PaymentSubmissions\Pages\ListPaymentSubmissions;
use App\Filament\Resources\PaymentSubmissions\Pages\ViewPaymentSubmission;
use App\Filament\Resources\PaymentSubmissions\Schemas\PaymentSubmissionInfolist;
use App\Filament\Resources\PaymentSubmissions\Tables\PaymentSubmissionsTable;
use App\Models\PaymentSubmission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PaymentSubmissionResource extends Resource
{
    protected static ?string $model = PaymentSubmission::class;

    protected static ?string $navigationLabel = 'Payment submissions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Fulfillment';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return PaymentSubmissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentSubmissionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentSubmissions::route('/'),
            'view' => ViewPaymentSubmission::route('/{record}'),
        ];
    }
}
