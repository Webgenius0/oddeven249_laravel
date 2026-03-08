<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Support\Carbon;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $walletService = app(WalletService::class);

        // ৫. Admin User
        $admin = User::create([
            'name'              => 'Admin',
            'email'             => 'admin@admin.com',
            'password'          => Hash::make('12345678'),
            'role'              => 'advertiser',
            'email_verified_at' => Carbon::now(),
        ]);
        $walletService->createWallet($admin);
        // ১. Normal Advertiser User
        $user = User::create([
            'name'              => 'User',
            'email'             => 'user@user.com',
            'password'          => Hash::make('12345678'),
            'role'              => 'advertiser',
            'email_verified_at' => Carbon::now(),
        ]);
        $walletService->createWallet($user);

        // ২. Influencer Users
        $influencer = User::create([
            'name'              => 'Influencer User',
            'email'             => 'influencer@gmail.com',
            'password'          => Hash::make('12345678'),
            'role'              => 'influencer',
            'email_verified_at' => Carbon::now(),
        ]);
        $walletService->createWallet($influencer);

        $influencer2 = User::create([
            'name'              => 'Second Influencer',
            'email'             => 'influencer2@gmail.com',
            'password'          => Hash::make('12345678'),
            'role'              => 'influencer',
            'email_verified_at' => Carbon::now(),
        ]);
        $walletService->createWallet($influencer2);

        // ৩. Business Managers (BM)
        $manager = User::create([
            'name'              => 'Manager for Influencer',
            'email'             => 'inf_manager@gmail.com',
            'password'          => Hash::make('12345678'),
            'role'              => 'business_manager',
            'country'           => 'Bangladesh',
            'phone'             => '+8801700000000',
            'email_verified_at' => Carbon::now(),
        ]);
        $walletService->createWallet($manager);

        $manager2 = User::create([
            'name'              => 'Global Business Manager',
            'email'             => 'manager2@gmail.com',
            'password'          => Hash::make('12345678'),
            'role'              => 'business_manager',
            'country'           => 'USA',
            'email_verified_at' => Carbon::now(),
        ]);
        $walletService->createWallet($manager2);

        // ৪. Agencies
        $agency = User::create([
            'name'              => 'Agency User',
            'email'             => 'agency@agency.com',
            'password'          => Hash::make('12345678'),
            'role'              => 'agency',
            'email_verified_at' => Carbon::now(),
        ]);
        $walletService->createWallet($agency);

        $agency1 = User::create([
            'name'              => 'Global Agency Ltd',
            'email'             => 'global@agency.com',
            'password'          => Hash::make('12345678'),
            'role'              => 'agency',
            'email_verified_at' => Carbon::now(),
        ]);
        $walletService->createWallet($agency1);

        // --- Permissions Logic ---

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
            'create_event_permission' => false,
        ];

        $influencer->agencies()->attach($manager->id, [
            'permissions' => json_encode($bmPermissions),
        ]);

        $influencer->agencies()->attach($agency->id, [
            'permissions' => json_encode($agencyPermissions),
        ]);

        $influencer2->agencies()->attach($manager2->id, [
            'permissions' => json_encode($bmPermissions),
        ]);

        $influencer2->agencies()->attach($agency1->id, [
            'permissions' => json_encode($agencyPermissions),
        ]);

        $influencer2->agencies()->attach($manager->id, [
            'permissions' => json_encode($bmPermissions),
        ]);
    }
}
