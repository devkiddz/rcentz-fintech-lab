<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the complete development/demo environment.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            CoreDataSeeder::class,
            LiveTestDataSeeder::class,
        ]);
    }
}
