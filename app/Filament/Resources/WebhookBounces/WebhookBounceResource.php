<?php

declare(strict_types=1);

namespace App\Filament\Resources\WebhookBounces;

use App\Filament\Resources\WebhookBounces\Pages\ListWebhookBounces;
use App\Filament\Resources\WebhookBounces\Tables\WebhookBouncesTable;
use App\Models\WebhookBounce;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WebhookBounceResource extends Resource
{
    protected static ?string $model = WebhookBounce::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    public static function getModelLabel(): string
    {
        return __('tocaan.resources.webhook_bounce.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('tocaan.resources.webhook_bounce.plural');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('tocaan.navigation.webhooks');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) WebhookBounce::query()->count() ?: null;
    }

    public static function table(Table $table): Table
    {
        return WebhookBouncesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebhookBounces::route('/'),
        ];
    }
}
