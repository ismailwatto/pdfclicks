<?php

declare(strict_types=1);

namespace App\Filament\Resources\FormSubmissions\Pages;

use App\Filament\Resources\FormSubmissions\FormSubmissionResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateFormSubmission extends CreateRecord
{
    protected static string $resource = FormSubmissionResource::class;
}
