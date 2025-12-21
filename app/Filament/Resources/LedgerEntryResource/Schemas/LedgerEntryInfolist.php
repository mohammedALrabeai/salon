<?php

namespace App\Filament\Resources\LedgerEntryResource\Schemas;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\LedgerEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LedgerEntryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('ledger_entries.sections.party'))
                    ->schema([
                        TextEntry::make('party_type')
                            ->label(__('ledger_entries.fields.party_type'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::partyTypeLabel($state))
                            ->color(fn (?string $state): string => self::partyTypeColor($state)),
                        TextEntry::make('party_id')
                            ->label(__('ledger_entries.fields.party'))
                            ->formatStateUsing(fn (?string $state, LedgerEntry $record): string => self::resolvePartyLabel($record)),
                        TextEntry::make('date')
                            ->label(__('ledger_entries.fields.date'))
                            ->date(),
                        TextEntry::make('type')
                            ->label(__('ledger_entries.fields.type'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::typeLabel($state))
                            ->color(fn (?string $state): string => self::typeColor($state)),
                    ])
                    ->columns(4),
                Section::make(__('ledger_entries.sections.details'))
                    ->schema([
                        TextEntry::make('amount')
                            ->label(__('ledger_entries.fields.amount'))
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' SAR'),
                        TextEntry::make('source')
                            ->label(__('ledger_entries.fields.source'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::sourceLabel($state)),
                        TextEntry::make('status')
                            ->label(__('ledger_entries.fields.status'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                            ->color(fn (?string $state): string => self::statusColor($state)),
                        TextEntry::make('payment_method')
                            ->label(__('ledger_entries.fields.payment_method'))
                            ->formatStateUsing(fn (?string $state): string => self::paymentMethodLabel($state)),
                        TextEntry::make('category')
                            ->label(__('ledger_entries.fields.category')),
                    ])
                    ->columns(3),
                Section::make(__('ledger_entries.sections.description'))
                    ->schema([
                        TextEntry::make('description')
                            ->label(__('ledger_entries.fields.description'))
                            ->columnSpanFull(),
                        TextEntry::make('attachment_url')
                            ->label(__('ledger_entries.fields.attachment_url'))
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
                Section::make(__('ledger_entries.sections.reference'))
                    ->schema([
                        TextEntry::make('reference_type')
                            ->label(__('ledger_entries.fields.reference_type')),
                        TextEntry::make('reference_id')
                            ->label(__('ledger_entries.fields.reference_id')),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
                Section::make(__('ledger_entries.sections.metadata'))
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('ledger_entries.fields.created_at'))
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label(__('ledger_entries.fields.updated_at'))
                            ->dateTime(),
                        TextEntry::make('deleted_at')
                            ->label(__('ledger_entries.fields.deleted_at'))
                            ->dateTime(),
                        TextEntry::make('createdBy.name')
                            ->label(__('ledger_entries.fields.created_by')),
                        TextEntry::make('updatedBy.name')
                            ->label(__('ledger_entries.fields.updated_by')),
                    ])
                    ->columns(3)
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
    private static function paymentMethodOptions(): array
    {
        return [
            'cash' => __('ledger_entries.payment_methods.cash'),
            'bank_transfer' => __('ledger_entries.payment_methods.bank_transfer'),
            'check' => __('ledger_entries.payment_methods.check'),
            'other' => __('ledger_entries.payment_methods.other'),
        ];
    }

    private static function partyTypeLabel(?string $state): string
    {
        return self::partyTypeOptions()[$state] ?? (string) $state;
    }

    private static function partyTypeColor(?string $state): string
    {
        return match ($state) {
            'employee' => 'success',
            'branch' => 'info',
            'supplier' => 'warning',
            'customer' => 'primary',
            default => 'gray',
        };
    }

    private static function typeLabel(?string $state): string
    {
        return self::typeOptions()[$state] ?? (string) $state;
    }

    private static function typeColor(?string $state): string
    {
        return match ($state) {
            'debit' => 'danger',
            'credit' => 'success',
            default => 'gray',
        };
    }

    private static function sourceLabel(?string $state): string
    {
        return self::sourceOptions()[$state] ?? (string) $state;
    }

    private static function statusLabel(?string $state): string
    {
        return self::statusOptions()[$state] ?? (string) $state;
    }

    private static function statusColor(?string $state): string
    {
        return match ($state) {
            'pending' => 'warning',
            'confirmed' => 'success',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    private static function paymentMethodLabel(?string $state): string
    {
        return self::paymentMethodOptions()[$state] ?? (string) $state;
    }

    private static function resolvePartyLabel(LedgerEntry $record): string
    {
        if ($record->party_type === 'employee') {
            $employee = Employee::query()->select('name', 'phone')->find($record->party_id);

            if ($employee) {
                return trim("{$employee->name} ({$employee->phone})");
            }
        }

        if ($record->party_type === 'branch') {
            $branch = Branch::query()->select('name')->find($record->party_id);

            if ($branch) {
                return $branch->name;
            }
        }

        return (string) $record->party_id;
    }
}
