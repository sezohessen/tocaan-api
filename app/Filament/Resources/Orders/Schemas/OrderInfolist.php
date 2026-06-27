<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('tocaan.resources.order.label'))
                ->columns(3)
                ->schema([
                    TextEntry::make('uuid')->label(__('tocaan.fields.uuid'))->copyable(),
                    TextEntry::make('member.name')->label(__('tocaan.fields.customer')),
                    TextEntry::make('status')->label(__('tocaan.fields.status'))->badge(),
                    TextEntry::make('subtotal')->label(__('tocaan.fields.subtotal'))->money(fn ($record) => $record->currency),
                    TextEntry::make('tax')->label(__('tocaan.fields.tax'))->money(fn ($record) => $record->currency),
                    TextEntry::make('discount')->label(__('tocaan.fields.discount'))->money(fn ($record) => $record->currency),
                    TextEntry::make('total')->label(__('tocaan.fields.total'))->money(fn ($record) => $record->currency),
                    TextEntry::make('created_at')->label(__('tocaan.fields.created_at'))->dateTime(),
                ]),

            Section::make(__('tocaan.fields.items_count'))
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->columns(3)
                        ->schema([
                            TextEntry::make('product_name')->label(__('tocaan.fields.name')),
                            TextEntry::make('quantity')->label(__('tocaan.fields.items_count')),
                            TextEntry::make('total')->label(__('tocaan.fields.total')),
                        ]),
                ]),

            Section::make(__('tocaan.resources.payment.plural'))
                ->schema([
                    RepeatableEntry::make('payments')
                        ->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            TextEntry::make('gateway')->label(__('tocaan.fields.gateway')),
                            TextEntry::make('status')->label(__('tocaan.fields.status'))->badge(),
                            TextEntry::make('amount')->label(__('tocaan.fields.amount')),
                            TextEntry::make('gateway_reference')->label(__('tocaan.fields.gateway_reference')),
                        ]),
                ]),
        ]);
    }
}
