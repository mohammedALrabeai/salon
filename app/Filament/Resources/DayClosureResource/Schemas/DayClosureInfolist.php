<?php

namespace App\Filament\Resources\DayClosureResource\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DayClosureInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('day_closures.sections.summary'))
                    ->schema([
                        TextEntry::make('date')
                            ->label(__('day_closures.fields.date'))
                            ->date(),
                        TextEntry::make('branch.name')
                            ->label(__('day_closures.fields.branch_id')),
                        TextEntry::make('closedBy.name')
                            ->label(__('day_closures.fields.closed_by')),
                        TextEntry::make('closed_at')
                            ->label(__('day_closures.fields.closed_at'))
                            ->dateTime(),
                    ])
                    ->columns(4),
                Section::make(__('day_closures.sections.totals'))
                    ->schema([
                        TextEntry::make('total_sales')
                            ->label(__('day_closures.fields.total_sales'))
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' SAR'),
                        TextEntry::make('total_cash')
                            ->label(__('day_closures.fields.total_cash'))
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' SAR'),
                        TextEntry::make('total_expense')
                            ->label(__('day_closures.fields.total_expense'))
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' SAR'),
                        TextEntry::make('total_commission')
                            ->label(__('day_closures.fields.total_commission'))
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' SAR'),
                        TextEntry::make('total_bonus')
                            ->label(__('day_closures.fields.total_bonus'))
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' SAR'),
                        TextEntry::make('total_net')
                            ->label(__('day_closures.fields.total_net'))
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' SAR'),
                        TextEntry::make('entries_count')
                            ->label(__('day_closures.fields.entries_count'))
                            ->numeric(),
                        TextEntry::make('employees_count')
                            ->label(__('day_closures.fields.employees_count'))
                            ->numeric(),
                    ])
                    ->columns(4),
                Section::make(__('day_closures.sections.report'))
                    ->schema([
                        TextEntry::make('pdf_url')
                            ->label(__('day_closures.fields.pdf_url'))
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab(),
                        TextEntry::make('pdf_generated_at')
                            ->label(__('day_closures.fields.pdf_generated_at'))
                            ->dateTime(),
                        TextEntry::make('notes')
                            ->label(__('day_closures.fields.notes'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
                Section::make(__('day_closures.sections.metadata'))
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('day_closures.fields.created_at'))
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
