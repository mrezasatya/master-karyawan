<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->warn('Seeder demo tidak dijalankan di production.');

            return;
        }
        $this->call(DemoSeeder::class);
    }
}
