<?php

namespace App\Filament\Resources\DailyEntryResource\Pages;

use App\Filament\Resources\DailyEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditDailyEntry extends EditRecord
{
    protected static string $resource = DailyEntryResource::class;

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

        $isLocked = (bool) ($data['is_locked'] ?? false);

        if ($isLocked) {
            $data['locked_at'] = $this->record->locked_at ?? now();
            $data['locked_by'] = $this->record->locked_by ?? $userId;
        } else {
            $data['locked_at'] = null;
            $data['locked_by'] = null;
        }

        return $data;
    }
}
