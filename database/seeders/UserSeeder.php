<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Carbon;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Normal User
        User::create([
            'name' => 'User',
            'email' => 'user@user.com',
            'password' => Hash::make('12345678'),
            'role' => 'advertiser',
            'email_verified_at' => Carbon::now(),
        ]);
        User::create([
                    'name' => 'Influencer User',
                    'email' => 'influencer@gmail.com',
                    'password' => Hash::make('12345678'),
                    'role' => 'influencer',
                    'email_verified_at' => Carbon::now(),
                ]);
        User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('12345678'),
            'email_verified_at' => Carbon::now(),
        ]);
        User::create([
            'name' => 'Agency User',
            'email' => 'agency@agency.com',
            'password' => Hash::make('12345678'),
            'role' => 'agency',
            'email_verified_at' => Carbon::now(),
        ]);
    }
}
