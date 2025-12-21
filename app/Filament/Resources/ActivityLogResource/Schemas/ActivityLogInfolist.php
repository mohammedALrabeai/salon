<?php

namespace App\Filament\Resources\ActivityLogResource\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('activity_logs.sections.details'))
                    ->schema([
                        TextEntry::make('log_name')
                            ->label(__('activity_logs.fields.log_name'))
                            ->placeholder('-'),
                        TextEntry::make('event')
                            ->label(__('activity_logs.fields.event'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::eventLabel($state))
                            ->color(fn (?string $state): string => self::eventColor($state)),
                        TextEntry::make('description')
                            ->label(__('activity_logs.fields.description'))
                            ->columnSpanFull(),
                        TextEntry::make('subject_type')
                            ->label(__('activity_logs.fields.subject'))
                            ->formatStateUsing(fn (?string $state, Activity $record): string => self::subjectLabel($record))
                            ->columnSpanFull(),
                        TextEntry::make('causer_type')
                            ->label(__('activity_logs.fields.causer'))
                            ->formatStateUsing(fn (?string $state, Activity $record): string => self::causerLabel($record))
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label(__('activity_logs.fields.created_at'))
                            ->dateTime(),
                    ])
                    ->columns(2),
                Section::make(__('activity_logs.sections.properties'))
                    ->schema([
                        TextEntry::make('properties')
                            ->label(__('activity_logs.fields.properties'))
                            ->formatStateUsing(function (mixed $state): string {
                                if (! $state) {
                                    return '-';
                                }

                                if ($state instanceof Collection) {
                                    $state = $state->toArray();
                                }

                                return (string) json_encode(
                                    $state,
                                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                                );
                            })
                            ->copyable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function eventOptions(): array
    {
        return [
            'created' => __('activity_logs.events.created'),
            'updated' => __('activity_logs.events.updated'),
            'deleted' => __('activity_logs.events.deleted'),
            'restored' => __('activity_logs.events.restored'),
        ];
    }

    private static function eventLabel(?string $state): string
    {
        return self::eventOptions()[$state] ?? (string) $state;
    }

    private static function eventColor(?string $state): string
    {
        return match ($state) {
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            'restored' => 'info',
            default => 'gray',
        };
    }

    private static function subjectLabel(Activity $record): string
    {
        if (! $record->subject_type) {
            return '-';
        }

        $type = class_basename($record->subject_type);
        $id = $record->subject_id;

        return $id ? "{$type} - {$id}" : $type;
    }

    private static function causerLabel(Activity $record): string
    {
        if (! $record->causer_type) {
            return '-';
        }

        $causer = $record->causer;

        if ($causer && method_exists($causer, 'getAttribute')) {
            $name = $causer->getAttribute('name') ?? $causer->getAttribute('email');

            if ($name) {
                return (string) $name;
            }
        }

        $type = class_basename($record->causer_type);
        $id = $record->causer_id;

        return $id ? "{$type} - {$id}" : $type;
    }
}
