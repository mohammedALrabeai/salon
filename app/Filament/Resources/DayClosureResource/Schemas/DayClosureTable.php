<?php

namespace App\Filament\Resources\DayClosureResource\Schemas;

use App\Models\DailyEntry;
use App\Models\DayClosure;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class DayClosureTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label(__('day_closures.fields.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label(__('day_closures.fields.branch_id'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('total_sales')
                    ->label(__('day_closures.fields.total_sales'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' SAR')
                    ->sortable(),
                TextColumn::make('total_cash')
                    ->label(__('day_closures.fields.total_cash'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' SAR')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_expense')
                    ->label(__('day_closures.fields.total_expense'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' SAR')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_net')
                    ->label(__('day_closures.fields.total_net'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' SAR')
                    ->sortable(),
                TextColumn::make('total_commission')
                    ->label(__('day_closures.fields.total_commission'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' SAR')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_bonus')
                    ->label(__('day_closures.fields.total_bonus'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' SAR')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('entries_count')
                    ->label(__('day_closures.fields.entries_count'))
                    ->numeric()
                    ->toggleable(),
                TextColumn::make('employees_count')
                    ->label(__('day_closures.fields.employees_count'))
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('closedBy.name')
                    ->label(__('day_closures.fields.closed_by'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('closed_at')
                    ->label(__('day_closures.fields.closed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('pdf_generated_at')
                    ->label(__('day_closures.fields.pdf_generated_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('day_closures.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label(__('day_closures.fields.branch_id'))
                    ->relationship('branch', 'name'),
                SelectFilter::make('closed_by')
                    ->label(__('day_closures.fields.closed_by'))
                    ->relationship('closedBy', 'name'),
                Filter::make('date')
                    ->form([
                        DatePicker::make('from')
                            ->label(__('day_closures.fields.date')),
                        DatePicker::make('until')
                            ->label(__('day_closures.fields.date')),
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
            ])
            ->recordActions([
                Action::make('sync_totals')
                    ->label(__('day_closures.actions.sync_totals'))
                    ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(fn (DayClosure $record) => self::syncTotals($record)),
                Action::make('lock_entries')
                    ->label(__('day_closures.actions.lock_entries'))
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->color('success')
                    ->visible(fn (DayClosure $record): bool => self::hasUnlockedEntries($record))
                    ->requiresConfirmation()
                    ->action(fn (DayClosure $record) => self::setEntriesLock($record, true)),
                Action::make('unlock_entries')
                    ->label(__('day_closures.actions.unlock_entries'))
                    ->icon(Heroicon::OutlinedLockOpen)
                    ->color('gray')
                    ->visible(fn (DayClosure $record): bool => self::hasLockedEntries($record))
                    ->requiresConfirmation()
                    ->action(fn (DayClosure $record) => self::setEntriesLock($record, false)),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    private static function syncTotals(DayClosure $record): void
    {
        $summary = DailyEntry::query()
            ->where('branch_id', $record->branch_id)
            ->whereDate('date', $record->date)
            ->selectRaw('COALESCE(SUM(sales), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(cash), 0) as total_cash')
            ->selectRaw('COALESCE(SUM(expense), 0) as total_expense')
            ->selectRaw('COALESCE(SUM(net), 0) as total_net')
            ->selectRaw('COALESCE(SUM(commission), 0) as total_commission')
            ->selectRaw('COALESCE(SUM(bonus), 0) as total_bonus')
            ->selectRaw('COUNT(*) as entries_count')
            ->selectRaw('COUNT(DISTINCT employee_id) as employees_count')
            ->first();

        $record->update([
            'total_sales' => $summary?->total_sales ?? 0,
            'total_cash' => $summary?->total_cash ?? 0,
            'total_expense' => $summary?->total_expense ?? 0,
            'total_net' => $summary?->total_net ?? 0,
            'total_commission' => $summary?->total_commission ?? 0,
            'total_bonus' => $summary?->total_bonus ?? 0,
            'entries_count' => $summary?->entries_count ?? 0,
            'employees_count' => $summary?->employees_count ?? 0,
        ]);
    }

    private static function setEntriesLock(DayClosure $record, bool $lock): void
    {
        $userId = auth()->id();

        DailyEntry::query()
            ->where('branch_id', $record->branch_id)
            ->whereDate('date', $record->date)
            ->update([
                'is_locked' => $lock,
                'locked_at' => $lock ? now() : null,
                'locked_by' => $lock ? $userId : null,
                'updated_by' => $userId,
            ]);
    }

    private static function hasUnlockedEntries(DayClosure $record): bool
    {
        return DailyEntry::query()
            ->where('branch_id', $record->branch_id)
            ->whereDate('date', $record->date)
            ->where('is_locked', false)
            ->exists();
    }

    private static function hasLockedEntries(DayClosure $record): bool
    {
        return DailyEntry::query()
            ->where('branch_id', $record->branch_id)
            ->whereDate('date', $record->date)
            ->where('is_locked', true)
            ->exists();
    }
}
