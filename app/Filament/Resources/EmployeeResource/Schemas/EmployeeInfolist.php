<?php

namespace App\Filament\Resources\EmployeeResource\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('employees.sections.assignment'))
                    ->schema([
                        TextEntry::make('branch.name')
                            ->label(__('employees.fields.branch_id')),
                        TextEntry::make('role')
                            ->label(__('employees.fields.role'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::roleLabel($state))
                            ->color(fn (?string $state): string => self::roleColor($state)),
                        TextEntry::make('status')
                            ->label(__('employees.fields.status'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                            ->color(fn (?string $state): string => self::statusColor($state)),
                    ])
                    ->columns(3),
                Section::make(__('employees.sections.basic'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('employees.fields.name')),
                        TextEntry::make('phone')
                            ->label(__('employees.fields.phone')),
                        TextEntry::make('email')
                            ->label(__('employees.fields.email')),
                        TextEntry::make('national_id')
                            ->label(__('employees.fields.national_id')),
                        TextEntry::make('passport_number')
                            ->label(__('employees.fields.passport_number')),
                    ])
                    ->columns(3),
                Section::make(__('employees.sections.employment'))
                    ->schema([
                        TextEntry::make('hire_date')
                            ->label(__('employees.fields.hire_date'))
                            ->date(),
                        TextEntry::make('termination_date')
                            ->label(__('employees.fields.termination_date'))
                            ->date(),
                        TextEntry::make('employment_type')
                            ->label(__('employees.fields.employment_type'))
                            ->formatStateUsing(fn (?string $state): string => self::employmentTypeLabel($state)),
                    ])
                    ->columns(3),
                Section::make(__('employees.sections.compensation'))
                    ->schema([
                        TextEntry::make('commission_rate')
                            ->label(__('employees.fields.commission_rate'))
                            ->numeric(decimalPlaces: 2)
                            ->suffix('%'),
                        TextEntry::make('commission_type')
                            ->label(__('employees.fields.commission_type'))
                            ->formatStateUsing(fn (?string $state): string => self::commissionTypeLabel($state)),
                        TextEntry::make('base_salary')
                            ->label(__('employees.fields.base_salary'))
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' SAR'),
                    ])
                    ->columns(3),
                Section::make(__('employees.sections.profile'))
                    ->schema([
                        TextEntry::make('avatar_url')
                            ->label(__('employees.fields.avatar_url'))
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab()
                            ->columnSpanFull(),
                        TextEntry::make('bio')
                            ->label(__('employees.fields.bio'))
                            ->columnSpanFull(),
                        TextEntry::make('skills')
                            ->label(__('employees.fields.skills'))
                            ->listWithLineBreaks()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                Section::make(__('employees.sections.metadata'))
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('employees.fields.created_at'))
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label(__('employees.fields.updated_at'))
                            ->dateTime(),
                        TextEntry::make('deleted_at')
                            ->label(__('employees.fields.deleted_at'))
                            ->dateTime(),
                        TextEntry::make('createdBy.name')
                            ->label(__('employees.fields.created_by')),
                        TextEntry::make('updatedBy.name')
                            ->label(__('employees.fields.updated_by')),
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
