<?php

namespace App\Filament\Resources\DailyEntryResource\Widgets;

use App\Models\Branch;
use App\Models\DailyEntry;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class DailySalesChart extends ChartWidget
{
    protected ?string $heading = 'Daily Sales Overview';

    protected function getFilters(): ?array
    {
        return [
            null => 'All Branches',
            ...Branch::pluck('name', 'id')->toArray(),
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        $query = DailyEntry::query()
            ->where('date', '>=', now()->subDays(30))
            ->orderBy('date');

        if ($activeFilter) {
            $query->where('branch_id', $activeFilter);
        }

        $data = $query->get()
            ->groupBy(fn(DailyEntry $entry) => Carbon::parse($entry->date)->format('M d'))
            ->map(fn($entries) => $entries->sum('sales'));

        return [
            'datasets' => [
                [
                    'label' => __('Sales over last 30 days'),
                    'data' => $data->values()->all(),
                    'fill' => 'start',
                    'tension' => 0.5,
                ],
            ],
            'labels' => $data->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
