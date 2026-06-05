<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CreateAffiliateAdminUser extends Command
{
    protected $signature = 'affiliate:create-admin {affiliate_id}';
    protected $description = 'Create admin user for an affiliate';

    public function handle()
    {
        $affiliateId = $this->argument('affiliate_id');

        $email = 'adebsholey4real@gmail.com';

        // ✅ Skip if already exists for this affiliate
        $existing = User::where('affiliate_id', $affiliateId)
            ->where('email', $email)
            ->where('role_id', 1)
            ->first();

        if ($existing) {
            $this->info("Admin already exists for this affiliate. Skipping...");
            return;
        }

        // Optional: prevent duplicate email globally (safer)
        $globalCheck = User::where('email', $email)->first();

        if ($globalCheck) {
            $this->info("Email already exists in system. Skipping creation...");
            return;
        }

        $user = User::create([
            'username' => 'samuel.adebunmi',
            'affiliate_id' => $affiliateId,
            'first_name' => 'Samuel',
            'last_name' => 'Adebunmi',
            'email' => $email,
            'password' => bcrypt('password123'), // change later
            'role_id' => 1,
            'active' => 1,
            'customer_category' => 'generic',
            'user_2fa_setting' => 'OFF',
            'default_wallet_setting' => 'main_wallet',
            'main_wallet' => 0,
            'verification_status' => 0,
        ]);

        $this->info("Affiliate admin created successfully: {$user->email}");
    }
}