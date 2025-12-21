<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLogResource\Pages\ViewActivityLog;
use App\Filament\Resources\ActivityLogResource\Schemas\ActivityLogInfolist;
use App\Filament\Resources\ActivityLogResource\Schemas\ActivityLogTable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function getModelLabel(): string
    {
        return __('activity_logs.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('activity_logs.model.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('activity_logs.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('activity_logs.navigation_group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ActivityLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivityLogTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
            'view' => ViewActivityLog::route('/{record}'),
        ];
    }
}
