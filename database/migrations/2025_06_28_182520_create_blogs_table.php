<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('author')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();

            $table->json('tags')->nullable(); // Store tags as a JSON array
            $table->json('categories')->nullable(); // Store categories as a JSON array
            $table->json('seo_data')->nullable(); // Store additional SEO data as JSON
            $table->timestamps();

            $table->index('slug');
        });
    }
};
