<?php

namespace App\Filament\Resources\CatalogCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CatalogCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('platform.name')->label('Platform')->disabled(),
                TextInput::make('name')->required(),
                TextInput::make('slug')->disabled(),
                TextInput::make('sort_order')->numeric(),
                Toggle::make('is_active'),
            ]);
    }
}
