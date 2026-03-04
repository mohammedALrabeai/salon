<?php

namespace App\Filament\Resources\DayClosureResource\Pages;

use App\Filament\Resources\DayClosureResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use App\Models\DailyEntry;

class CreateDayClosure extends CreateRecord
{
    protected static string $resource = DayClosureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('compute_totals')
                ->label(__('Compute Totals'))
                ->icon('heroicon-m-calculator')
                ->action(function () {
                    $data = $this->form->getRawState();
                    $branchId = $data['branch_id'] ?? null;
                    $date = $data['date'] ?? null;

                    if (!$branchId || !$date) {
                        return;
                    }

                    $entries = DailyEntry::query()
                        ->where('branch_id', $branchId)
                        ->whereDate('date', $date)
                        ->get();

                    $sales = $entries->sum('sales');
                    $cash = $entries->sum('cash');
                    $expense = $entries->sum('expense');
                    $commission = $entries->sum('commission');
                    $bonus = $entries->sum('bonus');

                    $this->form->fill([
                        ...$data,
                        'total_sales' => $sales,
                        'total_cash' => $cash,
                        'total_expense' => $expense,
                        'total_commission' => $commission,
                        'total_bonus' => $bonus,
                        'total_net' => $sales - $cash - $expense,
                        'entries_count' => $entries->count(),
                        'employees_count' => $entries->pluck('user_id')->unique()->count(),
                    ]);
                }),
        ];
    }

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

        if (!array_key_exists('total_net', $data) || $data['total_net'] === null) {
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
