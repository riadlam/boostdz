<?php

namespace App\Filament\Resources\CatalogSyncEvents\Schemas;

use Filament\Schemas\Schema;

class CatalogSyncEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
