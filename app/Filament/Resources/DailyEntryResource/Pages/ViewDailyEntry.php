<?php

namespace App\Filament\Resources\DailyEntryResource\Pages;

use App\Filament\Resources\DailyEntryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDailyEntry extends ViewRecord
{
    protected static string $resource = DailyEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
