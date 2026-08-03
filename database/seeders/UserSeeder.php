<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            ['name' => 'Alexandru Popa', 'email' => 'alexandru@twm.ro', 'password' => 'twm2025', 'role' => 'admin'],
        ])->each(function ($factory) {
            $user = User::factory()->make([
                'name' => $factory['name'],
                'email' => $factory['email'],
            ]);

            $user->password = Hash::make($factory['password']);

            $user->save();

            $user->assignRole($factory['role']);

        });
    }
}
