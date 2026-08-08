<?php

namespace Database\Seeders;

use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\ParentResellerLevel;
use App\Models\ProviderConnection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class OresamsubParentSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('parent_businesses.oresamsub');

        $parent = ParentBusiness::query()->updateOrCreate(
            ['slug' => $config['slug']],
            ['name' => $config['name']],
        );

        $providerConfig = $config['provider'];
        $provider = ProviderConnection::query()->updateOrCreate(
            ['slug' => $providerConfig['slug']],
            [
                'name' => $providerConfig['name'],
                'adapter' => $providerConfig['adapter'],
            ],
        );

        ParentProviderConnection::query()->updateOrCreate(
            [
                'parent_business_id' => $parent->id,
                'provider_connection_id' => $provider->id,
                'name' => $providerConfig['name'],
            ],
            ['base_url' => $providerConfig['base_url']],
        );

        foreach (['Basic', 'Bronze', 'Silver', 'Gold', 'Diamond', 'Platinum'] as $index => $name) {
            ParentResellerLevel::query()->updateOrCreate(
                [
                    'parent_business_id' => $parent->id,
                    'position' => $index + 1,
                ],
                ['name' => $name],
            );
        }

        $adminConfig = $config['admin'];
        $existingAdmin = ParentAdmin::query()->where('email', $adminConfig['email'])->first();

        if ($existingAdmin && $existingAdmin->parent_business_id !== $parent->id) {
            throw new RuntimeException('The configured OresamSub parent-admin email belongs to another parent.');
        }

        if ($existingAdmin) {
            return;
        }

        $configuredPassword = $adminConfig['password'];
        $temporaryPassword = filled($configuredPassword)
            ? $configuredPassword
            : $this->generateTemporaryPassword();

        $admin = ParentAdmin::query()->firstOrCreate(
            ['email' => $adminConfig['email']],
            [
                'parent_business_id' => $parent->id,
                'name' => $adminConfig['name'],
                'password' => $temporaryPassword,
                'must_change_password' => true,
                'active' => true,
            ],
        );

        if ($admin->wasRecentlyCreated && blank($configuredPassword)) {
            $this->command?->warn("Temporary OresamSub parent-admin password: {$temporaryPassword}");
        }
    }

    protected function generateTemporaryPassword(): string
    {
        return Str::password(24);
    }
}
