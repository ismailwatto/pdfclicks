<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (User::where('email', 'admin@pdfclicks.com')->exists()) {
            return;
        }
        User::factory()->create();

        // User::create([
        //     'name' => 'Admin User',
        //     'email' => 'admin@pdfclicks.com',
        //     'password' => bcrypt('password'), // Ensure to hash the password
        // ]);

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
