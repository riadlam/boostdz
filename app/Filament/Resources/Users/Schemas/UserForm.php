<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required(),
                TextInput::make('email')->email()->required(),
                Select::make('role')
                    ->options(['user' => 'User', 'admin' => 'Admin'])
                    ->required()
                    ->disabled(fn (?User $record) => $record && auth()->id() === $record->id),
                Toggle::make('is_active')
                    ->label('Active')
                    ->disabled(fn (?User $record) => $record && auth()->id() === $record->id),
                TextInput::make('timezone'),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn (?string $state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->label('New password'),
            ]);
    }
}
