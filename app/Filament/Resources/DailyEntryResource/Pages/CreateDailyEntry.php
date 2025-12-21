<?php

namespace App\Filament\Resources\DailyEntryResource\Pages;

use App\Filament\Resources\DailyEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyEntry extends CreateRecord
{
    protected static string $resource = DailyEntryResource::class;

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

        if (! empty($data['is_locked'])) {
            $data['locked_at'] = now();
            $data['locked_by'] = $userId;
        } else {
            $data['locked_at'] = null;
            $data['locked_by'] = null;
        }

        return $data;
    }
}
