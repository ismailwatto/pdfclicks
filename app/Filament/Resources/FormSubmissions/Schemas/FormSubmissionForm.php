<?php

declare(strict_types=1);

namespace App\Filament\Resources\FormSubmissions\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

final class FormSubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
}
