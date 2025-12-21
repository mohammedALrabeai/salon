<?php

namespace App\Filament\Resources\DayClosureResource\Pages;

use App\Filament\Resources\DayClosureResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDayClosure extends CreateRecord
{
    protected static string $resource = DayClosureResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $userId = auth()->id();

        if (empty($data['closed_by']) && $userId) {
            $data['closed_by'] = $userId;
        }

        if (empty($data['closed_at'])) {
            $data['closed_at'] = now();
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
