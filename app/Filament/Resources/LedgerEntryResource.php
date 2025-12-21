<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LedgerEntryResource\Pages\CreateLedgerEntry;
use App\Filament\Resources\LedgerEntryResource\Pages\EditLedgerEntry;
use App\Filament\Resources\LedgerEntryResource\Pages\ListLedgerEntries;
use App\Filament\Resources\LedgerEntryResource\Pages\ViewLedgerEntry;
use App\Filament\Resources\LedgerEntryResource\Schemas\LedgerEntryForm;
use App\Filament\Resources\LedgerEntryResource\Schemas\LedgerEntryInfolist;
use App\Filament\Resources\LedgerEntryResource\Schemas\LedgerEntryTable;
use App\Models\LedgerEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LedgerEntryResource extends Resource
{
    protected static ?string $model = LedgerEntry::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedBookOpen;

    public static function getModelLabel(): string
    {
        return __('ledger_entries.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ledger_entries.model.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('ledger_entries.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('ledger_entries.navigation_group');
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'description';
    }

    public static function form(Schema $schema): Schema
    {
        return LedgerEntryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LedgerEntryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LedgerEntryTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLedgerEntries::route('/'),
            'create' => CreateLedgerEntry::route('/create'),
            'view' => ViewLedgerEntry::route('/{record}'),
            'edit' => EditLedgerEntry::route('/{record}/edit'),
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
