<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PagesResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePages extends CreateRecord
{
    protected static string $resource = PagesResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Extract SEO data before creating the main record
        $seoData = [
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
        ];

        // Remove SEO data from main record data
        unset($data['meta_title'], $data['meta_description'], $data['meta_keywords']);

        // Create the main page record
        $record = self::getModel()::create($data);

        // Save SEO data to pages_metas table
        foreach ($seoData as $key => $value) {
            if ($value !== null) {
                \App\Models\PagesMeta::create([
                    'page_id' => $record->id,
                    'meta_key' => $key,
                    'meta_value' => $value,
                ]);
            }
        }

        return $record;
    }
}
