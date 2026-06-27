<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')
                    ->label(__('tocaan.fields.uuid'))
                    ->limit(13)
                    ->copyable()
                    ->searchable(),
                TextColumn::make('order.uuid')
                    ->label(__('tocaan.resources.order.label'))
                    ->limit(13),
                TextColumn::make('gateway')
                    ->label(__('tocaan.fields.gateway'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('tocaan.fields.status'))
                    ->badge(),
                TextColumn::make('amount')
                    ->label(__('tocaan.fields.amount'))
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),
                TextColumn::make('processed_at')
                    ->label(__('tocaan.fields.processed_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('tocaan.fields.status'))
                    ->options(PaymentStatus::options()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
