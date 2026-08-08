<?php

use App\Models\ParentAdmin;
use App\Models\ParentBusiness;
use App\Models\ParentProviderConnection;
use App\Models\ParentResellerLevel;
use App\Models\ProviderConnection;
use Database\Seeders\OresamsubParentSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

it('idempotently seeds the OresamSub parent foundation', function () {
    config()->set('parent_businesses.oresamsub.admin', [
        'name' => 'Owner Name',
        'email' => 'owner@example.test',
        'password' => 'local-secret',
    ]);

    $this->seed(OresamsubParentSeeder::class);
    $this->seed(OresamsubParentSeeder::class);

    $parent = ParentBusiness::where('slug', 'oresamsub')->sole();
    $admin = ParentAdmin::where('email', 'owner@example.test')->sole();

    expect(ParentBusiness::where('slug', 'oresamsub')->count())->toBe(1)
        ->and(ProviderConnection::where('slug', 'oresamsub')->count())->toBe(1)
        ->and(ParentProviderConnection::where('parent_business_id', $parent->id)->count())->toBe(1)
        ->and(ParentResellerLevel::where('parent_business_id', $parent->id)->count())->toBe(6)
        ->and(ParentResellerLevel::where('parent_business_id', $parent->id)
            ->orderBy('position')->pluck('name')->all())
        ->toBe(['Basic', 'Bronze', 'Silver', 'Gold', 'Diamond', 'Platinum'])
        ->and(ParentAdmin::where('parent_business_id', $parent->id)->count())->toBe(1)
        ->and($admin->password)->not->toBe('local-secret')
        ->and(Hash::check('local-secret', $admin->password))->toBeTrue();
});

it('reports a temporary password only when creating an admin without a configured password', function () {
    config()->set('parent_businesses.oresamsub.admin', [
        'name' => 'Temporary Owner',
        'email' => 'temporary-owner@example.test',
        'password' => null,
    ]);

    Artisan::call('db:seed', ['--class' => OresamsubParentSeeder::class]);
    $firstOutput = Artisan::output();
    Artisan::call('db:seed', ['--class' => OresamsubParentSeeder::class]);
    $secondOutput = Artisan::output();

    $admin = ParentAdmin::where('email', 'temporary-owner@example.test')->sole();

    expect($firstOutput)->toContain('Temporary OresamSub parent-admin password:')
        ->and($secondOutput)->not->toContain('Temporary OresamSub parent-admin password:')
        ->and($admin->must_change_password)->toBeTrue();
});

it('generates a temporary password only while creating a new admin', function () {
    config()->set('parent_businesses.oresamsub.admin', [
        'name' => 'Temporary Owner',
        'email' => 'temporary-owner@example.test',
        'password' => null,
    ]);
    $seeder = new class extends OresamsubParentSeeder
    {
        public int $temporaryPasswordGenerations = 0;

        protected function generateTemporaryPassword(): string
        {
            $this->temporaryPasswordGenerations++;

            return 'fixed-temporary-password';
        }
    };

    $seeder->run();
    $seeder->run();

    expect($seeder->temporaryPasswordGenerations)->toBe(1)
        ->and(Hash::check('fixed-temporary-password', ParentAdmin::sole()->password))->toBeTrue();
});

it('does not rotate an existing parent admin password', function () {
    config()->set('parent_businesses.oresamsub.admin', [
        'name' => 'Owner Name',
        'email' => 'owner@example.test',
        'password' => 'first-secret',
    ]);
    $this->seed(OresamsubParentSeeder::class);

    config()->set('parent_businesses.oresamsub.admin.password', 'second-secret');
    $this->seed(OresamsubParentSeeder::class);

    $password = ParentAdmin::where('email', 'owner@example.test')->sole()->password;

    expect(Hash::check('first-secret', $password))->toBeTrue()
        ->and(Hash::check('second-secret', $password))->toBeFalse();
});

it('rejects an admin email that belongs to another parent', function () {
    $otherParent = ParentBusiness::create(['name' => 'Other Parent', 'slug' => 'other-parent']);
    ParentAdmin::create([
        'parent_business_id' => $otherParent->id,
        'name' => 'Other Owner',
        'email' => 'shared@example.test',
        'password' => 'existing-secret',
    ]);
    config()->set('parent_businesses.oresamsub.admin', [
        'name' => 'OresamSub Owner',
        'email' => 'shared@example.test',
        'password' => 'new-secret',
    ]);

    expect(fn () => $this->seed(OresamsubParentSeeder::class))
        ->toThrow(RuntimeException::class);
});

it('holds and releases the scoped seed lock even when seeding fails', function () {
    $otherParent = ParentBusiness::create(['name' => 'Other Parent', 'slug' => 'locked-other-parent']);
    ParentAdmin::create([
        'parent_business_id' => $otherParent->id,
        'name' => 'Other Owner',
        'email' => 'locked-shared@example.test',
        'password' => 'existing-secret',
    ]);
    config()->set('parent_businesses.oresamsub.admin', [
        'name' => 'OresamSub Owner',
        'email' => 'locked-shared@example.test',
        'password' => 'new-secret',
    ]);
    $seeder = new class extends OresamsubParentSeeder
    {
        public int $acquisitions = 0;

        public int $releases = 0;

        protected function acquireSeederLock(): void
        {
            $this->acquisitions++;
        }

        protected function releaseSeederLock(): void
        {
            $this->releases++;
        }
    };

    expect(fn () => $seeder->run())->toThrow(RuntimeException::class)
        ->and($seeder->acquisitions)->toBe(1)
        ->and($seeder->releases)->toBe(1);
});
