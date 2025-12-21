<?php

namespace App\Filament\Resources\ActivityLogResource\Schemas;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('log_name')
                    ->label(__('activity_logs.fields.log_name'))
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('description')
                    ->label(__('activity_logs.fields.description'))
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('event')
                    ->label(__('activity_logs.fields.event'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::eventLabel($state))
                    ->color(fn (?string $state): string => self::eventColor($state)),
                TextColumn::make('subject_type')
                    ->label(__('activity_logs.fields.subject'))
                    ->formatStateUsing(fn (?string $state, Activity $record): string => self::subjectLabel($record))
                    ->toggleable(),
                TextColumn::make('causer_type')
                    ->label(__('activity_logs.fields.causer'))
                    ->formatStateUsing(fn (?string $state, Activity $record): string => self::causerLabel($record))
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('activity_logs.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label(__('activity_logs.fields.event'))
                    ->options(self::eventOptions()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
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
