<?php

namespace App\Filament\Resources\AdvanceRequestResource\Pages;

use App\Filament\Resources\AdvanceRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditAdvanceRequest extends EditRecord
{
    protected static string $resource = AdvanceRequestResource::class;

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
        return $this->normalizeProcessing($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeProcessing(array $data): array
    {
        $status = $data['status'] ?? $this->record->status;
        $userId = auth()->id();

        if (in_array($status, ['approved', 'rejected', 'cancelled'], true)) {
            $data['processed_at'] = $data['processed_at'] ?? $this->record->processed_at ?? now();
            $data['processed_by'] = $data['processed_by'] ?? $this->record->processed_by ?? $userId;
        } else {
            $data['processed_at'] = null;
            $data['processed_by'] = null;
        }

        return $data;
    }
}
