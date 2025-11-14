<?php

declare(strict_types=1);

namespace App\Filament\Resources\Blogs;

use App\Filament\Resources\Blogs\Pages\CreateBlogs;
use App\Filament\Resources\Blogs\Pages\EditBlogs;
use App\Filament\Resources\Blogs\Pages\ListBlogs;
use App\Filament\Resources\Blogs\Schemas\BlogsForm;
use App\Filament\Resources\Blogs\Tables\BlogsTable;
use App\Models\Blogs;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class BlogsResource extends Resource
{
    protected static ?string $model = Blogs::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static bool $shouldSkipAuthorization = true;

    public static function form(Schema $schema): Schema
    {
        return BlogsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlogsTable::configure($table);
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
            'index' => ListBlogs::route('/'),
            'create' => CreateBlogs::route('/create'),
            'edit' => EditBlogs::route('/{record}/edit'),
        ];
    }
}
