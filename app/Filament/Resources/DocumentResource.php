<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages\CreateDocument;
use App\Filament\Resources\DocumentResource\Pages\EditDocument;
use App\Filament\Resources\DocumentResource\Pages\ListDocuments;
use App\Filament\Resources\DocumentResource\Pages\ViewDocument;
use App\Filament\Resources\DocumentResource\RelationManagers\DocumentFilesRelationManager;
use App\Filament\Resources\DocumentResource\Schemas\DocumentForm;
use App\Filament\Resources\DocumentResource\Schemas\DocumentInfolist;
use App\Filament\Resources\DocumentResource\Schemas\DocumentTable;
use App\Models\Document;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function getModelLabel(): string
    {
        return __('documents.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('documents.model.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('documents.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('documents.navigation_group');
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'title';
    }

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DocumentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'view' => ViewDocument::route('/{record}'),
            'edit' => EditDocument::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            DocumentFilesRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
