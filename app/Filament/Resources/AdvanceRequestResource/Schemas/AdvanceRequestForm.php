<?php

namespace App\Filament\Resources\AdvanceRequestResource\Schemas;

use App\Models\Employee;
use App\Models\LedgerEntry;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AdvanceRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('advance_requests.sections.request'))
                    ->schema([
                        Select::make('branch_id')
                            ->label(__('advance_requests.fields.branch_id'))
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('employee_id', null);
                            }),
                        Select::make('employee_id')
                            ->label(__('advance_requests.fields.employee_id'))
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
                        TextInput::make('amount')
                            ->label(__('advance_requests.fields.amount'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('SAR')
                            ->required(),
                        DateTimePicker::make('requested_at')
                            ->label(__('advance_requests.fields.requested_at'))
                            ->seconds(false)
                            ->default(fn () => now())
                            ->required(),
                        Select::make('status')
                            ->label(__('advance_requests.fields.status'))
                            ->options(self::statusOptions())
                            ->required()
                            ->default('pending')
                            ->live(),
                        Textarea::make('reason')
                            ->label(__('advance_requests.fields.reason'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(4),
                Section::make(__('advance_requests.sections.decision'))
                    ->schema([
                        Select::make('processed_by')
                            ->label(__('advance_requests.fields.processed_by'))
                            ->relationship('processedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled(),
                        DateTimePicker::make('processed_at')
                            ->label(__('advance_requests.fields.processed_at'))
                            ->seconds(false)
                            ->disabled(),
                        Textarea::make('decision_notes')
                            ->label(__('advance_requests.fields.decision_notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('rejection_reason')
                            ->label(__('advance_requests.fields.rejection_reason'))
                            ->rows(2)
                            ->visible(fn (Get $get): bool => $get('status') === 'rejected')
                            ->required(fn (Get $get): bool => $get('status') === 'rejected')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
                Section::make(__('advance_requests.sections.payment'))
                    ->schema([
                        DatePicker::make('payment_date')
                            ->label(__('advance_requests.fields.payment_date'))
                            ->visible(fn (Get $get): bool => $get('status') === 'approved')
                            ->required(fn (Get $get): bool => $get('status') === 'approved'),
                        Select::make('payment_method')
                            ->label(__('advance_requests.fields.payment_method'))
                            ->options(self::paymentMethodOptions())
                            ->visible(fn (Get $get): bool => $get('status') === 'approved')
                            ->required(fn (Get $get): bool => $get('status') === 'approved'),
                        Select::make('ledger_entry_id')
                            ->label(__('advance_requests.fields.ledger_entry_id'))
                            ->relationship('ledgerEntry', 'description')
                            ->getOptionLabelFromRecordUsing(
                                fn (LedgerEntry $record): string => trim("{$record->description} ({$record->amount} SAR)")
                            )
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('status') === 'approved'),
                        TextInput::make('attachment_url')
                            ->label(__('advance_requests.fields.attachment_url'))
                            ->url()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return [
            'pending' => __('advance_requests.status.pending'),
            'approved' => __('advance_requests.status.approved'),
            'rejected' => __('advance_requests.status.rejected'),
            'cancelled' => __('advance_requests.status.cancelled'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function paymentMethodOptions(): array
    {
        return [
            'cash' => __('advance_requests.payment_methods.cash'),
            'bank_transfer' => __('advance_requests.payment_methods.bank_transfer'),
            'check' => __('advance_requests.payment_methods.check'),
            'deduction' => __('advance_requests.payment_methods.deduction'),
        ];
    }
}
