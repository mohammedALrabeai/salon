<?php

namespace App\Filament\Resources\NotificationResource\Schemas;

use App\Models\Branch;
use App\Models\Notification;
use App\Models\User;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NotificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('notifications.sections.target'))
                    ->schema([
                        TextEntry::make('type')
                            ->label(__('notifications.fields.type'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::typeLabel($state)),
                        TextEntry::make('target_type')
                            ->label(__('notifications.fields.target_type'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::targetTypeLabel($state)),
                        TextEntry::make('target_id')
                            ->label(__('notifications.fields.target'))
                            ->formatStateUsing(fn (?string $state, Notification $record): string => self::resolveTargetLabel($record)),
                        TextEntry::make('priority')
                            ->label(__('notifications.fields.priority'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::priorityLabel($state))
                            ->color(fn (?string $state): string => self::priorityColor($state)),
                    ])
                    ->columns(4),
                Section::make(__('notifications.sections.content'))
                    ->schema([
                        TextEntry::make('title')
                            ->label(__('notifications.fields.title')),
                        TextEntry::make('message')
                            ->label(__('notifications.fields.message'))
                            ->columnSpanFull(),
                        TextEntry::make('action_url')
                            ->label(__('notifications.fields.action_url'))
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab(),
                    ])
                    ->columns(2),
                Section::make(__('notifications.sections.delivery'))
                    ->schema([
                        TextEntry::make('status')
                            ->label(__('notifications.fields.status'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                            ->color(fn (?string $state): string => self::statusColor($state)),
                        TextEntry::make('channels')
                            ->label(__('notifications.fields.channels'))
                            ->formatStateUsing(fn (?string $state): string => self::channelLabel($state))
                            ->listWithLineBreaks(),
                        TextEntry::make('sent_at')
                            ->label(__('notifications.fields.sent_at'))
                            ->dateTime(),
                        TextEntry::make('read_at')
                            ->label(__('notifications.fields.read_at'))
                            ->dateTime(),
                        TextEntry::make('expires_at')
                            ->label(__('notifications.fields.expires_at'))
                            ->dateTime(),
                        TextEntry::make('created_at')
                            ->label(__('notifications.fields.created_at'))
                            ->dateTime(),
                    ])
                    ->columns(3),
                Section::make(__('notifications.sections.payload'))
                    ->schema([
                        KeyValueEntry::make('data')
                            ->label(__('notifications.fields.data'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
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
}
