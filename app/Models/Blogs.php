<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Actions\SitemapAction;

final class Blogs extends Model
{
    /** @use HasFactory<\Database\Factories\BlogsFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'content',
        'published_at',
        'featured_image',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'published_at' => 'datetime',
        'is_active' => 'boolean',
        'tags' => 'array',
        'categories' => 'array',
        'seo_data' => 'array',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    protected static function booted()
    {
        // Regenerate sitemap when post is created/updated/deleted
        self::created(function ($post) {
            app(SitemapAction::class)->generate();
        });

        self::updated(function ($post) {
            app(SitemapAction::class)->generate();
        });

        self::deleted(function ($post) {
            app(SitemapAction::class)->generate();
        });
    }
}
