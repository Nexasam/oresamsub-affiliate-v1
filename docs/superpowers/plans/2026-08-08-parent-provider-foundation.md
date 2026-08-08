# Parent Provider Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a backward-compatible parent/provider/pricing foundation, seed OresamSub, and safely backfill existing local ownership without switching live purchase or funding behavior.

**Architecture:** New normalized tables own parent identity, parent admins, provider catalogue entries, parent credentials, six reseller levels, parent plan prices, and primary/backup plan routes. Existing affiliates, plans, and transactions gain nullable snapshot relationships; an idempotent command performs a dry-run or committed OresamSub backfill in chunks. Legacy pricing columns and all current runtime purchase paths remain intact behind disabled feature flags.

**Tech Stack:** PHP 8.3, Laravel 13, Eloquent, Laravel migrations/seeders/Artisan commands, Pest feature tests, MySQL in local development and SQLite in tests.

## Global Constraints

- Work on the current `main` branch only because the user explicitly authorized it.
- Do not touch `docs/.DS_Store` or `docs/research/`.
- Do not hardcode parent ID `1`; resolve OresamSub by unique slug `oresamsub`.
- Preserve all existing primary IDs and legacy pricing/profit columns.
- Keep `product_plan_categories` global and do not add `parent_business_id` to it.
- Initial parent/provider/route foreign keys added to existing tables are nullable.
- OresamSub is the only seeded operational service provider in this plan.
- Parent provider credentials are encrypted at rest, hidden from serialization, and never logged.
- Parent reseller levels and affiliate customer levels are limited to positions 1–6.
- Backup routes may be stored but runtime failover is outside this plan.
- Existing purchase, refund, funding, commission, and landing-page behavior must remain unchanged.
- Every backfill supports exactly one of `--dry-run` and `--commit`, is idempotent, and processes large tables in chunks.
- Run focused tests after every task; run the full relevant regression suite before completion.

---

## File map

### New foundation files

- `database/migrations/2026_08_08_100000_create_parent_provider_foundation_tables.php` — creates parents, parent admins, provider catalogue, parent connections, and parent reseller levels.
- `database/migrations/2026_08_08_100100_add_parent_ownership_and_plan_routing.php` — adds nullable ownership snapshots and creates normalized plan prices/routes and migration audits.
- `app/Models/ParentBusiness.php` — parent aggregate relationships.
- `app/Models/ParentAdmin.php` — authenticatable parent administrator.
- `app/Models/ProviderConnection.php` — global provider catalogue entry.
- `app/Models/ParentProviderConnection.php` — encrypted parent-specific provider credentials.
- `app/Models/ParentResellerLevel.php` — parent-defined level position 1–6.
- `app/Models/ProductPlanParentPrice.php` — normalized plan price per parent level.
- `app/Models/ProductPlanProviderRoute.php` — provider-specific plan mapping and priority.
- `app/Models/MultiParentMigrationAudit.php` — row-level audit of customer-plan consolidation.
- `config/parent_businesses.php` — feature flags and OresamSub seed defaults.
- `database/seeders/OresamsubParentSeeder.php` — idempotent OresamSub parent/admin/provider/level seed.
- `app/Services/MultiParent/OresamsubFoundationBackfillService.php` — ownership, prices, routes, and level-consolidation backfill.
- `app/Console/Commands/BackfillOresamsubFoundation.php` — guarded CLI entry point.

### Existing files modified

- `config/auth.php` — adds the inactive-by-default `parent_admin` guard/provider; no routes are added yet.
- `app/Models/Affiliate.php` — parent and reseller-level relationships.
- `app/Models/ProductPlan.php` — parent, price, and route relationships.
- `app/Models/Transaction.php` — immutable parent/route snapshot relationships and casts.
- `database/seeders/DatabaseSeeder.php` — invokes only the safe idempotent parent seeder.
- `.env.example` — documents optional OresamSub parent-admin seed overrides and disabled flags.

### Tests

- `tests/Feature/MultiParent/ParentProviderSchemaTest.php`
- `tests/Feature/MultiParent/OresamsubParentSeederTest.php`
- `tests/Feature/MultiParent/OresamsubFoundationBackfillTest.php`
- `tests/Feature/MultiParent/LegacyLevelConsolidationTest.php`

---

### Task 1: Parent, provider, and reseller-level schema

**Files:**
- Create: `database/migrations/2026_08_08_100000_create_parent_provider_foundation_tables.php`
- Create: `app/Models/ParentBusiness.php`
- Create: `app/Models/ParentAdmin.php`
- Create: `app/Models/ProviderConnection.php`
- Create: `app/Models/ParentProviderConnection.php`
- Create: `app/Models/ParentResellerLevel.php`
- Modify: `config/auth.php`
- Test: `tests/Feature/MultiParent/ParentProviderSchemaTest.php`

**Interfaces:**
- Produces: `ParentBusiness::providerConnections()`, `ParentBusiness::resellerLevels()`, and `ParentProviderConnection.credentials` encrypted-array cast.
- Produces: auth guard `parent_admin` backed by `App\Models\ParentAdmin`; no login routes or dashboard are included.

- [ ] **Step 1: Write the failing schema and relationship tests**

Create Pest tests that use `RefreshDatabase` and assert:

```php
expect(Schema::hasColumns('parent_businesses', ['id', 'name', 'slug', 'status']))->toBeTrue()
    ->and(Schema::hasColumns('parent_admins', ['parent_business_id', 'email', 'password', 'must_change_password']))->toBeTrue()
    ->and(Schema::hasColumns('provider_connections', ['name', 'slug', 'adapter', 'capabilities', 'status']))->toBeTrue()
    ->and(Schema::hasColumns('parent_provider_connections', ['parent_business_id', 'provider_connection_id', 'base_url', 'credentials', 'settings']))->toBeTrue()
    ->and(Schema::hasColumns('parent_reseller_levels', ['parent_business_id', 'name', 'position', 'status']))->toBeTrue();
```

Add a credential test that creates a `ParentProviderConnection` with `['token' => 'secret-value']`, verifies the database value does not contain `secret-value`, verifies the model decrypts it, and verifies `toArray()` omits `credentials`.

Add uniqueness tests for parent slug, provider slug, `(parent_business_id, provider_connection_id, name)`, and `(parent_business_id, position)`.

- [ ] **Step 2: Run the tests and verify they fail**

Run:

```bash
php artisan test tests/Feature/MultiParent/ParentProviderSchemaTest.php
```

Expected: failure because the tables and models do not exist.

- [ ] **Step 3: Create the foundation migration**

Implement these exact constraints:

```php
Schema::create('parent_businesses', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('contact_email')->nullable();
    $table->string('contact_phone')->nullable();
    $table->string('status')->default('active')->index();
    $table->timestamps();
});

Schema::create('parent_admins', function (Blueprint $table) {
    $table->id();
    $table->foreignId('parent_business_id')->constrained()->restrictOnDelete();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->boolean('active')->default(true);
    $table->boolean('must_change_password')->default(true);
    $table->timestamp('last_login_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
});

Schema::create('provider_connections', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('adapter');
    $table->json('capabilities')->nullable();
    $table->string('status')->default('active')->index();
    $table->timestamps();
});

Schema::create('parent_provider_connections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('parent_business_id')->constrained()->restrictOnDelete();
    $table->foreignId('provider_connection_id')->constrained()->restrictOnDelete();
    $table->string('name');
    $table->string('base_url')->nullable();
    $table->text('credentials')->nullable();
    $table->json('settings')->nullable();
    $table->string('status')->default('active')->index();
    $table->timestamp('last_tested_at')->nullable();
    $table->timestamps();
    $table->unique(
        ['parent_business_id', 'provider_connection_id', 'name'],
        'parent_provider_connection_identity_unique'
    );
    $table->unique(['id', 'parent_business_id']);
});

Schema::create('parent_reseller_levels', function (Blueprint $table) {
    $table->id();
    $table->foreignId('parent_business_id')->constrained()->restrictOnDelete();
    $table->string('name');
    $table->unsignedTinyInteger('position');
    $table->string('status')->default('active')->index();
    $table->timestamps();
    $table->unique(['parent_business_id', 'position']);
    $table->unique(['id', 'parent_business_id']);
});
```

The migration `down()` drops tables in reverse dependency order: reseller levels, parent provider connections, providers, parent admins, parents.

- [ ] **Step 4: Implement focused models and auth configuration**

Use `Authenticatable` for `ParentAdmin`; cast password as `hashed`, booleans as booleans, dates as datetimes. In `ParentProviderConnection`:

```php
protected $hidden = ['credentials'];

protected function casts(): array
{
    return [
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'last_tested_at' => 'datetime',
    ];
}
```

Add to `config/auth.php`:

```php
'parent_admin' => [
    'driver' => 'session',
    'provider' => 'parent_admins',
],
```

and:

```php
'parent_admins' => [
    'driver' => 'eloquent',
    'model' => App\Models\ParentAdmin::class,
],
```

- [ ] **Step 5: Run schema tests and format**

```bash
vendor/bin/pint app/Models database/migrations/2026_08_08_100000_create_parent_provider_foundation_tables.php tests/Feature/MultiParent/ParentProviderSchemaTest.php config/auth.php
php artisan test tests/Feature/MultiParent/ParentProviderSchemaTest.php
```

Expected: all Task 1 tests pass.

- [ ] **Step 6: Commit Task 1**

```bash
git add config/auth.php app/Models/ParentBusiness.php app/Models/ParentAdmin.php app/Models/ProviderConnection.php app/Models/ParentProviderConnection.php app/Models/ParentResellerLevel.php database/migrations/2026_08_08_100000_create_parent_provider_foundation_tables.php tests/Feature/MultiParent/ParentProviderSchemaTest.php
git commit -m "feat: add parent provider foundation schema"
```

---

### Task 2: Idempotent OresamSub seed and temporary parent admin

**Files:**
- Create: `config/parent_businesses.php`
- Create: `database/seeders/OresamsubParentSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `.env.example`
- Test: `tests/Feature/MultiParent/OresamsubParentSeederTest.php`

**Interfaces:**
- Produces: `OresamsubParentSeeder::run(): void`.
- Produces: config keys `parent_businesses.oresamsub.*` and feature flags under `parent_businesses.features.*`.
- Consumes: Task 1 models and tables.

- [ ] **Step 1: Write failing seeder tests**

Test that two seeder runs create exactly one OresamSub parent, one provider definition, one parent connection, six ordered levels, and one parent admin. Assert the six names are exactly Basic, Bronze, Silver, Gold, Diamond, and Platinum.

Add one test with explicit config values:

```php
config()->set('parent_businesses.oresamsub.admin', [
    'name' => 'Owner Name',
    'email' => 'owner@example.test',
    'password' => 'local-secret',
]);
```

Assert the stored password is hashed and `Hash::check('local-secret', $admin->password)` is true.

Add a fallback test with a null password. Capture command output and assert it contains `Temporary OresamSub parent-admin password:` only on the first run and the created admin has `must_change_password = true`.

- [ ] **Step 2: Verify the tests fail**

```bash
php artisan test tests/Feature/MultiParent/OresamsubParentSeederTest.php
```

Expected: failure because the configuration and seeder do not exist.

- [ ] **Step 3: Add configuration and documented environment overrides**

Create `config/parent_businesses.php`:

```php
return [
    'features' => [
        'ownership_reads' => env('PARENT_OWNERSHIP_READS', false),
        'normalized_pricing' => env('PARENT_NORMALIZED_PRICING', false),
        'provider_routing' => env('PARENT_PROVIDER_ROUTING', false),
    ],
    'oresamsub' => [
        'name' => 'OresamSub',
        'slug' => 'oresamsub',
        'provider' => [
            'name' => 'OresamSub',
            'slug' => 'oresamsub',
            'adapter' => 'oresamsub_legacy',
            'base_url' => env('ORESAMSUB_PROVIDER_BASE_URL', 'https://oresamsub.com/api/v1'),
        ],
        'admin' => [
            'name' => env('ORESAMSUB_PARENT_ADMIN_NAME', 'OresamSub Parent Admin'),
            'email' => env('ORESAMSUB_PARENT_ADMIN_EMAIL', 'parent-admin@oresamsub.local'),
            'password' => env('ORESAMSUB_PARENT_ADMIN_PASSWORD'),
        ],
    ],
];
```

Add all six environment keys to `.env.example`, with flags set to `false` and the password blank.

- [ ] **Step 4: Implement the idempotent seeder**

Use `firstOrCreate`/`updateOrCreate` by stable slugs and `(parent, position)`. Generate a 24-character random fallback password only when the admin email does not exist and configured password is blank:

```php
$temporaryPassword = filled($configuredPassword)
    ? $configuredPassword
    : Str::password(24);

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
```

If the email already belongs to a different parent, throw `RuntimeException` rather than reassigning it. Do not rotate an existing password on repeat runs.

- [ ] **Step 5: Run tests and format**

```bash
vendor/bin/pint config/parent_businesses.php database/seeders/OresamsubParentSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/MultiParent/OresamsubParentSeederTest.php
php artisan test tests/Feature/MultiParent/OresamsubParentSeederTest.php
```

- [ ] **Step 6: Commit Task 2**

```bash
git add .env.example config/parent_businesses.php database/seeders/OresamsubParentSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/MultiParent/OresamsubParentSeederTest.php
git commit -m "feat: seed oresamsub parent foundation"
```

---

### Task 3: Parent-owned plans, prices, routes, and transaction snapshots

**Files:**
- Create: `database/migrations/2026_08_08_100100_add_parent_ownership_and_plan_routing.php`
- Create: `app/Models/ProductPlanParentPrice.php`
- Create: `app/Models/ProductPlanProviderRoute.php`
- Create: `app/Models/MultiParentMigrationAudit.php`
- Modify: `app/Models/Affiliate.php`
- Modify: `app/Models/ProductPlan.php`
- Modify: `app/Models/Transaction.php`
- Test: `tests/Feature/MultiParent/ParentOwnershipSchemaTest.php`

**Interfaces:**
- Produces: nullable `affiliates.parent_business_id` and `affiliates.parent_reseller_level_id`.
- Produces: nullable transaction snapshots and product-plan ownership.
- Produces: `ProductPlan::parentPrices()` and `ProductPlan::providerRoutes()`.
- Consumes: Task 1 parent/provider tables.

- [ ] **Step 1: Write failing ownership tests**

Assert all new columns/tables exist. Create two parents and verify unique constraints prevent duplicate plan prices for the same level and duplicate route priorities for the same plan, while allowing the same provider plan ID on different parent connections.

Assert a transaction can retain parent, connection, route, provider-plan ID, provider reference, routing status, provider cost, parent cost, affiliate cost, customer price, parent profit, and affiliate profit snapshots.

- [ ] **Step 2: Verify the tests fail**

```bash
php artisan test tests/Feature/MultiParent/ParentOwnershipSchemaTest.php
```

- [ ] **Step 3: Implement the ownership/routing migration**

Add nullable foreign IDs to `affiliates`, `product_plans`, and `transactions`. Add these transaction snapshot columns as nullable:

```php
$table->foreignId('parent_business_id')->nullable()->index();
$table->foreignId('parent_provider_connection_id')->nullable()->index();
$table->foreignId('product_plan_provider_route_id')->nullable()->index();
$table->string('provider_plan_id_snapshot')->nullable();
$table->string('provider_reference')->nullable()->index();
$table->string('routing_status')->nullable()->index();
$table->decimal('provider_cost_snapshot', 14, 2)->nullable();
$table->decimal('parent_cost_snapshot', 14, 2)->nullable();
$table->decimal('affiliate_cost_snapshot', 14, 2)->nullable();
$table->decimal('customer_price_snapshot', 14, 2)->nullable();
$table->decimal('parent_profit_snapshot', 14, 2)->nullable();
$table->decimal('affiliate_profit_snapshot', 14, 2)->nullable();
```

Add unique support indexes `(id, parent_business_id)` to `product_plans` and `parent_provider_connections`. Create `product_plan_parent_prices` with `parent_business_id`, unique `(product_plan_id, parent_reseller_level_id)`, decimal price/max-profit fields, and composite foreign keys that require both the plan and reseller level to belong to that parent. Create `product_plan_provider_routes` with `parent_business_id`, unique `(product_plan_id, priority)`, provider plan ID, priority, active status, and composite foreign keys that require both the plan and parent connection to belong to that parent. Add a composite affiliate foreign key so `(parent_reseller_level_id, parent_business_id)` must reference a level owned by the affiliate's parent. Create `multi_parent_migration_audits` with `batch_uuid`, `action`, `entity_type`, `entity_id`, nullable `from_value`, `to_value`, JSON metadata, and timestamps.

Use named foreign keys and reverse the exact dependency order in `down()`. In MySQL, drop foreign keys before composite indexes and columns.

- [ ] **Step 4: Add relationships and casts**

Add:

```php
// Affiliate
public function parentBusiness(): BelongsTo
public function parentResellerLevel(): BelongsTo

// ProductPlan
public function parentBusiness(): BelongsTo
public function parentPrices(): HasMany
public function providerRoutes(): HasMany

// Transaction
public function parentBusiness(): BelongsTo
public function parentProviderConnection(): BelongsTo
public function productPlanProviderRoute(): BelongsTo
```

Cast every monetary snapshot and normalized price to `decimal:2`, priorities to integer, booleans to boolean, and audit metadata to array.

- [ ] **Step 5: Run tests and migration SQL validation**

```bash
vendor/bin/pint app/Models database/migrations/2026_08_08_100100_add_parent_ownership_and_plan_routing.php tests/Feature/MultiParent/ParentOwnershipSchemaTest.php
php artisan test tests/Feature/MultiParent/ParentOwnershipSchemaTest.php
php artisan migrate --pretend
```

Expected: tests pass and MySQL generates valid add/drop SQL without applying it.

- [ ] **Step 6: Commit Task 3**

```bash
git add app/Models/Affiliate.php app/Models/ProductPlan.php app/Models/Transaction.php app/Models/ProductPlanParentPrice.php app/Models/ProductPlanProviderRoute.php app/Models/MultiParentMigrationAudit.php database/migrations/2026_08_08_100100_add_parent_ownership_and_plan_routing.php tests/Feature/MultiParent/ParentOwnershipSchemaTest.php
git commit -m "feat: add parent owned plan pricing and routes"
```

---

### Task 4: Guarded OresamSub ownership, price, route, and transaction backfill

**Files:**
- Create: `app/Services/MultiParent/OresamsubFoundationBackfillService.php`
- Create: `app/Console/Commands/BackfillOresamsubFoundation.php`
- Test: `tests/Feature/MultiParent/OresamsubFoundationBackfillTest.php`

**Interfaces:**
- Produces: `OresamsubFoundationBackfillService::run(bool $dryRun): array<string,int>`.
- Produces: `php artisan multi-parent:backfill-oresamsub-foundation --dry-run|--commit`.
- Consumes: Tasks 1–3 models, seed data, and nullable columns.

- [ ] **Step 1: Write failing backfill tests**

Seed two affiliates, two product plans with `cost_price_1..6`, affiliate product plans, and historical transactions. Test that dry-run returns counts but leaves parent ownership, normalized prices, routes, transaction snapshots, and audit records absent.

Test committed execution assigns every legacy row to OresamSub, assigns every affiliate to Basic, creates exactly six price rows per product plan when all six legacy values are numeric, creates one primary route per product plan, and sets historical transaction parent/route snapshots without changing any IDs or wallet amounts.

Run the committed service twice and assert the second run creates or updates zero ownership, price, route, and audit records.

Create a second parent-owned affiliate and plan before rerunning; assert the service leaves those records unchanged. Add a partial-ownership fixture and assert the service throws with the conflicting record ID instead of claiming it.

- [ ] **Step 2: Verify tests fail**

```bash
php artisan test tests/Feature/MultiParent/OresamsubFoundationBackfillTest.php
```

- [ ] **Step 3: Implement command guards**

The command signature is:

```php
protected $signature = 'multi-parent:backfill-oresamsub-foundation
    {--dry-run : Execute and roll back all writes}
    {--commit : Persist the backfill}';
```

Return failure unless exactly one option is present. Print a table for parents, affiliates, plans, prices, routes, transactions, and audits. Catch and display exceptions without hiding their type from logs.

- [ ] **Step 4: Implement the transactional idempotent service**

Resolve OresamSub by slug and its provider/connection/Basic level by stable keys. Never assume numeric IDs. Wrap the complete operation in a database transaction; roll back when `$dryRun` is true.

Use `chunkById(250, ...)`. Only claim rows where all new ownership fields are null. Rows already fully owned by OresamSub are eligible for missing normalized child records. Rows fully owned by another parent are skipped. Partially owned rows throw before mutation.

For each plan, create normalized prices only for numeric legacy `cost_price_1..6`. Create the primary route using the OresamSub parent connection and this provider ID precedence:

```php
$providerPlanId = $plan->automation_product_plan_id
    ?: $plan->api_id
    ?: "legacy-{$plan->id}";
```

For historical transactions, derive the global product plan through `affiliate_product_plan_id → affiliate_product_plans.product_plan_id`, then link the OresamSub primary route. Store available cost/amount snapshots but do not alter `amount`, balances, wallet logs, status, or references.

- [ ] **Step 5: Run focused tests, formatting, and command discovery**

```bash
vendor/bin/pint app/Services/MultiParent/OresamsubFoundationBackfillService.php app/Console/Commands/BackfillOresamsubFoundation.php tests/Feature/MultiParent/OresamsubFoundationBackfillTest.php
php artisan test tests/Feature/MultiParent/OresamsubFoundationBackfillTest.php
php artisan list --raw | rg '^multi-parent:backfill-oresamsub-foundation'
```

- [ ] **Step 6: Commit Task 4**

```bash
git add app/Services/MultiParent/OresamsubFoundationBackfillService.php app/Console/Commands/BackfillOresamsubFoundation.php tests/Feature/MultiParent/OresamsubFoundationBackfillTest.php
git commit -m "feat: add guarded oresamsub foundation backfill"
```

---

### Task 5: Consolidate legacy customer levels 7–12 safely

**Files:**
- Modify: `app/Services/MultiParent/OresamsubFoundationBackfillService.php`
- Modify: `app/Http/Controllers/AffiliateUserPlanController.php`
- Modify: `app/Services/AffiliateCatalogGenerationService.php`
- Test: `tests/Feature/MultiParent/LegacyLevelConsolidationTest.php`
- Test: `tests/Feature/PlatformAdmin/AffiliateOperationsTest.php`

**Interfaces:**
- Produces: `consolidateLegacyCustomerLevels(ParentBusiness $parent, string $batchUuid): int` inside the backfill service.
- Preserves: the existing generate-user-plans controller/service response contract.

- [ ] **Step 1: Inspect the existing generation contract before adding its tests**

Run:

```bash
sed -n '1,220p' app/Services/AffiliateCatalogGenerationService.php
```

Preserve `AffiliateCatalogGenerationService::generateUserPlans(Affiliate $affiliate): array` and its existing response keys; do not create a duplicate generation path.

- [ ] **Step 2: Write failing consolidation and generation tests**

Create an affiliate with levels 1–12 and customers assigned to levels 6, 7, and 12. Run committed consolidation and assert:

- level-6 customer remains unchanged;
- level-7 and level-12 customers move to the same affiliate's level 6;
- one audit row is written per moved customer with old and new plan IDs;
- levels 7–12 are hidden rather than deleted;
- a second run moves nobody and writes no duplicate audit rows.

Test an affiliate with levels 1, 2, and 4; invoke the existing generate action and assert it creates only positions 3, 5, and 6. Assert a seventh active position is rejected.

- [ ] **Step 3: Verify tests fail**

```bash
php artisan test tests/Feature/MultiParent/LegacyLevelConsolidationTest.php
```

- [ ] **Step 4: Implement consolidation and six-level generation**

Within each affiliate and transaction:

1. Resolve or create level 6 using the existing affiliate generation convention.
2. Lock users whose current affiliate plan has `plan_level > 6`.
3. Update only those users to that affiliate's level-6 plan.
4. Insert an audit with action `customer_plan_consolidated_to_level_6` and a deterministic uniqueness key in metadata derived from user ID and old plan ID.
5. Set legacy plan visibility to `0`; do not delete it.

Update generation validation to accept only integer positions 1–6, enforce unique `(affiliate_id, plan_level)` after duplicates are audited/resolved, and make the button create only missing positions.

- [ ] **Step 5: Run level and platform-admin regression tests**

```bash
vendor/bin/pint app/Services/MultiParent/OresamsubFoundationBackfillService.php app/Http/Controllers/AffiliateUserPlanController.php tests/Feature/MultiParent/LegacyLevelConsolidationTest.php
php artisan test tests/Feature/MultiParent/LegacyLevelConsolidationTest.php tests/Feature/PlatformAdmin/AffiliateOperationsTest.php
```

- [ ] **Step 6: Commit Task 5**

```bash
git add app/Services/MultiParent/OresamsubFoundationBackfillService.php app/Services/AffiliateCatalogGenerationService.php app/Http/Controllers/AffiliateUserPlanController.php tests/Feature/MultiParent/LegacyLevelConsolidationTest.php tests/Feature/PlatformAdmin/AffiliateOperationsTest.php
git commit -m "feat: consolidate affiliate customer plans to six levels"
```

---

### Task 6: Local migration rehearsal and foundation acceptance gate

**Files:**
- Modify only if a verification defect is proven by a failing test.
- Create: `docs/runbooks/parent-foundation-local-rehearsal.md`

**Interfaces:**
- Consumes: all preceding tasks.
- Produces: repeatable local rehearsal commands and recorded expected invariants.

- [ ] **Step 1: Run the complete focused test suite**

```bash
php artisan test tests/Feature/MultiParent tests/Feature/PlatformAdmin
```

Expected: all tests pass. If any failure occurs, use `superpowers:systematic-debugging`, add a regression test, and fix only the proven defect.

- [ ] **Step 2: Validate formatting and migration SQL**

```bash
vendor/bin/pint --test
git diff --check
php artisan migrate --pretend
```

Expected: no formatting/diff errors and valid MySQL SQL.

- [ ] **Step 3: Rehearse on the local database in safe order**

Record a local database backup outside the repository, then run:

```bash
php artisan migrate
php artisan db:seed --class=OresamsubParentSeeder
php artisan multi-parent:backfill-oresamsub-foundation --dry-run
php artisan multi-parent:backfill-oresamsub-foundation --commit
php artisan multi-parent:backfill-oresamsub-foundation --commit
```

Expected: dry-run writes nothing; first commit reports the exact local ownership/pricing/route/consolidation counts; second commit reports zero mutations.

- [ ] **Step 4: Verify local invariants with read-only queries**

Document and run queries proving:

```text
one OresamSub parent
one OresamSub provider definition
one OresamSub parent provider connection
six OresamSub parent reseller levels
zero affiliates without parent/level ownership
zero product plans without OresamSub ownership and a primary route
zero historical transactions without parent ownership when their source plan exists
zero active affiliate customer levels above 6
zero cross-parent price or route relationships
all parent feature flags remain false
```

- [ ] **Step 5: Write the local rehearsal runbook**

Include backup prerequisite, migration, seed, dry-run, commit, second-run idempotency check, read-only invariant checks, and rollback instructions. Explicitly state that this is not a production runbook and does not authorize production deployment.

- [ ] **Step 6: Request code review and run final verification**

Use `superpowers:requesting-code-review` against the foundation commit range. Address Critical and Important findings with regression tests. Then rerun:

```bash
php artisan test tests/Feature/MultiParent tests/Feature/PlatformAdmin
vendor/bin/pint --test
git diff --check
```

- [ ] **Step 7: Commit the rehearsal runbook or verified fixes**

```bash
git add docs/runbooks/parent-foundation-local-rehearsal.md
git commit -m "docs: add parent foundation local rehearsal"
```

---

## Follow-up implementation plans

These are intentionally excluded from this foundation and require separate plans after local acceptance:

1. Parent-scoped pricing read service and legacy-equivalence comparison.
2. Primary provider purchase routing for data, airtime, cable, and electricity.
3. Transaction state machine, requery, and idempotent refund conversion.
4. Hybrid parent/affiliate funding connections and webhook routing.
5. Parent-admin authentication, dashboard, policies, and audit UI.
6. Parent-scoped reporting, remaining operational models, and commission runtime conversion.
7. Modern-classic affiliate landing-page simplification.
