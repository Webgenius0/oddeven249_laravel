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
        // ১. Normal Advertiser User
        User::create([
            'name' => 'User',
            'email' => 'user@user.com',
            'password' => Hash::make('12345678'),
            'role' => 'advertiser',
            'email_verified_at' => Carbon::now(),
        ]);

        // ২. Influencer Users
        $influencer = User::create([
            'name' => 'Influencer User',
            'email' => 'influencer@gmail.com',
            'password' => Hash::make('12345678'),
            'role' => 'influencer',
            'email_verified_at' => Carbon::now(),
        ]);

        $influencer2 = User::create([
            'name' => 'Second Influencer',
            'email' => 'influencer2@gmail.com',
            'password' => Hash::make('12345678'),
            'role' => 'influencer',
            'email_verified_at' => Carbon::now(),
        ]);

        // ৩. Business Managers (BM)
        $manager = User::create([
            'name' => 'Manager for Influencer',
            'email' => 'inf_manager@gmail.com',
            'password' => Hash::make('12345678'),
            'role' => 'business_manager',
            'country' => 'Bangladesh',
            'phone' => '+8801700000000',
            'email_verified_at' => Carbon::now(),
        ]);

        $manager2 = User::create([
            'name' => 'Global Business Manager',
            'email' => 'manager2@gmail.com',
            'password' => Hash::make('12345678'),
            'role' => 'business_manager',
            'country' => 'USA',
            'email_verified_at' => Carbon::now(),
        ]);

        // ৪. Agencies
        $agency = User::create([
            'name' => 'Agency User',
            'email' => 'agency@agency.com',
            'password' => Hash::make('12345678'),
            'role' => 'agency',
            'email_verified_at' => Carbon::now(),
        ]);

        $agency1 = User::create([
            'name' => 'Global Agency Ltd',
            'email' => 'global@agency.com',
            'password' => Hash::make('12345678'),
            'role' => 'agency',
            'email_verified_at' => Carbon::now(),
        ]);

        // ৫. Admin User
        User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('12345678'),
            'role' => 'advertiser',
            'email_verified_at' => Carbon::now(),
        ]);

        // --- Permissions Logic ---

        // Business Manager Permission Structure
        $bmPermissions = [
            'view_deal'           => false,
            'view_earning'        => false,
            'view_portfolio'      => true,
            'manage_contests'     => false,
            'chat_with_client'    => true,
            'accept_reject_deals' => false,
        ];

        $agencyPermissions = [
            'chat_permission'         => true,
            'portfolio_permission'    => true,
            'deal_manage_permission'  => true,
            'voucher_add_permission'  => false,
            'create_event_permission' => false
        ];

        $influencer->agencies()->attach($manager->id, [
            'permissions' => json_encode($bmPermissions)
        ]);

        $influencer->agencies()->attach($agency->id, [
            'permissions' => json_encode($agencyPermissions)
        ]);

        $influencer2->agencies()->attach($manager2->id, [
            'permissions' => json_encode($bmPermissions)
        ]);

        $influencer2->agencies()->attach($agency1->id, [
            'permissions' => json_encode($agencyPermissions)
        ]);

        $influencer2->agencies()->attach($manager->id, [
            'permissions' => json_encode($bmPermissions)
        ]);
    }
}
