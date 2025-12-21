<?php

namespace App\Filament\Resources\DailyEntryResource\Pages;

use App\Filament\Resources\DailyEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDailyEntries extends ListRecords
{
    protected static string $resource = DailyEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
