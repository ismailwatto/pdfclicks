<?php

declare(strict_types=1);

namespace App\Filament\Resources\Blogs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

final class BlogsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                        if (($get('slug') ?? '') !== Str::slug($old)) {
                            return;
                        }

                        $set('slug', Str::slug($state));
                    }),
                TextInput::make('slug')
                    ->required()
                    ->unique(table: 'blogs', column: 'slug', ignoreRecord: true)
                    ->helperText('Auto-generated from the title, or you can enable editing.'),
                RichEditor::make('content')
                    ->columnSpanFull(),
                DateTimePicker::make('published_at'),
                FileUpload::make('featured_image')
                    ->image()
                    ->directory('blogs')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['image/*'])
                    ->maxSize(2048) // 2MB
                    ->helperText('Upload a featured image for the blog post.')
                    ->disk('public')
                    ->visibility('public'),
                TextInput::make('meta_title'),
                TextInput::make('meta_description'),
            ]);
    }
}
