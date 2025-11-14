<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PagesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditPages extends EditRecord
{
    protected static string $resource = PagesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Update the main page record with non-SEO data
        $seoData = [
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
        ];
        unset($data['meta_title'], $data['meta_description'], $data['meta_keywords']);

        $record->update($data);

        // Save SEO data to pages_metas table
        foreach ($seoData as $key => $value) {
            if ($value !== null) {
                \App\Models\PagesMeta::updateOrCreate(
                    ['page_id' => $record->id, 'meta_key' => $key],
                    ['meta_value' => $value]
                );
            }
        }

        return $record;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load SEO data from pages_metas table when filling the form
        $page = $this->record;
        $metaData = \App\Models\PagesMeta::where('page_id', $page->id)
            ->whereIn('meta_key', ['meta_title', 'meta_description', 'meta_keywords'])
            ->pluck('meta_value', 'meta_key')
            ->toArray();

        return array_merge($data, $metaData);
    }
}
