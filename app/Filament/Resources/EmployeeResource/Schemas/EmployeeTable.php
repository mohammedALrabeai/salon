<?php

namespace App\Filament\Resources\EmployeeResource\Schemas;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EmployeeTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('employees.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label(__('employees.fields.branch_id'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('role')
                    ->label(__('employees.fields.role'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::roleLabel($state))
                    ->color(fn (?string $state): string => self::roleColor($state))
                    ->sortable(),
                TextColumn::make('phone')
                    ->label(__('employees.fields.phone'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->label(__('employees.fields.email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label(__('employees.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                    ->color(fn (?string $state): string => self::statusColor($state))
                    ->sortable(),
                TextColumn::make('employment_type')
                    ->label(__('employees.fields.employment_type'))
                    ->formatStateUsing(fn (?string $state): string => self::employmentTypeLabel($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('hire_date')
                    ->label(__('employees.fields.hire_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('commission_rate')
                    ->label(__('employees.fields.commission_rate'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix('%')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('commission_type')
                    ->label(__('employees.fields.commission_type'))
                    ->formatStateUsing(fn (?string $state): string => self::commissionTypeLabel($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('base_salary')
                    ->label(__('employees.fields.base_salary'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' SAR')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('employees.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('employees.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('employees.fields.deleted_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label(__('employees.fields.branch_id'))
                    ->relationship('branch', 'name'),
                SelectFilter::make('role')
                    ->label(__('employees.fields.role'))
                    ->options(self::roleOptions()),
                SelectFilter::make('status')
                    ->label(__('employees.fields.status'))
                    ->options(self::statusOptions()),
                SelectFilter::make('employment_type')
                    ->label(__('employees.fields.employment_type'))
                    ->options(self::employmentTypeOptions()),
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
            ->defaultSort('created_at', 'desc');
    }

    /**
     * @return array<string, string>
     */
    private static function roleOptions(): array
    {
        return [
            'barber' => __('employees.roles.barber'),
            'manager' => __('employees.roles.manager'),
            'receptionist' => __('employees.roles.receptionist'),
            'other' => __('employees.roles.other'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return [
            'active' => __('employees.status.active'),
            'inactive' => __('employees.status.inactive'),
            'on_leave' => __('employees.status.on_leave'),
            'suspended' => __('employees.status.suspended'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function employmentTypeOptions(): array
    {
        return [
            'full_time' => __('employees.employment_types.full_time'),
            'part_time' => __('employees.employment_types.part_time'),
            'contract' => __('employees.employment_types.contract'),
            'freelance' => __('employees.employment_types.freelance'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function commissionTypeOptions(): array
    {
        return [
            'percentage' => __('employees.commission_types.percentage'),
            'fixed' => __('employees.commission_types.fixed'),
            'tiered' => __('employees.commission_types.tiered'),
        ];
    }

    private static function roleLabel(?string $state): string
    {
        return self::roleOptions()[$state] ?? (string) $state;
    }

    private static function statusLabel(?string $state): string
    {
        return self::statusOptions()[$state] ?? (string) $state;
    }

    private static function employmentTypeLabel(?string $state): string
    {
        return self::employmentTypeOptions()[$state] ?? (string) $state;
    }

    private static function commissionTypeLabel(?string $state): string
    {
        return self::commissionTypeOptions()[$state] ?? (string) $state;
    }

    private static function roleColor(?string $state): string
    {
        return match ($state) {
            'manager' => 'primary',
            'receptionist' => 'info',
            'barber' => 'success',
            default => 'gray',
        };
    }

    private static function statusColor(?string $state): string
    {
        return match ($state) {
            'active' => 'success',
            'on_leave' => 'warning',
            'inactive', 'suspended' => 'gray',
            default => 'gray',
        };
    }
}
