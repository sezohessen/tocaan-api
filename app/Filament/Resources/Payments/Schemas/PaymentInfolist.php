<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('tocaan.resources.payment.label'))
                ->columns(2)
                ->schema([
                    TextEntry::make('uuid')->label(__('tocaan.fields.uuid'))->copyable(),
                    TextEntry::make('order.uuid')->label(__('tocaan.resources.order.label')),
                    TextEntry::make('gateway')->label(__('tocaan.fields.gateway'))->badge(),
                    TextEntry::make('method')->label(__('tocaan.fields.method')),
                    TextEntry::make('status')->label(__('tocaan.fields.status'))->badge(),
                    TextEntry::make('amount')->label(__('tocaan.fields.amount'))->money(fn ($record) => $record->currency),
                    TextEntry::make('gateway_reference')->label(__('tocaan.fields.gateway_reference'))->copyable(),
                    TextEntry::make('processed_at')->label(__('tocaan.fields.processed_at'))->dateTime(),
                ]),
        ]);
    }
}
