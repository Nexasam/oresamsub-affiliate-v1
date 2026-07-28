<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreatePlatformAdmin extends Command
{
    protected $signature = 'platform-admin:create {email?}';

    protected $description = 'Create a platform administrator';

    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Email address');
        $name = $this->ask('Name');
        $password = $this->secret('Password');

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $name || strlen((string) $password) < 8) {
            $this->error('A valid email, name, and password of at least 8 characters are required.');

            return self::FAILURE;
        }

        Admin::updateOrCreate(
            ['email' => strtolower($email)],
            ['name' => $name, 'password' => Hash::make($password), 'active' => true]
        );

        $this->info("Platform administrator {$email} is ready.");

        return self::SUCCESS;
    }
}
