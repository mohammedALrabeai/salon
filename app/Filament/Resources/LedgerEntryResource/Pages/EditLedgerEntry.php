<?php

namespace App\Filament\Resources\LedgerEntryResource\Pages;

use App\Filament\Resources\LedgerEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditLedgerEntry extends EditRecord
{
    protected static string $resource = LedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $userId = auth()->id();

        if ($userId) {
            $data['updated_by'] = $userId;
        }

        return $data;
    }
}
