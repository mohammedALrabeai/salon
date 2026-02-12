<?php

namespace App\Filament\Resources\DocumentResource\Schemas;

use App\Models\Branch;
use App\Models\Document;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('documents.sections.owner'))
                    ->schema([
                        Select::make('owner_type')
                            ->label(__('documents.fields.owner_type'))
                            ->options(self::ownerTypeOptions())
                            ->required()
                            ->default('employee')
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('owner_id', null);
                                $set('owner_selector', null);
                            }),
                        Select::make('owner_selector')
                            ->label(__('documents.fields.owner'))
                            ->options(fn(Get $get): array => self::ownerOptions($get('owner_type')))
                            ->searchable()
                            ->preload()
                            ->visible(fn(Get $get): bool => in_array($get('owner_type'), ['employee', 'branch'], true))
                            ->default(fn(Get $get) => $get('owner_id'))
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $set('owner_id', $state);
                            })
                            ->dehydrated(false),
                        TextInput::make('owner_id')
                            ->label(__('documents.fields.owner_id'))
                            ->helperText(__('documents.helpers.owner_id'))
                            ->maxLength(36)
                            ->rule('uuid')
                            ->required()
                            ->hidden(fn(Get $get): bool => in_array($get('owner_type'), ['employee', 'branch'], true)),
                        TextInput::make('type')
                            ->label(__('documents.fields.type'))
                            ->required()
                            ->maxLength(50),
                        TextInput::make('number')
                            ->label(__('documents.fields.number'))
                            ->maxLength(50),
                        TextInput::make('title')
                            ->label(__('documents.fields.title'))
                            ->maxLength(200),
                    ])
                    ->columns(3),
                Section::make(__('documents.sections.dates'))
                    ->schema([
                        DatePicker::make('issue_date')
                            ->label(__('documents.fields.issue_date')),
                        DatePicker::make('expiry_date')
                            ->label(__('documents.fields.expiry_date'))
                            ->afterOrEqual('issue_date'),
                        TextInput::make('notify_before_days')
                            ->label(__('documents.fields.notify_before_days'))
                            ->numeric()
                            ->minValue(0)
                            ->default(30),
                        DateTimePicker::make('last_notified_at')
                            ->label(__('documents.fields.last_notified_at'))
                            ->seconds(false)
                            ->disabled(),
                        Placeholder::make('status_display')
                            ->label(__('documents.fields.status'))
                            ->content(fn(?Document $record): string => self::statusLabel($record?->status)),
                        Placeholder::make('days_remaining_display')
                            ->label(__('documents.fields.days_remaining'))
                            ->content(fn(?Document $record): string => self::daysRemainingLabel($record?->days_remaining)),
                    ])
                    ->columns(3),
                Section::make(__('documents.sections.notes'))
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('documents.fields.notes'))
                            ->rows(3)
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
    private static function ownerTypeOptions(): array
    {
        return [
            'employee' => __('documents.owner_types.employee'),
            'branch' => __('documents.owner_types.branch'),
            'company' => __('documents.owner_types.company'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function ownerOptions(?string $ownerType): array
    {
        if ($ownerType === 'employee') {
            return User::query()
                ->whereIn('role', User::employeeRoles())
                ->orderBy('name')
                ->get()
                ->mapWithKeys(
                    fn(User $employee): array => [
                        $employee->id => trim("{$employee->name} ({$employee->phone})"),
                    ]
                )
                ->all();
        }

        if ($ownerType === 'branch') {
            return Branch::query()
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all();
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return [
            'safe' => __('documents.status.safe'),
            'near' => __('documents.status.near'),
            'urgent' => __('documents.status.urgent'),
            'expired' => __('documents.status.expired'),
        ];
    }

    private static function statusLabel(?string $state): string
    {
        if (!$state) {
            return '-';
        }

        return self::statusOptions()[$state] ?? $state;
    }

    private static function daysRemainingLabel(?int $daysRemaining): string
    {
        return $daysRemaining === null ? '-' : (string) $daysRemaining;
    }
}
