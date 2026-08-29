<?php

namespace App\Filament\Resources\Testimonials\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Reviewer')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        TextInput::make('avatar_path')
                            ->label('Avatar URL or path')
                            ->placeholder('/assets/testimonials/1.webp')
                            ->columnSpanFull(),
                        Toggle::make('is_published')
                            ->label('Show on landing page')
                            ->default(true),
                    ]),
                Tabs::make('Translations')
                    ->tabs([
                        Tab::make('English')
                            ->schema(self::translationFields('en')),
                        Tab::make('French')
                            ->schema(self::translationFields('fr')),
                        Tab::make('Arabic')
                            ->schema(self::translationFields('ar')),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, Textarea|TextInput>
     */
    protected static function translationFields(string $locale): array
    {
        return [
            Textarea::make("quote.{$locale}")
                ->label('Quote')
                ->rows(5)
                ->required($locale === 'en'),
            TextInput::make("role.{$locale}")
                ->label('Role / title')
                ->maxLength(160)
                ->required($locale === 'en'),
        ];
    }
}
