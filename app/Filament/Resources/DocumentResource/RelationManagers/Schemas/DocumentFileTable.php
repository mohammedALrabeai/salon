<?php

namespace App\Filament\Resources\DocumentResource\RelationManagers\Schemas;

use App\Models\DocumentFile;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class DocumentFileTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('document_files.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('version')
                    ->label(__('document_files.fields.version'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_current')
                    ->label(__('document_files.fields.is_current'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('size')
                    ->label(__('document_files.fields.size'))
                    ->formatStateUsing(fn (?int $state): string => $state ? Number::fileSize($state) : '-')
                    ->toggleable(),
                TextColumn::make('mime_type')
                    ->label(__('document_files.fields.mime_type'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('storage_provider')
                    ->label(__('document_files.fields.storage_provider'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::storageLabel($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('uploaded_at')
                    ->label(__('document_files.fields.uploaded_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('uploadedBy.name')
                    ->label(__('document_files.fields.uploaded_by'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('file_url')
                    ->label(__('document_files.fields.file_url'))
                    ->url(fn (?string $state): ?string => $state)
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('storage_provider')
                    ->label(__('document_files.fields.storage_provider'))
                    ->options(self::storageOptions()),
                TernaryFilter::make('is_current')
                    ->label(__('document_files.fields.is_current')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => self::mutateCreateData($data))
                    ->after(fn (DocumentFile $record) => self::syncCurrent($record)),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('document_files.actions.open'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (DocumentFile $record): string => $record->file_url, true),
                Action::make('set_current')
                    ->label(__('document_files.actions.set_current'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (DocumentFile $record): bool => ! $record->is_current)
                    ->requiresConfirmation()
                    ->action(fn (DocumentFile $record) => self::setCurrent($record)),
                EditAction::make()
                    ->after(fn (DocumentFile $record) => self::syncCurrent($record)),
                DeleteAction::make(),
            ])
            ->defaultSort('uploaded_at', 'desc');
    }

    /**
     * @return array<string, string>
     */
    private static function storageOptions(): array
    {
        return [
            'local' => __('document_files.storage_providers.local'),
            's3' => __('document_files.storage_providers.s3'),
            'cloudinary' => __('document_files.storage_providers.cloudinary'),
            'supabase' => __('document_files.storage_providers.supabase'),
        ];
    }

    private static function storageLabel(?string $state): string
    {
        return self::storageOptions()[$state] ?? (string) $state;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function mutateCreateData(array $data): array
    {
        if (empty($data['uploaded_at'])) {
            $data['uploaded_at'] = now();
        }

        if ($userId = auth()->id()) {
            $data['uploaded_by'] = $userId;
        }

        return $data;
    }

    private static function syncCurrent(DocumentFile $record): void
    {
        if (! $record->is_current) {
            return;
        }

        DocumentFile::query()
            ->where('document_id', $record->document_id)
            ->whereKeyNot($record->id)
            ->update(['is_current' => false]);
    }

    private static function setCurrent(DocumentFile $record): void
    {
        DocumentFile::query()
            ->where('document_id', $record->document_id)
            ->update(['is_current' => false]);

        $record->update(['is_current' => true]);
    }
}
