<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('tocaan.fields.name'))
                ->required()
                ->maxLength(255),
            TextInput::make('sku')
                ->label(__('tocaan.fields.sku'))
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            TextInput::make('price')
                ->label(__('tocaan.fields.price'))
                ->numeric()
                ->minValue(0)
                ->required(),
            TextInput::make('currency')
                ->label(__('tocaan.fields.currency'))
                ->default('USD')
                ->maxLength(3),
            TextInput::make('stock')
                ->label(__('tocaan.fields.stock'))
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),
            Toggle::make('is_active')
                ->label(__('tocaan.fields.is_active'))
                ->default(true),
            Textarea::make('description')
                ->label(__('tocaan.fields.description'))
                ->columnSpanFull(),
        ]);
    }
}
