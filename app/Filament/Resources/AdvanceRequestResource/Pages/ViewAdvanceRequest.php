<?php

namespace App\Filament\Resources\AdvanceRequestResource\Pages;

use App\Filament\Resources\AdvanceRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAdvanceRequest extends ViewRecord
{
    protected static string $resource = AdvanceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
