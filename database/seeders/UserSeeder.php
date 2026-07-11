<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@exostool.local'],
            ['name' => 'Administrador', 'password' => 'password']
        )->assignRole('admin');

        User::firstOrCreate(
            ['email' => 'ingeniero@exostool.local'],
            ['name' => 'Ingeniero Demo', 'password' => 'password']
        )->assignRole('engineer');

        User::firstOrCreate(
            ['email' => 'lectura@exostool.local'],
            ['name' => 'Usuario Lectura', 'password' => 'password']
        )->assignRole('reader');
    }
}
