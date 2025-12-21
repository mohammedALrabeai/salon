<?php

namespace App\Filament\Resources\LedgerEntryResource\Schemas;

use App\Models\Branch;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class LedgerEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('ledger_entries.sections.party'))
                    ->schema([
                        Select::make('party_type')
                            ->label(__('ledger_entries.fields.party_type'))
                            ->options(self::partyTypeOptions())
                            ->required()
                            ->default('employee')
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('party_id', null);
                                $set('party_lookup', null);
                            }),
                        Select::make('party_lookup')
                            ->label(__('ledger_entries.fields.party'))
                            ->options(fn (Get $get) => self::partyOptions($get('party_type')))
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => in_array($get('party_type'), ['employee', 'branch'], true))
                            ->default(fn (Get $get) => $get('party_id'))
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if ($state) {
                                    $set('party_id', $state);
                                }
                            })
                            ->dehydrated(false),
                        TextInput::make('party_id')
                            ->label(__('ledger_entries.fields.party_id'))
                            ->helperText(__('ledger_entries.helpers.party_id'))
                            ->maxLength(36)
                            ->rule('uuid')
                            ->required(),
                    ])
                    ->columns(3),
                Section::make(__('ledger_entries.sections.details'))
                    ->schema([
                        DatePicker::make('date')
                            ->label(__('ledger_entries.fields.date'))
                            ->required()
                            ->default(fn () => now()),
                        Select::make('type')
                            ->label(__('ledger_entries.fields.type'))
                            ->options(self::typeOptions())
                            ->required()
                            ->default('debit'),
                        Select::make('source')
                            ->label(__('ledger_entries.fields.source'))
                            ->options(self::sourceOptions())
                            ->required()
                            ->default('manual'),
                        Select::make('status')
                            ->label(__('ledger_entries.fields.status'))
                            ->options(self::statusOptions())
                            ->required()
                            ->default('confirmed'),
                        TextInput::make('amount')
                            ->label(__('ledger_entries.fields.amount'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('SAR')
                            ->required(),
                        TextInput::make('category')
                            ->label(__('ledger_entries.fields.category'))
                            ->maxLength(50),
                        Select::make('payment_method')
                            ->label(__('ledger_entries.fields.payment_method'))
                            ->options(self::paymentMethodOptions()),
                    ])
                    ->columns(4),
                Section::make(__('ledger_entries.sections.description'))
                    ->schema([
                        Textarea::make('description')
                            ->label(__('ledger_entries.fields.description'))
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('attachment_url')
                            ->label(__('ledger_entries.fields.attachment_url'))
                            ->url()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('ledger_entries.sections.reference'))
                    ->schema([
                        TextInput::make('reference_type')
                            ->label(__('ledger_entries.fields.reference_type'))
                            ->maxLength(30),
                        TextInput::make('reference_id')
                            ->label(__('ledger_entries.fields.reference_id'))
                            ->maxLength(36),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function partyTypeOptions(): array
    {
        return [
            'employee' => __('ledger_entries.party_types.employee'),
            'branch' => __('ledger_entries.party_types.branch'),
            'supplier' => __('ledger_entries.party_types.supplier'),
            'customer' => __('ledger_entries.party_types.customer'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function typeOptions(): array
    {
        return [
            'debit' => __('ledger_entries.types.debit'),
            'credit' => __('ledger_entries.types.credit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function sourceOptions(): array
    {
        return [
            'manual' => __('ledger_entries.sources.manual'),
            'advance_request' => __('ledger_entries.sources.advance_request'),
            'salary' => __('ledger_entries.sources.salary'),
            'closure' => __('ledger_entries.sources.closure'),
            'other' => __('ledger_entries.sources.other'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function paymentMethodOptions(): array
    {
        return [
            'cash' => __('ledger_entries.payment_methods.cash'),
            'bank_transfer' => __('ledger_entries.payment_methods.bank_transfer'),
            'check' => __('ledger_entries.payment_methods.check'),
            'other' => __('ledger_entries.payment_methods.other'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return [
            'pending' => __('ledger_entries.status.pending'),
            'confirmed' => __('ledger_entries.status.confirmed'),
            'cancelled' => __('ledger_entries.status.cancelled'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function partyOptions(?string $partyType): array
    {
        if ($partyType === 'employee') {
            return Employee::query()
                ->orderBy('name')
                ->get()
                ->mapWithKeys(
                    fn (Employee $employee): array => [
                        $employee->id => trim("{$employee->name} ({$employee->phone})"),
                    ]
                )
                ->all();
        }

        if ($partyType === 'branch') {
            return Branch::query()
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all();
        }

        return [];
    }
}
