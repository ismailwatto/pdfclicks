<?php

declare(strict_types=1);

namespace App\Filament\Resources\Blogs\Pages;

use App\Filament\Resources\Blogs\BlogsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListBlogs extends ListRecords
{
    protected static string $resource = BlogsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
