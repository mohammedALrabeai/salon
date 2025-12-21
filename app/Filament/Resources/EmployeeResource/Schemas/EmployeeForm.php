<?php

namespace App\Filament\Resources\EmployeeResource\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('employees.sections.assignment'))
                    ->schema([
                        Select::make('branch_id')
                            ->label(__('employees.fields.branch_id'))
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('role')
                            ->label(__('employees.fields.role'))
                            ->options(self::roleOptions())
                            ->required()
                            ->default('barber'),
                        Select::make('status')
                            ->label(__('employees.fields.status'))
                            ->options(self::statusOptions())
                            ->required()
                            ->default('active'),
                    ])
                    ->columns(3),
                Section::make(__('employees.sections.basic'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('employees.fields.name'))
                            ->required()
                            ->maxLength(100),
                        TextInput::make('phone')
                            ->label(__('employees.fields.phone'))
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true),
                        TextInput::make('email')
                            ->label(__('employees.fields.email'))
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
                Section::make(__('employees.sections.profile'))
                    ->schema([
                        TextInput::make('avatar_url')
                            ->label(__('employees.fields.avatar_url'))
                            ->url()
                            ->columnSpanFull(),
                        Textarea::make('bio')
                            ->label(__('employees.fields.bio'))
                            ->rows(3)
                            ->columnSpanFull(),
                        TagsInput::make('skills')
                            ->label(__('employees.fields.skills'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
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
}
