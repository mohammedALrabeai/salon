<?php

namespace App\Filament\Resources\DocumentResource\Schemas;

use App\Models\Branch;
use App\Models\Document;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DocumentTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label(__('documents.fields.type'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('documents.fields.title'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('owner_type')
                    ->label(__('documents.fields.owner_type'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::ownerTypeLabel($state))
                    ->color(fn (?string $state): string => self::ownerTypeColor($state))
                    ->toggleable(),
                TextColumn::make('owner_id')
                    ->label(__('documents.fields.owner'))
                    ->formatStateUsing(fn (?string $state, Document $record): string => self::resolveOwnerLabel($record))
                    ->toggleable(),
                TextColumn::make('number')
                    ->label(__('documents.fields.number'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('issue_date')
                    ->label(__('documents.fields.issue_date'))
                    ->date()
                    ->toggleable(),
                TextColumn::make('expiry_date')
                    ->label(__('documents.fields.expiry_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('documents.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                    ->color(fn (?string $state): string => self::statusColor($state))
                    ->sortable(),
                TextColumn::make('days_remaining')
                    ->label(__('documents.fields.days_remaining'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notify_before_days')
                    ->label(__('documents.fields.notify_before_days'))
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_notified_at')
                    ->label(__('documents.fields.last_notified_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('documents.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('documents.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('documents.fields.deleted_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('owner_type')
                    ->label(__('documents.fields.owner_type'))
                    ->options(self::ownerTypeOptions()),
                SelectFilter::make('status')
                    ->label(__('documents.fields.status'))
                    ->options(self::statusOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['value'] ?? null;

                        if (! filled($status)) {
                            return $query;
                        }

                        if (! self::isSqlite()) {
                            return $query->where('status', $status);
                        }

                        return $query->whereRaw(self::sqliteStatusExpression() . ' = ?', [$status]);
                    }),
                Filter::make('expiry_date')
                    ->form([
                        DatePicker::make('from')
                            ->label(__('documents.fields.expiry_date')),
                        DatePicker::make('until')
                            ->label(__('documents.fields.expiry_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('expiry_date', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('expiry_date', '<=', $date)
                            );
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('mark_notified')
                    ->label(__('documents.actions.mark_notified'))
                    ->icon(Heroicon::OutlinedBellAlert)
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(fn (Document $record) => self::markNotified($record)),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('expiry_date', 'asc');
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

    private static function markNotified(Document $record): void
    {
        $payload = ['last_notified_at' => now()];

        if ($userId = auth()->id()) {
            $payload['updated_by'] = $userId;
        }

        $record->update($payload);
    }

    private static function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    private static function sqliteStatusExpression(): string
    {
        return "CASE WHEN expiry_date IS NULL THEN 'safe' WHEN date(expiry_date) < date('now') THEN 'expired' WHEN date(expiry_date) <= date('now', '+15 days') THEN 'urgent' WHEN date(expiry_date) <= date('now', '+60 days') THEN 'near' ELSE 'safe' END";
    }
}
