<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Pages extends Model
{
    /** @use HasFactory<\Database\Factories\PagesFactory> */
    use HasFactory;

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id')->where('is_active', true)->whereNull('parent_id');
    }
}
