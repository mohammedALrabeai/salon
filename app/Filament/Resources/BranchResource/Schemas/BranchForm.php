<?php

namespace App\Filament\Resources\BranchResource\Schemas;

use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('branches.sections.basic'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('branches.fields.name'))
                            ->required()
                            ->maxLength(100),
                        TextInput::make('code')
                            ->label(__('branches.fields.code'))
                            ->maxLength(20)
                            ->unique(ignoreRecord: true),
                        Select::make('status')
                            ->label(__('branches.fields.status'))
                            ->options(self::statusOptions())
                            ->required(),
                    ])
                    ->columns(3),
                Section::make(__('branches.sections.location'))
                    ->schema([
                        Textarea::make('address')
                            ->label(__('branches.fields.address'))
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('city')
                            ->label(__('branches.fields.city'))
                            ->maxLength(50),
                        TextInput::make('region')
                            ->label(__('branches.fields.region'))
                            ->maxLength(50),
                        TextInput::make('country')
                            ->label(__('branches.fields.country'))
                            ->maxLength(50)
                            ->default('Saudi Arabia'),
                        TextInput::make('postal_code')
                            ->label(__('branches.fields.postal_code'))
                            ->maxLength(10),
                        TextInput::make('latitude')
                            ->label(__('branches.fields.latitude'))
                            ->numeric()
                            ->minValue(-90)
                            ->maxValue(90),
                        TextInput::make('longitude')
                            ->label(__('branches.fields.longitude'))
                            ->numeric()
                            ->minValue(-180)
                            ->maxValue(180),
                    ])
                    ->columns(3),
                Section::make(__('branches.sections.contact'))
                    ->schema([
                        TextInput::make('phone')
                            ->label(__('branches.fields.phone'))
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('email')
                            ->label(__('branches.fields.email'))
                            ->email()
                            ->maxLength(100),
                    ])
                    ->columns(2),
                Section::make(__('branches.sections.management'))
                    ->schema([
                        Select::make('manager_id')
                            ->label(__('branches.fields.manager_id'))
                            ->relationship(
                                name: 'manager',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->whereIn('role', ['super_admin', 'owner', 'manager'])
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (User $record): string => trim("{$record->name} ({$record->phone})")
                            )
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),
                Section::make(__('branches.sections.hours'))
                    ->schema([
                        TimePicker::make('opening_time')
                            ->label(__('branches.fields.opening_time'))
                            ->seconds(false),
                        TimePicker::make('closing_time')
                            ->label(__('branches.fields.closing_time'))
                            ->seconds(false),
                        CheckboxList::make('working_days')
                            ->label(__('branches.fields.working_days'))
                            ->options(self::workingDayOptions())
                            ->default(array_keys(self::workingDayOptions()))
                            ->columns(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('branches.sections.settings'))
                    ->schema([
                        KeyValue::make('settings')
                            ->label(__('branches.fields.settings'))
                            ->keyLabel(__('branches.fields.setting_key'))
                            ->valueLabel(__('branches.fields.setting_value'))
                            ->addButtonLabel(__('branches.actions.add_setting'))
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
    private static function statusOptions(): array
    {
        return [
            'active' => __('branches.status.active'),
            'inactive' => __('branches.status.inactive'),
            'maintenance' => __('branches.status.maintenance'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function workingDayOptions(): array
    {
        return [
            'sunday' => __('branches.days.sunday'),
            'monday' => __('branches.days.monday'),
            'tuesday' => __('branches.days.tuesday'),
            'wednesday' => __('branches.days.wednesday'),
            'thursday' => __('branches.days.thursday'),
            'friday' => __('branches.days.friday'),
            'saturday' => __('branches.days.saturday'),
        ];
    }
}
