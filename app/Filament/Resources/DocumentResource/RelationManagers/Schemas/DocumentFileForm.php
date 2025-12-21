<?php

namespace App\Filament\Resources\DocumentResource\RelationManagers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentFileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('document_files.sections.file'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('document_files.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('file_url')
                            ->label(__('document_files.fields.file_url'))
                            ->url()
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('size')
                            ->label(__('document_files.fields.size'))
                            ->numeric()
                            ->minValue(0)
                            ->suffix(__('document_files.helpers.bytes'))
                            ->required(),
                        TextInput::make('mime_type')
                            ->label(__('document_files.fields.mime_type'))
                            ->required()
                            ->maxLength(100),
                        Select::make('storage_provider')
                            ->label(__('document_files.fields.storage_provider'))
                            ->options(self::storageOptions())
                            ->required()
                            ->default('local'),
                        TextInput::make('version')
                            ->label(__('document_files.fields.version'))
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                        Toggle::make('is_current')
                            ->label(__('document_files.fields.is_current'))
                            ->default(true),
                    ])
                    ->columns(3),
                Section::make(__('document_files.sections.metadata'))
                    ->schema([
                        DateTimePicker::make('uploaded_at')
                            ->label(__('document_files.fields.uploaded_at'))
                            ->seconds(false)
                            ->disabled(),
                        Select::make('uploaded_by')
                            ->label(__('document_files.fields.uploaded_by'))
                            ->relationship('uploadedBy', 'name')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
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
}
