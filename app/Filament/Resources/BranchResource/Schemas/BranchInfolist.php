<?php

namespace App\Filament\Resources\BranchResource\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BranchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('branches.sections.basic'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('branches.fields.name')),
                        TextEntry::make('code')
                            ->label(__('branches.fields.code')),
                        TextEntry::make('status')
                            ->label(__('branches.fields.status'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                            ->color(fn (?string $state): string => self::statusColor($state)),
                    ])
                    ->columns(3),
                Section::make(__('branches.sections.location'))
                    ->schema([
                        TextEntry::make('address')
                            ->label(__('branches.fields.address'))
                            ->columnSpanFull(),
                        TextEntry::make('city')
                            ->label(__('branches.fields.city')),
                        TextEntry::make('region')
                            ->label(__('branches.fields.region')),
                        TextEntry::make('country')
                            ->label(__('branches.fields.country')),
                        TextEntry::make('postal_code')
                            ->label(__('branches.fields.postal_code')),
                        TextEntry::make('latitude')
                            ->label(__('branches.fields.latitude')),
                        TextEntry::make('longitude')
                            ->label(__('branches.fields.longitude')),
                    ])
                    ->columns(3),
                Section::make(__('branches.sections.contact'))
                    ->schema([
                        TextEntry::make('phone')
                            ->label(__('branches.fields.phone')),
                        TextEntry::make('email')
                            ->label(__('branches.fields.email')),
                    ])
                    ->columns(2),
                Section::make(__('branches.sections.management'))
                    ->schema([
                        TextEntry::make('manager.name')
                            ->label(__('branches.fields.manager_id')),
                        TextEntry::make('opening_time')
                            ->label(__('branches.fields.opening_time')),
                        TextEntry::make('closing_time')
                            ->label(__('branches.fields.closing_time')),
                        TextEntry::make('working_days')
                            ->label(__('branches.fields.working_days'))
                            ->formatStateUsing(fn (?string $state): string => self::workingDayOptions()[$state] ?? (string) $state)
                            ->listWithLineBreaks()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('branches.sections.settings'))
                    ->schema([
                        KeyValueEntry::make('settings')
                            ->label(__('branches.fields.settings'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                Section::make(__('branches.sections.metadata'))
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('branches.fields.created_at'))
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label(__('branches.fields.updated_at'))
                            ->dateTime(),
                        TextEntry::make('deleted_at')
                            ->label(__('branches.fields.deleted_at'))
                            ->dateTime(),
                        TextEntry::make('createdBy.name')
                            ->label(__('branches.fields.created_by')),
                        TextEntry::make('updatedBy.name')
                            ->label(__('branches.fields.updated_by')),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
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
