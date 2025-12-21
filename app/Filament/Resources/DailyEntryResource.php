<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyEntryResource\Pages\CreateDailyEntry;
use App\Filament\Resources\DailyEntryResource\Pages\EditDailyEntry;
use App\Filament\Resources\DailyEntryResource\Pages\ListDailyEntries;
use App\Filament\Resources\DailyEntryResource\Pages\ViewDailyEntry;
use App\Filament\Resources\DailyEntryResource\Schemas\DailyEntryForm;
use App\Filament\Resources\DailyEntryResource\Schemas\DailyEntryInfolist;
use App\Filament\Resources\DailyEntryResource\Schemas\DailyEntryTable;
use App\Models\DailyEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DailyEntryResource extends Resource
{
    protected static ?string $model = DailyEntry::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function getModelLabel(): string
    {
        return __('daily_entries.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('daily_entries.model.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('daily_entries.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('daily_entries.navigation_group');
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'date';
    }

    public static function form(Schema $schema): Schema
    {
        return DailyEntryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DailyEntryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DailyEntryTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDailyEntries::route('/'),
            'create' => CreateDailyEntry::route('/create'),
            'view' => ViewDailyEntry::route('/{record}'),
            'edit' => EditDailyEntry::route('/{record}/edit'),
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
