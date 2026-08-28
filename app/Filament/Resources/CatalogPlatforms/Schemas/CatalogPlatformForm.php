<?php

namespace App\Filament\Resources\CatalogPlatforms\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CatalogPlatformForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required(),
                TextInput::make('slug')->disabled(),
                TextInput::make('sort_order')->numeric(),
                Toggle::make('is_active'),
            ]);
    }
}
