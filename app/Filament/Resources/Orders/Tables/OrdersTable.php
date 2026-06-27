<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
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
                TextColumn::make('member.name')
                    ->label(__('tocaan.fields.customer'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('tocaan.fields.status'))
                    ->badge(),
                TextColumn::make('total')
                    ->label(__('tocaan.fields.total'))
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),
                TextColumn::make('payments_count')
                    ->label(__('tocaan.fields.payments_count'))
                    ->counts('payments')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('tocaan.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('tocaan.fields.status'))
                    ->options(OrderStatus::options()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
