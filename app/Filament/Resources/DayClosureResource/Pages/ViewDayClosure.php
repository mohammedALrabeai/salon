<?php

namespace App\Filament\Resources\DayClosureResource\Pages;

use App\Filament\Resources\DayClosureResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDayClosure extends ViewRecord
{
    protected static string $resource = DayClosureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
