<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

final class PagesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Page Settings')
                    ->tabs([
                        Tab::make('General')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Title')
                                    ->columnSpanFull(),
                                TextInput::make('slug')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                                    ->label('Slug')
                                    ->helperText('Must be lowercase, alphanumeric, and can contain hyphens.')
                                    ->placeholder('Enter the slug for the page...')
                                    ->columnSpanFull()
                                    ->dehydrateStateUsing(fn ($state, $record) => \Illuminate\Support\Str::slug($state ?? $record->title))
                                    ->hint('The slug is used in the URL and must be unique.'),
                                RichEditor::make('content')
                                    ->columnSpanFull()
                                    ->required()
                                    ->label('Content')
                                    ->helperText('Use the editor to format your content.')
                                    ->maxLength(65535)
                                    ->placeholder('Enter the content of the page here...'),
                                Toggle::make('is_active')
                                    ->required(),
                                Select::make('parent_id')
                                    ->label('Parent Page')
                                    ->options(
                                        fn ($record) => \App\Models\Pages::query()
                                            ->where('is_active', true)
                                            ->when($record, fn ($query, $record) => $query->where('id', '!=', $record->id))
                                            ->whereNull('parent_id')
                                            ->pluck('title', 'id')
                                    )
                                    ->nullable(),
                            ]),
                        Tab::make('SEO Settings')
                            ->schema([
                                // These fields are stored in the pages_metas table with meta_key and meta_value pairs
                                TextInput::make('meta_title')
                                    ->maxLength(255)
                                    ->label('Meta Title')
                                    ->placeholder('Enter the meta title for SEO...')
                                    ->columnSpanFull(),
                                Textarea::make('meta_description')
                                    ->maxLength(500)
                                    ->label('Meta Description')
                                    ->placeholder('Enter the meta description for SEO...')
                                    ->columnSpanFull(),
                                TextInput::make('meta_keywords')
                                    ->maxLength(255)
                                    ->label('Meta Keywords')
                                    ->placeholder('Enter keywords for SEO, separated by commas...')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])->columns([
                'sm' => 1,
                'md' => 2,
                'lg' => 2,
                'xl' => 2,
                '2xl' => 2,
            ]);
    }
}
