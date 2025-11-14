<?php

declare(strict_types=1);

namespace App\Filament\Resources\Blogs\Pages;

use App\Filament\Resources\Blogs\BlogsResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateBlogs extends CreateRecord
{
    protected static string $resource = BlogsResource::class;
}
