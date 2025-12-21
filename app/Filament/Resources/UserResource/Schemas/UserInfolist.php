<?php

namespace App\Filament\Resources\UserResource\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('users.sections.identity'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('users.fields.name')),
                        TextEntry::make('phone')
                            ->label(__('users.fields.phone')),
                        TextEntry::make('email')
                            ->label(__('users.fields.email')),
                    ])
                    ->columns(3),
                Section::make(__('users.sections.access'))
                    ->schema([
                        TextEntry::make('role')
                            ->label(__('users.fields.role'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::roleLabel($state))
                            ->color(fn (?string $state): string => self::roleColor($state)),
                        TextEntry::make('status')
                            ->label(__('users.fields.status'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                            ->color(fn (?string $state): string => self::statusColor($state)),
                        TextEntry::make('branch.name')
                            ->label(__('users.fields.branch_id')),
                    ])
                    ->columns(3),
                Section::make(__('users.sections.security'))
                    ->schema([
                        TextEntry::make('last_login_at')
                            ->label(__('users.fields.last_login_at'))
                            ->dateTime(),
                        TextEntry::make('last_login_ip')
                            ->label(__('users.fields.last_login_ip')),
                        TextEntry::make('failed_login_count')
                            ->label(__('users.fields.failed_login_count'))
                            ->numeric(),
                        TextEntry::make('locked_until')
                            ->label(__('users.fields.locked_until'))
                            ->dateTime(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
                Section::make(__('users.sections.profile'))
                    ->schema([
                        TextEntry::make('avatar_url')
                            ->label(__('users.fields.avatar_url'))
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab()
                            ->columnSpanFull(),
                        TextEntry::make('bio')
                            ->label(__('users.fields.bio'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                Section::make(__('users.sections.preferences'))
                    ->schema([
                        KeyValueEntry::make('preferences')
                            ->label(__('users.fields.preferences'))
                            ->columnSpanFull(),
                        KeyValueEntry::make('settings')
                            ->label(__('users.fields.settings'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                Section::make(__('users.sections.metadata'))
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('users.fields.created_at'))
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label(__('users.fields.updated_at'))
                            ->dateTime(),
                        TextEntry::make('deleted_at')
                            ->label(__('users.fields.deleted_at'))
                            ->dateTime(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
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
