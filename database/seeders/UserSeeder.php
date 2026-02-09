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
        $influencer = User::where('role', 'influencer')->first();

        User::create([
            'name' => 'Manager for Influencer',
            'email' => 'inf_manager@gmail.com',
            'password' => Hash::make('12345678'),
            'role' => 'business_manager',
            // 'parent_id' => $influencer->id,
            'country' => 'Bangladesh',
            'phone' => '+8801700000000',
            'email_verified_at' => Carbon::now(),
            'manager_permissions' => [
                'view_deal' => true,
                'accept_reject_deals' => false,
                'chat_with_client' => true,
                'view_portfolio' => true,
                'manage_contests' => true,
                'view_earning' => false,
            ],
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
