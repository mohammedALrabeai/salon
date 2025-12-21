<?php

namespace App\Filament\Resources\NotificationResource\Schemas;

use App\Models\Branch;
use App\Models\Notification;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class NotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('notifications.sections.target'))
                    ->schema([
                        Select::make('type')
                            ->label(__('notifications.fields.type'))
                            ->options(self::typeOptions())
                            ->required()
                            ->default('system'),
                        Select::make('target_type')
                            ->label(__('notifications.fields.target_type'))
                            ->options(self::targetTypeOptions())
                            ->required()
                            ->default('user')
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('target_id', null);
                            }),
                        Select::make('target_id')
                            ->label(__('notifications.fields.target'))
                            ->options(fn (Get $get): array => self::targetOptions($get('target_type')))
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => in_array($get('target_type'), ['user', 'branch', 'role'], true))
                            ->required(fn (Get $get): bool => in_array($get('target_type'), ['user', 'branch', 'role'], true))
                            ->helperText(fn (Get $get): ?string => $get('target_type') === 'role'
                                ? __('notifications.helpers.role_target')
                                : null),
                    ])
                    ->columns(3),
                Section::make(__('notifications.sections.content'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('notifications.fields.title'))
                            ->required()
                            ->maxLength(200),
                        Textarea::make('message')
                            ->label(__('notifications.fields.message'))
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('action_url')
                            ->label(__('notifications.fields.action_url'))
                            ->url()
                            ->columnSpanFull(),
                        KeyValue::make('data')
                            ->label(__('notifications.fields.data'))
                            ->keyLabel(__('notifications.fields.data_key'))
                            ->valueLabel(__('notifications.fields.data_value'))
                            ->addButtonLabel(__('notifications.actions.add_data'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('notifications.sections.delivery'))
                    ->schema([
                        Select::make('status')
                            ->label(__('notifications.fields.status'))
                            ->options(self::statusOptions())
                            ->required()
                            ->default('pending'),
                        Select::make('priority')
                            ->label(__('notifications.fields.priority'))
                            ->options(self::priorityOptions())
                            ->required()
                            ->default('normal'),
                        CheckboxList::make('channels')
                            ->label(__('notifications.fields.channels'))
                            ->options(self::channelOptions())
                            ->columns(2)
                            ->default(['in_app']),
                        DateTimePicker::make('sent_at')
                            ->label(__('notifications.fields.sent_at'))
                            ->seconds(false)
                            ->disabled(),
                        DateTimePicker::make('read_at')
                            ->label(__('notifications.fields.read_at'))
                            ->seconds(false)
                            ->disabled(),
                        DateTimePicker::make('expires_at')
                            ->label(__('notifications.fields.expires_at'))
                            ->seconds(false),
                        Placeholder::make('created_at')
                            ->label(__('notifications.fields.created_at'))
                            ->content(fn (?Notification $record): string => $record?->created_at?->toDateTimeString() ?? '-'),
                    ])
                    ->columns(3),
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

    /**
     * @return array<string, string>
     */
    private static function targetOptions(?string $targetType): array
    {
        if ($targetType === 'user') {
            return User::query()
                ->orderBy('name')
                ->get()
                ->mapWithKeys(
                    fn (User $user): array => [
                        $user->id => trim("{$user->name} ({$user->phone})"),
                    ]
                )
                ->all();
        }

        if ($targetType === 'branch') {
            return Branch::query()
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all();
        }

        if ($targetType === 'role') {
            return self::roleOptions();
        }

        return [];
    }
}
