<?php

namespace App\Filament\Resources\LedgerEntryResource\Schemas;

use App\Models\Branch;
use App\Models\Employee;
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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LedgerEntryTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label(__('ledger_entries.fields.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('party_type')
                    ->label(__('ledger_entries.fields.party_type'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::partyTypeLabel($state))
                    ->color(fn (?string $state): string => self::partyTypeColor($state))
                    ->toggleable(),
                TextColumn::make('party_id')
                    ->label(__('ledger_entries.fields.party'))
                    ->formatStateUsing(fn (?string $state, LedgerEntry $record): string => self::resolvePartyLabel($record))
                    ->toggleable(),
                TextColumn::make('type')
                    ->label(__('ledger_entries.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::typeLabel($state))
                    ->color(fn (?string $state): string => self::typeColor($state))
                    ->sortable(),
                TextColumn::make('amount')
                    ->label(__('ledger_entries.fields.amount'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' SAR')
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('ledger_entries.fields.description'))
                    ->limit(40)
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('source')
                    ->label(__('ledger_entries.fields.source'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::sourceLabel($state))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('ledger_entries.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                    ->color(fn (?string $state): string => self::statusColor($state))
                    ->toggleable(),
                TextColumn::make('payment_method')
                    ->label(__('ledger_entries.fields.payment_method'))
                    ->formatStateUsing(fn (?string $state): string => self::paymentMethodLabel($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('category')
                    ->label(__('ledger_entries.fields.category'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reference_type')
                    ->label(__('ledger_entries.fields.reference_type'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reference_id')
                    ->label(__('ledger_entries.fields.reference_id'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('ledger_entries.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('ledger_entries.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('ledger_entries.fields.deleted_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('party_type')
                    ->label(__('ledger_entries.fields.party_type'))
                    ->options(self::partyTypeOptions()),
                SelectFilter::make('type')
                    ->label(__('ledger_entries.fields.type'))
                    ->options(self::typeOptions()),
                SelectFilter::make('status')
                    ->label(__('ledger_entries.fields.status'))
                    ->options(self::statusOptions()),
                SelectFilter::make('source')
                    ->label(__('ledger_entries.fields.source'))
                    ->options(self::sourceOptions()),
                SelectFilter::make('payment_method')
                    ->label(__('ledger_entries.fields.payment_method'))
                    ->options(self::paymentMethodOptions()),
                Filter::make('date')
                    ->form([
                        DatePicker::make('from')
                            ->label(__('ledger_entries.fields.date')),
                        DatePicker::make('until')
                            ->label(__('ledger_entries.fields.date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date)
                            );
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('mark_confirmed')
                    ->label(__('ledger_entries.actions.mark_confirmed'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (LedgerEntry $record): bool => $record->status !== 'confirmed')
                    ->requiresConfirmation()
                    ->action(fn (LedgerEntry $record) => self::updateStatus($record, 'confirmed')),
                Action::make('mark_pending')
                    ->label(__('ledger_entries.actions.mark_pending'))
                    ->icon(Heroicon::OutlinedClock)
                    ->color('warning')
                    ->visible(fn (LedgerEntry $record): bool => $record->status !== 'pending')
                    ->requiresConfirmation()
                    ->action(fn (LedgerEntry $record) => self::updateStatus($record, 'pending')),
                Action::make('mark_cancelled')
                    ->label(__('ledger_entries.actions.mark_cancelled'))
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('gray')
                    ->visible(fn (LedgerEntry $record): bool => $record->status !== 'cancelled')
                    ->requiresConfirmation()
                    ->action(fn (LedgerEntry $record) => self::updateStatus($record, 'cancelled')),
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
            ->defaultSort('date', 'desc');
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

    private static function updateStatus(LedgerEntry $record, string $status): void
    {
        $payload = ['status' => $status];

        if ($userId = auth()->id()) {
            $payload['updated_by'] = $userId;
        }

        $record->update($payload);
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
