<?php

namespace App\Filament\Resources\BranchResource\Schemas;

use App\Models\Branch;
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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BranchTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('branches.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label(__('branches.fields.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->label(__('branches.fields.city'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('country')
                    ->label(__('branches.fields.country'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('manager.name')
                    ->label(__('branches.fields.manager_id'))
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label(__('branches.fields.phone'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('branches.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                    ->color(fn (?string $state): string => self::statusColor($state))
                    ->sortable(),
                TextColumn::make('opening_time')
                    ->label(__('branches.fields.opening_time'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('closing_time')
                    ->label(__('branches.fields.closing_time'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('branches.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('branches.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('branches.fields.deleted_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('branches.fields.status'))
                    ->options(self::statusOptions()),
                SelectFilter::make('city')
                    ->label(__('branches.fields.city'))
                    ->options(fn (): array => Branch::query()
                        ->whereNotNull('city')
                        ->distinct()
                        ->orderBy('city')
                        ->pluck('city', 'city')
                        ->all()),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('activate')
                    ->label(__('branches.actions.activate'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Branch $record): bool => $record->status !== 'active')
                    ->requiresConfirmation()
                    ->action(fn (Branch $record) => self::updateStatus($record, 'active')),
                Action::make('deactivate')
                    ->label(__('branches.actions.deactivate'))
                    ->icon(Heroicon::OutlinedPauseCircle)
                    ->color('gray')
                    ->visible(fn (Branch $record): bool => $record->status !== 'inactive')
                    ->requiresConfirmation()
                    ->action(fn (Branch $record) => self::updateStatus($record, 'inactive')),
                Action::make('maintenance')
                    ->label(__('branches.actions.set_maintenance'))
                    ->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->color('warning')
                    ->visible(fn (Branch $record): bool => $record->status !== 'maintenance')
                    ->requiresConfirmation()
                    ->action(fn (Branch $record) => self::updateStatus($record, 'maintenance')),
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
    private static function statusOptions(): array
    {
        return [
            'active' => __('branches.status.active'),
            'inactive' => __('branches.status.inactive'),
            'maintenance' => __('branches.status.maintenance'),
        ];
    }

    private static function updateStatus(Branch $record, string $status): void
    {
        $payload = ['status' => $status];

        if ($userId = auth()->id()) {
            $payload['updated_by'] = $userId;
        }

        $record->update($payload);
    }

    private static function statusLabel(?string $state): string
    {
        return self::statusOptions()[$state] ?? (string) $state;
    }

    private static function statusColor(?string $state): string
    {
        return match ($state) {
            'active' => 'success',
            'maintenance' => 'warning',
            'inactive' => 'gray',
            default => 'gray',
        };
    }
}
