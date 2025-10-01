<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminExists = User::where('USR_UserRole', 'admin')->exists();

        if (!$adminExists) {
            User::create([
                'USR_Name' => 'Admin',
                'USR_LastName' => 'Roblox',
                'USR_Email' => 'admin@gmail.com',
                'USR_Phone' => '8713574926',
                'USR_Password' => Hash::make('password'),
                'USR_UserRole' => 'admin',
                'USR_FCM' => 'esta vaina no ministro'
            ]);
        }
    }
}
