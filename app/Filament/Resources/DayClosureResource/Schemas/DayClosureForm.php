<?php

namespace App\Filament\Resources\DayClosureResource\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;
use Illuminate\Validation\Rules\Unique;

class DayClosureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('day_closures.sections.summary'))
                    ->schema([
                        Select::make('branch_id')
                            ->label(__('day_closures.fields.branch_id'))
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        DatePicker::make('date')
                            ->label(__('day_closures.fields.date'))
                            ->required()
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('branch_id', $get('branch_id'))
                            ),
                        Select::make('closed_by')
                            ->label(__('day_closures.fields.closed_by'))
                            ->relationship('closedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->id())
                            ->disabled(),
                        DateTimePicker::make('closed_at')
                            ->label(__('day_closures.fields.closed_at'))
                            ->seconds(false)
                            ->default(fn () => now()),
                    ])
                    ->columns(4),
                Section::make(__('day_closures.sections.totals'))
                    ->schema([
                        TextInput::make('total_sales')
                            ->label(__('day_closures.fields.total_sales'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('SAR')
                            ->default(0)
                            ->live(debounce: 500),
                        TextInput::make('total_cash')
                            ->label(__('day_closures.fields.total_cash'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('SAR')
                            ->default(0),
                        TextInput::make('total_expense')
                            ->label(__('day_closures.fields.total_expense'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('SAR')
                            ->default(0)
                            ->live(debounce: 500),
                        TextInput::make('total_commission')
                            ->label(__('day_closures.fields.total_commission'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('SAR')
                            ->default(0)
                            ->live(debounce: 500),
                        TextInput::make('total_bonus')
                            ->label(__('day_closures.fields.total_bonus'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('SAR')
                            ->default(0)
                            ->live(debounce: 500),
                        TextInput::make('total_net')
                            ->label(__('day_closures.fields.total_net'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('SAR')
                            ->default(0),
                        Placeholder::make('computed_net')
                            ->label(__('day_closures.fields.computed_net'))
                            ->content(fn (Get $get): string => Number::format(self::calculateNet($get), 2, locale: app()->getLocale()) . ' SAR'),
                        TextInput::make('entries_count')
                            ->label(__('day_closures.fields.entries_count'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('employees_count')
                            ->label(__('day_closures.fields.employees_count'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->columns(4),
                Section::make(__('day_closures.sections.report'))
                    ->schema([
                        TextInput::make('pdf_url')
                            ->label(__('day_closures.fields.pdf_url'))
                            ->url()
                            ->columnSpanFull(),
                        DateTimePicker::make('pdf_generated_at')
                            ->label(__('day_closures.fields.pdf_generated_at'))
                            ->seconds(false),
                        Textarea::make('notes')
                            ->label(__('day_closures.fields.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    private static function calculateNet(Get $get): float
    {
        return (float) $get('total_sales')
            - (float) $get('total_expense')
            - (float) $get('total_commission')
            - (float) $get('total_bonus');
    }
}
