<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('Admin234'),
            'role' => 'Admin',
        ]);

        User::create([
            'name' => 'Proveedor',
            'email' => 'prov@example.com',
            'password' => Hash::make('Prov234'),
            'role' => 'Provider',
        ]);

        User::create([
            'name' => 'Cliente',
            'email' => 'client@example.com',
            'password' => Hash::make('Client234'),
            'role' => 'Client',
        ]);
    }
}
