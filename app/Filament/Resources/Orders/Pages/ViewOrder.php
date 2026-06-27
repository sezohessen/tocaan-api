<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Orders\CancelOrderAction;
use App\Actions\Orders\ConfirmOrderAction;
use App\Actions\Orders\UpdateOrderAction;
use App\Data\UpdateOrderData;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->confirmAction(),
            $this->cancelAction(),
            $this->editTotalsAction(),
        ];
    }

    private function confirmAction(): Action
    {
        return Action::make('confirm')
            ->label(__('tocaan.actions.confirm'))
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (Order $record): bool => $record->status->canTransitionTo(OrderStatus::Confirmed))
            ->action(fn (Order $record) => $this->run(
                fn () => app(ConfirmOrderAction::class)->execute($record),
                __('tocaan.actions.confirmed'),
            ));
    }

    private function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label(__('tocaan.actions.cancel'))
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (Order $record): bool => $record->status->canTransitionTo(OrderStatus::Cancelled))
            ->action(fn (Order $record) => $this->run(
                fn () => app(CancelOrderAction::class)->execute($record),
                __('tocaan.actions.cancelled'),
            ));
    }

    private function editTotalsAction(): Action
    {
        return Action::make('editTotals')
            ->label(__('tocaan.actions.edit_totals'))
            ->icon(Heroicon::OutlinedPencilSquare)
            ->fillForm(fn (Order $record): array => [
                'tax' => (float) $record->tax,
                'discount' => (float) $record->discount,
            ])
            ->schema([
                TextInput::make('tax')->label(__('tocaan.fields.tax'))->numeric()->minValue(0)->required(),
                TextInput::make('discount')->label(__('tocaan.fields.discount'))->numeric()->minValue(0)->required(),
            ])
            ->action(fn (array $data, Order $record) => $this->run(
                fn () => app(UpdateOrderAction::class)->execute(
                    $record,
                    UpdateOrderData::from(['tax' => (float) $data['tax'], 'discount' => (float) $data['discount']]),
                ),
                __('tocaan.actions.updated'),
            ));
    }

    private function run(callable $callback, string $successMessage): void
    {
        try {
            $callback();

            Notification::make()->title($successMessage)->success()->send();

            $this->refreshFormData(['status', 'tax', 'discount', 'subtotal', 'total']);
        } catch (Throwable $e) {
            Notification::make()
                ->title(__('tocaan.actions.failed', ['message' => $e->getMessage()]))
                ->danger()
                ->send();
        }
    }
}
