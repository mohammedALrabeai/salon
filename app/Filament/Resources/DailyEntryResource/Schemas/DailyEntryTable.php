<?php

namespace App\Filament\Resources\DailyEntryResource\Schemas;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DailyEntryTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label(__('daily_entries.fields.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label(__('daily_entries.fields.branch_id'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('employee.name')
                    ->label(__('daily_entries.fields.employee_id'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('sales')
                    ->label(__('daily_entries.fields.sales'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' SAR')
                    ->sortable(),
                TextColumn::make('cash')
                    ->label(__('daily_entries.fields.cash'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' SAR')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('expense')
                    ->label(__('daily_entries.fields.expense'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' SAR')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('net')
                    ->label(__('daily_entries.fields.net'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' SAR')
                    ->sortable(),
                TextColumn::make('commission')
                    ->label(__('daily_entries.fields.commission'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' SAR')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bonus')
                    ->label(__('daily_entries.fields.bonus'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' SAR')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('source')
                    ->label(__('daily_entries.fields.source'))
                    ->formatStateUsing(fn (?string $state): string => self::sourceLabel($state))
                    ->badge()
                    ->toggleable(),
                IconColumn::make('is_locked')
                    ->label(__('daily_entries.fields.is_locked'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('transactions_count')
                    ->label(__('daily_entries.fields.transactions_count'))
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('daily_entries.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('daily_entries.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('daily_entries.fields.deleted_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label(__('daily_entries.fields.branch_id'))
                    ->relationship('branch', 'name'),
                SelectFilter::make('employee_id')
                    ->label(__('daily_entries.fields.employee_id'))
                    ->relationship('employee', 'name'),
                SelectFilter::make('source')
                    ->label(__('daily_entries.fields.source'))
                    ->options(self::sourceOptions()),
                TernaryFilter::make('is_locked')
                    ->label(__('daily_entries.fields.is_locked'))
                    ->trueLabel(__('daily_entries.locked.yes'))
                    ->falseLabel(__('daily_entries.locked.no')),
                TrashedFilter::make(),
            ])
            ->recordActions([
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
