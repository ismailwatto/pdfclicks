<?php

declare(strict_types=1);

namespace App\Filament\Resources\FormSubmissions;

use App\Filament\Resources\FormSubmissions\Pages\ListFormSubmissions;
use App\Filament\Resources\FormSubmissions\Pages\ViewFormSubmissions;
use App\Models\FormSubmissions;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

final class FormSubmissionResource extends Resource
{
    protected static ?string $model = FormSubmissions::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Form Submissions';

    protected static ?string $pluralModelLabel = 'Form Submissions';

    protected static bool $shouldSkipAuthorization = true;

    public static function getNavigationGroup(): ?string
    {
        return 'Forms';
    }

    public static function getNavigationLabel(): string
    {
        return 'Form Submissions';
    }

    public static function getModelLabel(): string
    {
        return 'Form Submission';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Form Submissions';
    }

    public static function getNavigationSort(): ?int
    {
        return 100;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['form_name', 'email'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('form_name')->label('Form Name')->disabled(),
            Forms\Components\TextInput::make('email')->label('Email')->disabled(),
            Forms\Components\KeyValue::make('data')
                ->label('Submitted Data')
                ->keyLabel('Field')
                ->valueLabel('Value')
                ->disabled(),
            Forms\Components\DateTimePicker::make('created_at')->label('Submitted At')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('form_name')->label('Form Name')->searchable()->sortable()->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucwords(mb_strtolower($state)))),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Submitted At')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('form_name')
                    ->label('Form Name')
                    ->options(fn () => FormSubmissions::query()->distinct()->pluck('form_name', 'form_name')->toArray()),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFormSubmissions::route('/'),
            'view' => ViewFormSubmissions::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    private function getHeaderActions(): array
    {
        return [];
    }
}
