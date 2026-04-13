<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Administrator
        User::firstOrCreate(['email' => 'admin@example.com'], [
            'name'     => 'Administrator',
            'role'     => 'administrator',
            'password' => 'admin1234',
        ]);

        // Assessor
        User::firstOrCreate(['email' => 'assessor@example.com'], [
            'name'     => 'Assessor User',
            'role'     => 'assessor',
            'password' => 'assessor1234',
        ]);
    }
}
