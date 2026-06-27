<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payments\Pages;

use App\Actions\Payments\RefundPaymentAction;
use App\Data\RefundPaymentData;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use App\Payments\Contracts\Refundable;
use App\Payments\PaymentManager;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refund')
                ->label(__('tocaan.actions.refund'))
                ->icon(Heroicon::OutlinedReceiptRefund)
                ->color('warning')
                ->visible(fn (Payment $record): bool => $this->isRefundable($record))
                ->schema([
                    TextInput::make('amount')
                        ->label(__('tocaan.actions.amount'))
                        ->numeric()
                        ->minValue(0.01)
                        ->maxValue(fn (Payment $record): float => $record->refundableAmount()),
                    TextInput::make('reason')
                        ->label(__('tocaan.actions.reason'))
                        ->maxLength(255),
                ])
                ->action(function (array $data, Payment $record): void {
                    try {
                        app(RefundPaymentAction::class)->execute($record, new RefundPaymentData(
                            amount: filled($data['amount'] ?? null) ? (float) $data['amount'] : null,
                            reason: $data['reason'] ?? null,
                        ));

                        Notification::make()->title(__('tocaan.actions.refunded'))->success()->send();

                        $this->refreshFormData(['status']);
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title(__('tocaan.actions.failed', ['message' => $e->getMessage()]))
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    private function isRefundable(Payment $payment): bool
    {
        if (! $payment->isSuccessful() || $payment->isFullyRefunded()) {
            return false;
        }

        return app(PaymentManager::class)->driver($payment->gateway) instanceof Refundable;
    }
}
