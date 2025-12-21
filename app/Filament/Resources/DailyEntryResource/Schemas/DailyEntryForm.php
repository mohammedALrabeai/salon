<?php

namespace App\Filament\Resources\DailyEntryResource\Schemas;

use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;

class DailyEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('daily_entries.sections.assignment'))
                    ->schema([
                        Select::make('branch_id')
                            ->label(__('daily_entries.fields.branch_id'))
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('employee_id', null);
                            }),
                        Select::make('employee_id')
                            ->label(__('daily_entries.fields.employee_id'))
                            ->relationship(
                                name: 'employee',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query, Get $get) => $query->when(
                                    $get('branch_id'),
                                    fn ($query, $branchId) => $query->where('branch_id', $branchId)
                                )
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (Employee $record): string => trim("{$record->name} ({$record->phone})")
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('date')
                            ->label(__('daily_entries.fields.date'))
                            ->required(),
                        Select::make('source')
                            ->label(__('daily_entries.fields.source'))
                            ->options(self::sourceOptions())
                            ->required()
                            ->default('web'),
                    ])
                    ->columns(4),
                Section::make(__('daily_entries.sections.financial'))
                    ->schema([
                        TextInput::make('sales')
                            ->label(__('daily_entries.fields.sales'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('SAR')
                            ->default(0)
                            ->live(debounce: 500),
                        TextInput::make('cash')
                            ->label(__('daily_entries.fields.cash'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('SAR')
                            ->default(0)
                            ->live(debounce: 500),
                        TextInput::make('expense')
                            ->label(__('daily_entries.fields.expense'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('SAR')
                            ->default(0)
                            ->live(debounce: 500),
                        Placeholder::make('net')
                            ->label(__('daily_entries.fields.net'))
                            ->content(fn (Get $get): string => Number::format(
                                ((float) $get('sales')) - ((float) $get('cash')) - ((float) $get('expense')),
                                2,
                                locale: app()->getLocale()
                            ) . ' SAR'),
                    ])
                    ->columns(4),
                Section::make(__('daily_entries.sections.commission'))
                    ->schema([
                        TextInput::make('commission_rate')
                            ->label(__('daily_entries.fields.commission_rate'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%'),
                        TextInput::make('commission')
                            ->label(__('daily_entries.fields.commission'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('SAR')
                            ->default(0),
                        TextInput::make('transactions_count')
                            ->label(__('daily_entries.fields.transactions_count'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->columns(3),
                Section::make(__('daily_entries.sections.bonus'))
                    ->schema([
                        TextInput::make('bonus')
                            ->label(__('daily_entries.fields.bonus'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('SAR')
                            ->default(0),
                        Textarea::make('bonus_reason')
                            ->label(__('daily_entries.fields.bonus_reason'))
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('note')
                            ->label(__('daily_entries.fields.note'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
                Section::make(__('daily_entries.sections.lock'))
                    ->schema([
                        Toggle::make('is_locked')
                            ->label(__('daily_entries.fields.is_locked'))
                            ->default(false),
                        Select::make('locked_by')
                            ->label(__('daily_entries.fields.locked_by'))
                            ->relationship('lockedBy', 'name')
                            ->disabled(),
                        DateTimePicker::make('locked_at')
                            ->label(__('daily_entries.fields.locked_at'))
                            ->seconds(false)
                            ->disabled(),
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
}
