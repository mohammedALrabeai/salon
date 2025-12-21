<?php

namespace App\Filament\Resources\DayClosureResource\Pages;

use App\Filament\Resources\DayClosureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDayClosure extends EditRecord
{
    protected static string $resource = DayClosureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $userId = auth()->id();

        if (empty($data['closed_by']) && $userId) {
            $data['closed_by'] = $this->record->closed_by ?? $userId;
        }

        if (empty($data['closed_at'])) {
            $data['closed_at'] = $this->record->closed_at ?? now();
        }

        if (! array_key_exists('total_net', $data) || $data['total_net'] === null) {
            $data['total_net'] = $this->calculateNet($data);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function calculateNet(array $data): float
    {
        return (float) ($data['total_sales'] ?? 0)
            - (float) ($data['total_expense'] ?? 0)
            - (float) ($data['total_commission'] ?? 0)
            - (float) ($data['total_bonus'] ?? 0);
    }
}
