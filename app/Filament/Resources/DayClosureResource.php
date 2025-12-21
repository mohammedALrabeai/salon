<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DayClosureResource\Pages\CreateDayClosure;
use App\Filament\Resources\DayClosureResource\Pages\EditDayClosure;
use App\Filament\Resources\DayClosureResource\Pages\ListDayClosures;
use App\Filament\Resources\DayClosureResource\Pages\ViewDayClosure;
use App\Filament\Resources\DayClosureResource\Schemas\DayClosureForm;
use App\Filament\Resources\DayClosureResource\Schemas\DayClosureInfolist;
use App\Filament\Resources\DayClosureResource\Schemas\DayClosureTable;
use App\Models\DayClosure;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DayClosureResource extends Resource
{
    protected static ?string $model = DayClosure::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function getModelLabel(): string
    {
        return __('day_closures.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('day_closures.model.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('day_closures.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('day_closures.navigation_group');
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'date';
    }

    public static function form(Schema $schema): Schema
    {
        return DayClosureForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DayClosureInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DayClosureTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDayClosures::route('/'),
            'create' => CreateDayClosure::route('/create'),
            'view' => ViewDayClosure::route('/{record}'),
            'edit' => EditDayClosure::route('/{record}/edit'),
        ];
    }
}
