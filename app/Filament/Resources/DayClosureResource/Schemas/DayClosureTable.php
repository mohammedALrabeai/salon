<?php

namespace App\Filament\Resources\DayClosureResource\Schemas;

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
}
