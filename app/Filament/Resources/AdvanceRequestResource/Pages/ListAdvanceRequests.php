<?php

namespace App\Filament\Resources\AdvanceRequestResource\Pages;

use App\Filament\Resources\AdvanceRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdvanceRequests extends ListRecords
{
    protected static string $resource = AdvanceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
