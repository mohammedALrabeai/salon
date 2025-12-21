<?php

namespace App\Filament\Resources\DailyEntryResource\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DailyEntryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('daily_entries.sections.assignment'))
                    ->schema([
                        TextEntry::make('date')
                            ->label(__('daily_entries.fields.date'))
                            ->date(),
                        TextEntry::make('branch.name')
                            ->label(__('daily_entries.fields.branch_id')),
                        TextEntry::make('employee.name')
                            ->label(__('daily_entries.fields.employee_id')),
                        TextEntry::make('source')
                            ->label(__('daily_entries.fields.source'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::sourceLabel($state)),
                    ])
                    ->columns(4),
                Section::make(__('daily_entries.sections.financial'))
                    ->schema([
                        TextEntry::make('sales')
                            ->label(__('daily_entries.fields.sales'))
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' SAR'),
                        TextEntry::make('cash')
                            ->label(__('daily_entries.fields.cash'))
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' SAR'),
                        TextEntry::make('expense')
                            ->label(__('daily_entries.fields.expense'))
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' SAR'),
                        TextEntry::make('net')
                            ->label(__('daily_entries.fields.net'))
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' SAR'),
                    ])
                    ->columns(4),
                Section::make(__('daily_entries.sections.commission'))
                    ->schema([
                        TextEntry::make('commission_rate')
                            ->label(__('daily_entries.fields.commission_rate'))
                            ->numeric(decimalPlaces: 2)
                            ->suffix('%'),
                        TextEntry::make('commission')
                            ->label(__('daily_entries.fields.commission'))
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' SAR'),
                        TextEntry::make('transactions_count')
                            ->label(__('daily_entries.fields.transactions_count'))
                            ->numeric(),
                    ])
                    ->columns(3),
                Section::make(__('daily_entries.sections.bonus'))
                    ->schema([
                        TextEntry::make('bonus')
                            ->label(__('daily_entries.fields.bonus'))
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' SAR'),
                        TextEntry::make('bonus_reason')
                            ->label(__('daily_entries.fields.bonus_reason'))
                            ->columnSpanFull(),
                        TextEntry::make('note')
                            ->label(__('daily_entries.fields.note'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
                Section::make(__('daily_entries.sections.lock'))
                    ->schema([
                        TextEntry::make('is_locked')
                            ->label(__('daily_entries.fields.is_locked'))
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? __('daily_entries.locked.yes') : __('daily_entries.locked.no'))
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        TextEntry::make('lockedBy.name')
                            ->label(__('daily_entries.fields.locked_by')),
                        TextEntry::make('locked_at')
                            ->label(__('daily_entries.fields.locked_at'))
                            ->dateTime(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
                Section::make(__('daily_entries.sections.metadata'))
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('daily_entries.fields.created_at'))
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label(__('daily_entries.fields.updated_at'))
                            ->dateTime(),
                        TextEntry::make('deleted_at')
                            ->label(__('daily_entries.fields.deleted_at'))
                            ->dateTime(),
                        TextEntry::make('createdBy.name')
                            ->label(__('daily_entries.fields.created_by')),
                        TextEntry::make('updatedBy.name')
                            ->label(__('daily_entries.fields.updated_by')),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function sourceOptions(): array
    {
        return [
            'web' => __('daily_entries.source.web'),
            'mobile' => __('daily_entries.source.mobile'),
            'api' => __('daily_entries.source.api'),
        ];
    }

    private static function sourceLabel(?string $state): string
    {
        return self::sourceOptions()[$state] ?? (string) $state;
    }
}
