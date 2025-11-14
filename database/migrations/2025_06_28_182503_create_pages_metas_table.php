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
        Schema::create('pages_metas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')
                ->constrained('pages')
                ->onDelete('cascade');
            $table->string('meta_key');
            $table->text('meta_value')->nullable();
            $table->timestamps();

            $table->index(['page_id', 'meta_key'], 'page_meta_index');
            $table->unique(['page_id', 'meta_key'], 'page_meta_unique');
        });
    }
};
