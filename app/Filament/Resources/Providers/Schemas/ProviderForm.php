<?php

namespace App\Filament\Resources\Providers\Schemas;

use Filament\Schemas\Schema;

class ProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
