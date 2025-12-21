<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages\CreateNotification;
use App\Filament\Resources\NotificationResource\Pages\EditNotification;
use App\Filament\Resources\NotificationResource\Pages\ListNotifications;
use App\Filament\Resources\NotificationResource\Pages\ViewNotification;
use App\Filament\Resources\NotificationResource\Schemas\NotificationForm;
use App\Filament\Resources\NotificationResource\Schemas\NotificationInfolist;
use App\Filament\Resources\NotificationResource\Schemas\NotificationTable;
use App\Models\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedBellAlert;

    public static function getModelLabel(): string
    {
        return __('notifications.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('notifications.model.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('notifications.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('notifications.navigation_group');
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'title';
    }

    public static function form(Schema $schema): Schema
    {
        return NotificationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return NotificationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NotificationTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotifications::route('/'),
            'create' => CreateNotification::route('/create'),
            'view' => ViewNotification::route('/{record}'),
            'edit' => EditNotification::route('/{record}/edit'),
        ];
    }
}
