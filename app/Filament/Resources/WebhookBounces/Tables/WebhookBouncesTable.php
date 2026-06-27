<?php

declare(strict_types=1);

namespace App\Filament\Resources\WebhookBounces\Tables;

use App\Jobs\ProcessGatewayWebhookJob;
use App\Models\WebhookBounce;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WebhookBouncesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('tocaan.fields.id'))
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('tocaan.fields.name'))
                    ->badge(),
                TextColumn::make('url')
                    ->label(__('tocaan.fields.url'))
                    ->limit(40),
                TextColumn::make('exception')
                    ->label(__('tocaan.fields.exception'))
                    ->limit(60)
                    ->tooltip(fn (WebhookBounce $record): ?string => $record->exception)
                    ->color('danger'),
                TextColumn::make('created_at')
                    ->label(__('tocaan.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('retry')
                    ->label(__('tocaan.actions.retry'))
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->requiresConfirmation()
                    ->action(function (WebhookBounce $record): void {
                        $record->update(['exception' => null]);

                        ProcessGatewayWebhookJob::dispatch($record);

                        Notification::make()
                            ->title(__('tocaan.actions.retried'))
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
