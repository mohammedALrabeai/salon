<?php

namespace App\Filament\Resources\UserResource\Schemas;

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

class UserTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('users.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label(__('users.fields.phone'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('users.fields.email'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('role')
                    ->label(__('users.fields.role'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::roleLabel($state))
                    ->color(fn (?string $state): string => self::roleColor($state))
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label(__('users.fields.branch_id'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label(__('users.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                    ->color(fn (?string $state): string => self::statusColor($state))
                    ->sortable(),
                TextColumn::make('last_login_at')
                    ->label(__('users.fields.last_login_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('failed_login_count')
                    ->label(__('users.fields.failed_login_count'))
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('users.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('users.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('users.fields.deleted_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label(__('users.fields.role'))
                    ->options(self::roleOptions()),
                SelectFilter::make('status')
                    ->label(__('users.fields.status'))
                    ->options(self::statusOptions()),
                SelectFilter::make('branch_id')
                    ->label(__('users.fields.branch_id'))
                    ->relationship('branch', 'name'),
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
            'super_admin' => __('users.roles.super_admin'),
            'owner' => __('users.roles.owner'),
            'manager' => __('users.roles.manager'),
            'accountant' => __('users.roles.accountant'),
            'barber' => __('users.roles.barber'),
            'doc_supervisor' => __('users.roles.doc_supervisor'),
            'receptionist' => __('users.roles.receptionist'),
            'auditor' => __('users.roles.auditor'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return [
            'active' => __('users.status.active'),
            'inactive' => __('users.status.inactive'),
            'suspended' => __('users.status.suspended'),
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

    private static function roleColor(?string $state): string
    {
        return match ($state) {
            'super_admin' => 'danger',
            'owner' => 'primary',
            'manager' => 'info',
            'accountant' => 'warning',
            'barber' => 'success',
            'doc_supervisor' => 'gray',
            'receptionist' => 'gray',
            'auditor' => 'gray',
            default => 'gray',
        };
    }

    private static function statusColor(?string $state): string
    {
        return match ($state) {
            'active' => 'success',
            'inactive' => 'gray',
            'suspended' => 'warning',
            default => 'gray',
        };
    }
}
