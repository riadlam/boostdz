<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pricing & visibility')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->disabled(),
                        TextInput::make('platform')->disabled(),
                        TextInput::make('sell_rate_dzd')->label('Sell rate (DZD)')->numeric()->required(),
                        TextInput::make('sort_order')->numeric(),
                        Toggle::make('is_active'),
                        Toggle::make('is_hot')->label('Top seller'),
                        Toggle::make('is_cheap')->label('Best price'),
                    ]),
                Section::make('Catalog metadata')
                    ->columns(2)
                    ->schema([
                        TextInput::make('reaction_type')->disabled(),
                        TextInput::make('audience_gender')->disabled(),
                        TextInput::make('min')->disabled(),
                        TextInput::make('max')->disabled(),
                    ]),
            ]);
    }
}
