<?php

namespace App\Filament\Resources\ExchangeRates\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExchangeRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('from_currency')->default('IDR')->required(),
                TextInput::make('to_currency')->default('DZD')->required(),
                TextInput::make('rate')->numeric()->required(),
                TextInput::make('source')->default('manual'),
                DateTimePicker::make('fetched_at')->default(now()),
            ]);
    }
}
