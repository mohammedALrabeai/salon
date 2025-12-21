<?php

namespace App\Filament\Resources\AdvanceRequestResource\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdvanceRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('advance_requests.sections.request'))
                    ->schema([
                        TextEntry::make('requested_at')
                            ->label(__('advance_requests.fields.requested_at'))
                            ->dateTime(),
                        TextEntry::make('branch.name')
                            ->label(__('advance_requests.fields.branch_id')),
                        TextEntry::make('employee.name')
                            ->label(__('advance_requests.fields.employee_id')),
                        TextEntry::make('amount')
                            ->label(__('advance_requests.fields.amount'))
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' SAR'),
                        TextEntry::make('status')
                            ->label(__('advance_requests.fields.status'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                            ->color(fn (?string $state): string => self::statusColor($state)),
                        TextEntry::make('reason')
                            ->label(__('advance_requests.fields.reason'))
                            ->columnSpanFull(),
                    ])
                    ->columns(4),
                Section::make(__('advance_requests.sections.decision'))
                    ->schema([
                        TextEntry::make('processedBy.name')
                            ->label(__('advance_requests.fields.processed_by')),
                        TextEntry::make('processed_at')
                            ->label(__('advance_requests.fields.processed_at'))
                            ->dateTime(),
                        TextEntry::make('decision_notes')
                            ->label(__('advance_requests.fields.decision_notes'))
                            ->columnSpanFull(),
                        TextEntry::make('rejection_reason')
                            ->label(__('advance_requests.fields.rejection_reason'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
                Section::make(__('advance_requests.sections.payment'))
                    ->schema([
                        TextEntry::make('payment_method')
                            ->label(__('advance_requests.fields.payment_method'))
                            ->formatStateUsing(fn (?string $state): string => self::paymentMethodLabel($state)),
                        TextEntry::make('payment_date')
                            ->label(__('advance_requests.fields.payment_date'))
                            ->date(),
                        TextEntry::make('ledgerEntry.description')
                            ->label(__('advance_requests.fields.ledger_entry_id')),
                        TextEntry::make('attachment_url')
                            ->label(__('advance_requests.fields.attachment_url'))
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
                Section::make(__('advance_requests.sections.metadata'))
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('advance_requests.fields.created_at'))
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label(__('advance_requests.fields.updated_at'))
                            ->dateTime(),
                        TextEntry::make('deleted_at')
                            ->label(__('advance_requests.fields.deleted_at'))
                            ->dateTime(),
                    ])
                    ->columns(3)
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

    private static function statusLabel(?string $state): string
    {
        return self::statusOptions()[$state] ?? (string) $state;
    }

    private static function statusColor(?string $state): string
    {
        return match ($state) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    private static function paymentMethodLabel(?string $state): string
    {
        return self::paymentMethodOptions()[$state] ?? (string) $state;
    }
}
