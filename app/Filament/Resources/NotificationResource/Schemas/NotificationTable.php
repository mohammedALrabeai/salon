<?php

namespace App\Filament\Resources\NotificationResource\Schemas;

use App\Models\Branch;
use App\Models\Notification;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NotificationTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('notifications.fields.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('notifications.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::typeLabel($state))
                    ->toggleable(),
                TextColumn::make('target_type')
                    ->label(__('notifications.fields.target_type'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::targetTypeLabel($state))
                    ->toggleable(),
                TextColumn::make('target_id')
                    ->label(__('notifications.fields.target'))
                    ->formatStateUsing(fn (?string $state, Notification $record): string => self::resolveTargetLabel($record))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('notifications.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                    ->color(fn (?string $state): string => self::statusColor($state))
                    ->sortable(),
                TextColumn::make('priority')
                    ->label(__('notifications.fields.priority'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::priorityLabel($state))
                    ->color(fn (?string $state): string => self::priorityColor($state))
                    ->toggleable(),
                TextColumn::make('channels')
                    ->label(__('notifications.fields.channels'))
                    ->formatStateUsing(fn (?string $state): string => self::channelLabel($state))
                    ->listWithLineBreaks()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sent_at')
                    ->label(__('notifications.fields.sent_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('read_at')
                    ->label(__('notifications.fields.read_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expires_at')
                    ->label(__('notifications.fields.expires_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('notifications.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('notifications.fields.type'))
                    ->options(self::typeOptions()),
                SelectFilter::make('target_type')
                    ->label(__('notifications.fields.target_type'))
                    ->options(self::targetTypeOptions()),
                SelectFilter::make('status')
                    ->label(__('notifications.fields.status'))
                    ->options(self::statusOptions()),
                SelectFilter::make('priority')
                    ->label(__('notifications.fields.priority'))
                    ->options(self::priorityOptions()),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('notifications.actions.open_action'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Notification $record): ?string => $record->action_url, true)
                    ->visible(fn (Notification $record): bool => filled($record->action_url)),
                Action::make('mark_sent')
                    ->label(__('notifications.actions.mark_sent'))
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('info')
                    ->visible(fn (Notification $record): bool => $record->status !== 'sent')
                    ->requiresConfirmation()
                    ->action(fn (Notification $record) => self::markSent($record)),
                Action::make('mark_read')
                    ->label(__('notifications.actions.mark_read'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Notification $record): bool => $record->status !== 'read')
                    ->requiresConfirmation()
                    ->action(fn (Notification $record) => self::markRead($record)),
                Action::make('mark_failed')
                    ->label(__('notifications.actions.mark_failed'))
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (Notification $record): bool => $record->status !== 'failed')
                    ->requiresConfirmation()
                    ->action(fn (Notification $record) => self::markFailed($record)),
                Action::make('reset_pending')
                    ->label(__('notifications.actions.reset_pending'))
                    ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                    ->color('gray')
                    ->visible(fn (Notification $record): bool => $record->status !== 'pending')
                    ->requiresConfirmation()
                    ->action(fn (Notification $record) => self::resetPending($record)),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * @return array<string, string>
     */
    private static function typeOptions(): array
    {
        return [
            'document_expiry' => __('notifications.types.document_expiry'),
            'advance_request' => __('notifications.types.advance_request'),
            'day_closure' => __('notifications.types.day_closure'),
            'system' => __('notifications.types.system'),
            'other' => __('notifications.types.other'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function targetTypeOptions(): array
    {
        return [
            'user' => __('notifications.target_types.user'),
            'role' => __('notifications.target_types.role'),
            'branch' => __('notifications.target_types.branch'),
            'all' => __('notifications.target_types.all'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return [
            'pending' => __('notifications.status.pending'),
            'sent' => __('notifications.status.sent'),
            'read' => __('notifications.status.read'),
            'failed' => __('notifications.status.failed'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function priorityOptions(): array
    {
        return [
            'low' => __('notifications.priority.low'),
            'normal' => __('notifications.priority.normal'),
            'high' => __('notifications.priority.high'),
            'urgent' => __('notifications.priority.urgent'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function channelOptions(): array
    {
        return [
            'in_app' => __('notifications.channels.in_app'),
            'email' => __('notifications.channels.email'),
            'sms' => __('notifications.channels.sms'),
            'push' => __('notifications.channels.push'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function roleOptions(): array
    {
        return [
            'super_admin' => __('notifications.roles.super_admin'),
            'owner' => __('notifications.roles.owner'),
            'manager' => __('notifications.roles.manager'),
            'accountant' => __('notifications.roles.accountant'),
            'barber' => __('notifications.roles.barber'),
            'doc_supervisor' => __('notifications.roles.doc_supervisor'),
            'receptionist' => __('notifications.roles.receptionist'),
            'auditor' => __('notifications.roles.auditor'),
            'other' => __('notifications.roles.other'),
        ];
    }

    private static function typeLabel(?string $state): string
    {
        return self::typeOptions()[$state] ?? (string) $state;
    }

    private static function targetTypeLabel(?string $state): string
    {
        return self::targetTypeOptions()[$state] ?? (string) $state;
    }

    private static function statusLabel(?string $state): string
    {
        return self::statusOptions()[$state] ?? (string) $state;
    }

    private static function statusColor(?string $state): string
    {
        return match ($state) {
            'pending' => 'warning',
            'sent' => 'info',
            'read' => 'success',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    private static function priorityLabel(?string $state): string
    {
        return self::priorityOptions()[$state] ?? (string) $state;
    }

    private static function priorityColor(?string $state): string
    {
        return match ($state) {
            'urgent' => 'danger',
            'high' => 'warning',
            'normal' => 'info',
            'low' => 'gray',
            default => 'gray',
        };
    }

    private static function channelLabel(?string $state): string
    {
        return self::channelOptions()[$state] ?? (string) $state;
    }

    private static function resolveTargetLabel(Notification $record): string
    {
        if ($record->target_type === 'user') {
            $user = User::query()->select('name', 'phone')->find($record->target_id);

            if ($user) {
                return trim("{$user->name} ({$user->phone})");
            }
        }

        if ($record->target_type === 'branch') {
            $branch = Branch::query()->select('name')->find($record->target_id);

            if ($branch) {
                return $branch->name;
            }
        }

        if ($record->target_type === 'role') {
            return self::roleOptions()[(string) $record->target_id] ?? (string) $record->target_id;
        }

        if ($record->target_type === 'all') {
            return __('notifications.target_all');
        }

        return (string) $record->target_id;
    }

    private static function markSent(Notification $record): void
    {
        $record->update([
            'status' => 'sent',
            'sent_at' => now(),
            'read_at' => null,
        ]);
    }

    private static function markRead(Notification $record): void
    {
        $record->update([
            'status' => 'read',
            'sent_at' => $record->sent_at ?? now(),
            'read_at' => now(),
        ]);
    }

    private static function markFailed(Notification $record): void
    {
        $record->update([
            'status' => 'failed',
        ]);
    }

    private static function resetPending(Notification $record): void
    {
        $record->update([
            'status' => 'pending',
            'sent_at' => null,
            'read_at' => null,
        ]);
    }
}
