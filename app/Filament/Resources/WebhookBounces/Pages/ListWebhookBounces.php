<?php

declare(strict_types=1);

namespace App\Filament\Resources\WebhookBounces\Pages;

use App\Filament\Resources\WebhookBounces\WebhookBounceResource;
use Filament\Resources\Pages\ListRecords;

class ListWebhookBounces extends ListRecords
{
    protected static string $resource = WebhookBounceResource::class;
}
