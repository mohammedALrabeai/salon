<?php

namespace App\Filament\Resources\DocumentResource\Schemas;

use App\Models\Branch;
use App\Models\Document;
use App\Models\Employee;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('documents.sections.owner'))
                    ->schema([
                        TextEntry::make('owner_type')
                            ->label(__('documents.fields.owner_type'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::ownerTypeLabel($state))
                            ->color(fn (?string $state): string => self::ownerTypeColor($state)),
                        TextEntry::make('owner_id')
                            ->label(__('documents.fields.owner'))
                            ->formatStateUsing(fn (?string $state, Document $record): string => self::resolveOwnerLabel($record)),
                        TextEntry::make('type')
                            ->label(__('documents.fields.type')),
                        TextEntry::make('number')
                            ->label(__('documents.fields.number')),
                        TextEntry::make('title')
                            ->label(__('documents.fields.title'))
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make(__('documents.sections.dates'))
                    ->schema([
                        TextEntry::make('issue_date')
                            ->label(__('documents.fields.issue_date'))
                            ->date(),
                        TextEntry::make('expiry_date')
                            ->label(__('documents.fields.expiry_date'))
                            ->date(),
                        TextEntry::make('status')
                            ->label(__('documents.fields.status'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                            ->color(fn (?string $state): string => self::statusColor($state)),
                        TextEntry::make('days_remaining')
                            ->label(__('documents.fields.days_remaining')),
                        TextEntry::make('notify_before_days')
                            ->label(__('documents.fields.notify_before_days')),
                        TextEntry::make('last_notified_at')
                            ->label(__('documents.fields.last_notified_at'))
                            ->dateTime(),
                    ])
                    ->columns(3),
                Section::make(__('documents.sections.notes'))
                    ->schema([
                        TextEntry::make('notes')
                            ->label(__('documents.fields.notes'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                Section::make(__('documents.sections.metadata'))
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('documents.fields.created_at'))
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label(__('documents.fields.updated_at'))
                            ->dateTime(),
                        TextEntry::make('deleted_at')
                            ->label(__('documents.fields.deleted_at'))
                            ->dateTime(),
                        TextEntry::make('createdBy.name')
                            ->label(__('documents.fields.created_by')),
                        TextEntry::make('updatedBy.name')
                            ->label(__('documents.fields.updated_by')),
                    ])
                    ->columns(3)
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
    private static function statusOptions(): array
    {
        return [
            'safe' => __('documents.status.safe'),
            'near' => __('documents.status.near'),
            'urgent' => __('documents.status.urgent'),
            'expired' => __('documents.status.expired'),
        ];
    }

    private static function ownerTypeLabel(?string $state): string
    {
        return self::ownerTypeOptions()[$state] ?? (string) $state;
    }

    private static function ownerTypeColor(?string $state): string
    {
        return match ($state) {
            'employee' => 'success',
            'branch' => 'info',
            'company' => 'primary',
            default => 'gray',
        };
    }

    private static function statusLabel(?string $state): string
    {
        return self::statusOptions()[$state] ?? (string) $state;
    }

    private static function statusColor(?string $state): string
    {
        return match ($state) {
            'safe' => 'success',
            'near' => 'warning',
            'urgent' => 'danger',
            'expired' => 'danger',
            default => 'gray',
        };
    }

    private static function resolveOwnerLabel(Document $record): string
    {
        if ($record->owner_type === 'employee') {
            $employee = Employee::query()->select('name', 'phone')->find($record->owner_id);

            if ($employee) {
                return trim("{$employee->name} ({$employee->phone})");
            }
        }

        if ($record->owner_type === 'branch') {
            $branch = Branch::query()->select('name')->find($record->owner_id);

            if ($branch) {
                return $branch->name;
            }
        }

        if ($record->owner_type === 'company') {
            return __('documents.owner_types.company');
        }

        return (string) $record->owner_id;
    }
}
