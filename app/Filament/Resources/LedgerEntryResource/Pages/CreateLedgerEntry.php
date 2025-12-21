<?php

namespace App\Filament\Resources\LedgerEntryResource\Pages;

use App\Filament\Resources\LedgerEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLedgerEntry extends CreateRecord
{
    protected static string $resource = LedgerEntryResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $userId = auth()->id();

        if ($userId) {
            $data['created_by'] = $userId;
            $data['updated_by'] = $userId;
        }

        return $data;
    }
}
