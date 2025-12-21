<?php

namespace App\Filament\Resources\DocumentResource\RelationManagers;

use App\Filament\Resources\DocumentResource\RelationManagers\Schemas\DocumentFileForm;
use App\Filament\Resources\DocumentResource\RelationManagers\Schemas\DocumentFileTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DocumentFilesRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    protected static string | \BackedEnum | null $icon = Heroicon::OutlinedPaperClip;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('document_files.relation_title');
    }

    public function form(Schema $schema): Schema
    {
        return DocumentFileForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return DocumentFileTable::configure($table);
    }
}
