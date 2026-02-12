<?php

namespace App\Filament\Resources\UserResource\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('users.sections.identity'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('users.fields.name'))
                            ->required()
                            ->maxLength(100),
                        TextInput::make('phone')
                            ->label(__('users.fields.phone'))
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true),
                        TextInput::make('email')
                            ->label(__('users.fields.email'))
                            ->email()
                            ->maxLength(100),
                        TextInput::make('national_id')
                            ->label(__('employees.fields.national_id'))
                            ->maxLength(20),
                        TextInput::make('passport_number')
                            ->label(__('employees.fields.passport_number'))
                            ->maxLength(20),
                    ])
                    ->columns(3),
                Section::make(__('users.sections.access'))
                    ->schema([
                        Select::make('role')
                            ->label(__('users.fields.role'))
                            ->options(self::roleOptions())
                            ->required(),
                        Select::make('status')
                            ->label(__('users.fields.status'))
                            ->options(self::statusOptions())
                            ->required()
                            ->default('active'),
                        Select::make('branch_id')
                            ->label(__('users.fields.branch_id'))
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),
                Section::make(__('employees.sections.employment'))
                    ->schema([
                        DatePicker::make('hire_date')
                            ->label(__('employees.fields.hire_date'))
                            ->required(),
                        DatePicker::make('termination_date')
                            ->label(__('employees.fields.termination_date'))
                            ->afterOrEqual('hire_date'),
                        Select::make('employment_type')
                            ->label(__('employees.fields.employment_type'))
                            ->options(self::employmentTypeOptions())
                            ->required()
                            ->default('full_time'),
                    ])
                    ->columns(3),
                Section::make(__('employees.sections.compensation'))
                    ->schema([
                        TextInput::make('commission_rate')
                            ->label(__('employees.fields.commission_rate'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->default(50),
                        Select::make('commission_type')
                            ->label(__('employees.fields.commission_type'))
                            ->options(self::commissionTypeOptions())
                            ->required()
                            ->default('percentage'),
                        TextInput::make('base_salary')
                            ->label(__('employees.fields.base_salary'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix('SAR')
                            ->default(0),
                    ])
                    ->columns(3),
                Section::make(__('users.sections.security'))
                    ->schema([
                        TextInput::make('password_hash')
                            ->label(__('users.fields.password'))
                            ->password()
                            ->revealable()
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->dehydrated(fn(?string $state): bool => filled($state))
                            ->maxLength(255),
                        TextInput::make('last_login_ip')
                            ->label(__('users.fields.last_login_ip'))
                            ->disabled(),
                        DateTimePicker::make('last_login_at')
                            ->label(__('users.fields.last_login_at'))
                            ->seconds(false)
                            ->disabled(),
                        TextInput::make('failed_login_count')
                            ->label(__('users.fields.failed_login_count'))
                            ->numeric()
                            ->disabled(),
                        DateTimePicker::make('locked_until')
                            ->label(__('users.fields.locked_until'))
                            ->seconds(false)
                            ->disabled(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
                Section::make(__('users.sections.profile'))
                    ->schema([
                        TextInput::make('avatar_url')
                            ->label(__('users.fields.avatar_url'))
                            ->url()
                            ->columnSpanFull(),
                        Textarea::make('bio')
                            ->label(__('users.fields.bio'))
                            ->rows(3)
                            ->columnSpanFull(),
                        TagsInput::make('skills')
                            ->label(__('employees.fields.skills'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
                Section::make(__('users.sections.preferences'))
                    ->schema([
                        KeyValue::make('preferences')
                            ->label(__('users.fields.preferences'))
                            ->keyLabel(__('users.fields.preference_key'))
                            ->valueLabel(__('users.fields.preference_value'))
                            ->addButtonLabel(__('users.actions.add_preference'))
                            ->columnSpanFull(),
                        KeyValue::make('settings')
                            ->label(__('users.fields.settings'))
                            ->keyLabel(__('users.fields.setting_key'))
                            ->valueLabel(__('users.fields.setting_value'))
                            ->addButtonLabel(__('users.actions.add_setting'))
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
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
            'other' => __('users.roles.other'),
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
            'on_leave' => __('users.status.on_leave'),
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
}
