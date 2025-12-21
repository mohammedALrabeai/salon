<?php

namespace App\Filament\Resources\DayClosureResource\Pages;

use App\Filament\Resources\DayClosureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDayClosures extends ListRecords
{
    protected static string $resource = DayClosureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
