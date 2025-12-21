<?php

namespace App\Filament\Resources\AdvanceRequestResource\Schemas;

use App\Models\AdvanceRequest;
use App\Models\LedgerEntry;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdvanceRequestTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('requested_at')
                    ->label(__('advance_requests.fields.requested_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label(__('advance_requests.fields.branch_id'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('employee.name')
                    ->label(__('advance_requests.fields.employee_id'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('amount')
                    ->label(__('advance_requests.fields.amount'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' SAR')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('advance_requests.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                    ->color(fn (?string $state): string => self::statusColor($state))
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label(__('advance_requests.fields.payment_method'))
                    ->formatStateUsing(fn (?string $state): string => self::paymentMethodLabel($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_date')
                    ->label(__('advance_requests.fields.payment_date'))
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('processedBy.name')
                    ->label(__('advance_requests.fields.processed_by'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('processed_at')
                    ->label(__('advance_requests.fields.processed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reason')
                    ->label(__('advance_requests.fields.reason'))
                    ->limit(40)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('advance_requests.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('advance_requests.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('advance_requests.fields.deleted_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('advance_requests.fields.status'))
                    ->options(self::statusOptions()),
                SelectFilter::make('branch_id')
                    ->label(__('advance_requests.fields.branch_id'))
                    ->relationship('branch', 'name'),
                SelectFilter::make('employee_id')
                    ->label(__('advance_requests.fields.employee_id'))
                    ->relationship('employee', 'name'),
                SelectFilter::make('payment_method')
                    ->label(__('advance_requests.fields.payment_method'))
                    ->options(self::paymentMethodOptions()),
                SelectFilter::make('processed_by')
                    ->label(__('advance_requests.fields.processed_by'))
                    ->relationship('processedBy', 'name'),
                Filter::make('requested_at')
                    ->form([
                        DatePicker::make('from')
                            ->label(__('advance_requests.fields.requested_at')),
                        DatePicker::make('until')
                            ->label(__('advance_requests.fields.requested_at')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('requested_at', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('requested_at', '<=', $date)
                            );
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('advance_requests.actions.approve'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (AdvanceRequest $record): bool => $record->status === 'pending')
                    ->schema([
                        DatePicker::make('payment_date')
                            ->label(__('advance_requests.fields.payment_date'))
                            ->required()
                            ->default(fn () => now()),
                        Select::make('payment_method')
                            ->label(__('advance_requests.fields.payment_method'))
                            ->options(self::paymentMethodOptions())
                            ->required(),
                        Select::make('ledger_entry_id')
                            ->label(__('advance_requests.fields.ledger_entry_id'))
                            ->options(fn (): array => LedgerEntry::query()
                                ->orderByDesc('date')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(
                                    fn (LedgerEntry $entry): array => [
                                        $entry->id => trim("{$entry->description} ({$entry->amount} SAR)"),
                                    ]
                                )
                                ->all())
                            ->searchable(),
                        Textarea::make('decision_notes')
                            ->label(__('advance_requests.fields.decision_notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('attachment_url')
                            ->label(__('advance_requests.fields.attachment_url'))
                            ->url()
                            ->columnSpanFull(),
                    ])
                    ->action(function (AdvanceRequest $record, array $data): void {
                        $record->update([
                            'status' => 'approved',
                            'processed_at' => now(),
                            'processed_by' => auth()->id(),
                            'decision_notes' => $data['decision_notes'] ?? null,
                            'rejection_reason' => null,
                            'payment_date' => $data['payment_date'] ?? null,
                            'payment_method' => $data['payment_method'] ?? null,
                            'attachment_url' => filled($data['attachment_url'] ?? null) ? $data['attachment_url'] : $record->attachment_url,
                            'ledger_entry_id' => $data['ledger_entry_id'] ?? $record->ledger_entry_id,
                        ]);
                    }),
                Action::make('reject')
                    ->label(__('advance_requests.actions.reject'))
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (AdvanceRequest $record): bool => $record->status === 'pending')
                    ->schema([
                        Textarea::make('rejection_reason')
                            ->label(__('advance_requests.fields.rejection_reason'))
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('decision_notes')
                            ->label(__('advance_requests.fields.decision_notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->action(function (AdvanceRequest $record, array $data): void {
                        $record->update([
                            'status' => 'rejected',
                            'processed_at' => now(),
                            'processed_by' => auth()->id(),
                            'decision_notes' => $data['decision_notes'] ?? null,
                            'rejection_reason' => $data['rejection_reason'] ?? null,
                            'payment_date' => null,
                            'payment_method' => null,
                            'ledger_entry_id' => null,
                        ]);
                    }),
                Action::make('cancel')
                    ->label(__('advance_requests.actions.cancel'))
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('gray')
                    ->visible(fn (AdvanceRequest $record): bool => $record->status === 'pending')
                    ->schema([
                        Textarea::make('decision_notes')
                            ->label(__('advance_requests.fields.decision_notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->action(function (AdvanceRequest $record, array $data): void {
                        $record->update([
                            'status' => 'cancelled',
                            'processed_at' => now(),
                            'processed_by' => auth()->id(),
                            'decision_notes' => $data['decision_notes'] ?? null,
                            'rejection_reason' => null,
                            'payment_date' => null,
                            'payment_method' => null,
                            'ledger_entry_id' => null,
                        ]);
                    }),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('requested_at', 'desc');
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
