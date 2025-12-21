<?php

namespace App\Filament\Resources\AdvanceRequestResource\Pages;

use App\Filament\Resources\AdvanceRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdvanceRequest extends CreateRecord
{
    protected static string $resource = AdvanceRequestResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->normalizeProcessing($data);

        if (empty($data['requested_at'])) {
            $data['requested_at'] = now();
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeProcessing(array $data): array
    {
        $status = $data['status'] ?? 'pending';
        $userId = auth()->id();

        if (in_array($status, ['approved', 'rejected', 'cancelled'], true)) {
            if (empty($data['processed_at'])) {
                $data['processed_at'] = now();
            }

            if (empty($data['processed_by']) && $userId) {
                $data['processed_by'] = $userId;
            }
        } else {
            $data['processed_at'] = null;
            $data['processed_by'] = null;
        }

        return $data;
    }
}
