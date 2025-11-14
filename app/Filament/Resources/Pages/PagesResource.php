<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Pages\CreatePages;
use App\Filament\Resources\Pages\Pages\EditPages;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Pages\Schemas\PagesForm;
use App\Filament\Resources\Pages\Tables\PagesTable;
use App\Models\Pages;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class PagesResource extends Resource
{
    protected static ?string $model = Pages::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldSkipAuthorization = true;

    public static function form(Schema $schema): Schema
    {
        return PagesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePages::route('/create'),
            'edit' => EditPages::route('/{record}/edit'),
        ];
    }
}
