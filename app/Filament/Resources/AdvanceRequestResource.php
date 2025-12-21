<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvanceRequestResource\Pages\CreateAdvanceRequest;
use App\Filament\Resources\AdvanceRequestResource\Pages\EditAdvanceRequest;
use App\Filament\Resources\AdvanceRequestResource\Pages\ListAdvanceRequests;
use App\Filament\Resources\AdvanceRequestResource\Pages\ViewAdvanceRequest;
use App\Filament\Resources\AdvanceRequestResource\Schemas\AdvanceRequestForm;
use App\Filament\Resources\AdvanceRequestResource\Schemas\AdvanceRequestInfolist;
use App\Filament\Resources\AdvanceRequestResource\Schemas\AdvanceRequestTable;
use App\Models\AdvanceRequest;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdvanceRequestResource extends Resource
{
    protected static ?string $model = AdvanceRequest::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function getModelLabel(): string
    {
        return __('advance_requests.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('advance_requests.model.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('advance_requests.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('advance_requests.navigation_group');
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'requested_at';
    }

    public static function form(Schema $schema): Schema
    {
        return AdvanceRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AdvanceRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdvanceRequestTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdvanceRequests::route('/'),
            'create' => CreateAdvanceRequest::route('/create'),
            'view' => ViewAdvanceRequest::route('/{record}'),
            'edit' => EditAdvanceRequest::route('/{record}/edit'),
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
