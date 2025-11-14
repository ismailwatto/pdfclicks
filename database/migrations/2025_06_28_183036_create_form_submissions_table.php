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
        Schema::create('form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->string('form_name'); // Name of the form
            $table->string('email')->nullable(); // Email of the user submitting the form
            $table->json('data'); // JSON or serialized data of the form submission
            $table->timestamps();
        });
    }
};
