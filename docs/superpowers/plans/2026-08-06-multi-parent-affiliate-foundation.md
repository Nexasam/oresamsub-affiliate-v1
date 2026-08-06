# Multi-Parent Affiliate Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow independent VTU API owners to exist as parent businesses, own provider-scoped catalogues, purchase affiliate licences, and route each affiliate's purchases through the correct parent connection without treating OresamSub as the universal parent.

**Architecture:** Keep one Laravel application and one database. Add a parent-business boundary above affiliates, represent each parent's external API as a provider connection backed by an allow-listed adapter class, normalize parent plans into shared provider-scoped tables, and reuse `affiliate_product_plans` as the affiliate's sellable-plan layer. Migrate OresamSub into the new model first and preserve legacy IDs until the new purchase path has passed parity tests.

**Tech Stack:** PHP 8.3, Laravel 13, Eloquent, Pest 4, SQLite for automated tests, MySQL-compatible Laravel migrations for shared hosting, Blade/Inertia/Alpine patterns already present in the platform-admin area.

## Global Constraints

- One multi-tenant application and database; never create a Laravel installation or physical plan table per parent.
- One parent and one primary provider connection per affiliate in this release; the schema must permit a parent to own several provider connections later.
- OresamSub is seeded and backfilled as the first parent/provider connection; existing affiliate and transaction IDs remain valid.
- Provider credentials use Laravel encrypted casts and are never returned by controllers, resources, logs, exceptions, or exports.
- Money uses `decimal(14, 2)` and model decimal casts; booleans use boolean columns and casts; foreign keys use `foreignId()` and are indexed.
- External HTTP calls must not occur while a user-wallet row is locked inside a database transaction.
- Catalogue imports use atomic upserts keyed by provider connection and upstream code.
- Every tenant query is explicitly scoped; session-only global scoping is not accepted as the security boundary.
- Provider-specific integration work for the first external parent gets its own follow-up plan after that parent's API documentation and test credentials are supplied.

---

## Schema map

```text
parent_businesses
├── provider_connections
├── affiliates
│   ├── affiliate_licenses
│   ├── affiliate_product_plans ── provider_plans
│   └── transactions
└── provider_plan_categories ── canonical_plan_categories
    └── provider_plans
        └── provider_transaction_attempts

provider_adapters (allow-listed adapter metadata; no executable PHP from DB)
```

### New tables

| Table | Required columns and constraints |
|---|---|
| `parent_businesses` | `id`; unique `slug`; `name`; nullable contact fields; `status` (`active`, `suspended`); timestamps |
| `provider_adapters` | `id`; unique `key`; `name`; allow-listed `driver`; JSON `capabilities`; boolean `is_active`; timestamps |
| `provider_connections` | `id`; FK/index `parent_business_id`; FK/index `provider_adapter_id`; `name`; `base_url`; encrypted JSON `credentials`; JSON `settings`; `status`; nullable `last_tested_at`; unique (`parent_business_id`, `name`) |
| `affiliate_licenses` | `id`; unique FK `affiliate_id`; FK/index `parent_business_id`; `status`; decimal `purchase_amount`; nullable `activated_at`, `expires_at`, `suspended_at`; timestamps |
| `canonical_plan_categories` | `id`; unique `slug`; FK/index `product_id`; nullable FK/index `network_id`; `name`; nullable `data_type`; boolean `is_active`; timestamps |
| `provider_plan_categories` | `id`; FK/index `provider_connection_id`; nullable FK/index `canonical_plan_category_id`; `upstream_code`; `name`; `mapping_status`; JSON `raw_metadata`; unique (`provider_connection_id`, `upstream_code`) |
| `provider_plans` | `id`; FK/index `provider_connection_id`; FK/index `provider_plan_category_id`; `upstream_code`; `name`; decimal `cost_price`; nullable unsigned `data_size_mb`, `validity_days`; `status`; JSON `raw_metadata`; nullable `last_synced_at`; unique (`provider_connection_id`, `upstream_code`) |
| `provider_transaction_attempts` | `id`; FK/index `transaction_id`; FK/index `provider_connection_id`; FK/index `provider_plan_id`; `attempt_number`; `status`; nullable `upstream_reference`, `http_status`; JSON `sanitized_request`, `sanitized_response`; timestamps; unique (`transaction_id`, `attempt_number`) |

### Existing-table changes

- `affiliates`: add nullable indexed FKs `parent_business_id` and `provider_connection_id`; make legacy `parent_key` nullable and retain it during the compatibility period.
- `affiliate_product_plans`: add nullable indexed FK `provider_plan_id`; add unique (`affiliate_id`, `provider_plan_id`); retain `product_plan_id` during compatibility period.
- `transactions`: add nullable indexed FKs `parent_business_id`, `provider_connection_id`, `provider_plan_id`; add nullable unique `idempotency_key`; nullable `upstream_reference`; nullable `provider_status`; add composite indexes (`affiliate_id`, `status`, `created_at`) and (`provider_connection_id`, `provider_status`, `created_at`).

---

### Task 1: Parent, adapter, connection, and licence schema

**Files:**
- Create: `database/migrations/2026_08_06_000001_create_parent_provider_foundation_tables.php`
- Create: `app/Models/ParentBusiness.php`
- Create: `app/Models/ProviderAdapter.php`
- Create: `app/Models/ProviderConnection.php`
- Create: `app/Models/AffiliateLicense.php`
- Modify: `app/Models/Affiliate.php`
- Test: `tests/Feature/MultiParent/ParentProviderSchemaTest.php`

**Interfaces:**
- Produces: `ParentBusiness::providerConnections()`, `ParentBusiness::affiliates()`, `ProviderConnection::parentBusiness()`, `Affiliate::parentBusiness()`, `Affiliate::providerConnection()`, and `Affiliate::license()`.
- Produces: encrypted `ProviderConnection::$casts['credentials'] = 'encrypted:array'` and array cast for `settings`.

- [ ] **Step 1: Write the failing schema and encryption tests**

```php
it('links an affiliate and licence to one parent and connection', function () {
    $parent = ParentBusiness::factory()->create();
    $connection = ProviderConnection::factory()->for($parent)->create();
    $affiliate = Affiliate::factory()->create([
        'parent_business_id' => $parent->id,
        'provider_connection_id' => $connection->id,
    ]);
    AffiliateLicense::factory()->for($affiliate)->for($parent)->create();

    expect($affiliate->parentBusiness->is($parent))->toBeTrue()
        ->and($affiliate->providerConnection->is($connection))->toBeTrue()
        ->and($affiliate->license->parentBusiness->is($parent))->toBeTrue();
});

it('encrypts provider credentials at rest', function () {
    $connection = ProviderConnection::factory()->create([
        'credentials' => ['api_key' => 'secret-key'],
    ]);

    expect(DB::table('provider_connections')->where('id', $connection->id)->value('credentials'))
        ->not->toContain('secret-key')
        ->and($connection->fresh()->credentials['api_key'])->toBe('secret-key');
});
```

- [ ] **Step 2: Run the focused test and confirm it fails because the tables/models do not exist**

Run: `php artisan test tests/Feature/MultiParent/ParentProviderSchemaTest.php`

Expected: FAIL with missing `parent_businesses` or model-class errors.

- [ ] **Step 3: Add the migration, relationships, casts, factories, foreign-key indexes, and uniqueness rules exactly as specified in the schema map**

Use string status columns plus application enums initially for SQLite/MySQL portability. Use `restrictOnDelete()` for a parent with affiliates and `cascadeOnDelete()` only for connection-owned catalogue records that have no completed transaction history.

- [ ] **Step 4: Run the focused test and the existing platform-admin affiliate tests**

Run: `php artisan test tests/Feature/MultiParent/ParentProviderSchemaTest.php tests/Feature/PlatformAdmin/AffiliateOperationsTest.php`

Expected: PASS.

- [ ] **Step 5: Commit the foundation schema**

```bash
git add app/Models database/factories database/migrations tests/Feature/MultiParent/ParentProviderSchemaTest.php
git commit -m "feat: add parent provider foundation schema"
```

### Task 2: Canonical and provider-scoped catalogue schema

**Files:**
- Create: `database/migrations/2026_08_06_000002_create_provider_catalog_tables.php`
- Create: `app/Models/CanonicalPlanCategory.php`
- Create: `app/Models/ProviderPlanCategory.php`
- Create: `app/Models/ProviderPlan.php`
- Modify: `app/Models/AffiliateProductPlan.php`
- Test: `tests/Feature/MultiParent/ProviderCatalogSchemaTest.php`

**Interfaces:**
- Produces: `ProviderPlan::connection()`, `ProviderPlan::category()`, `ProviderPlanCategory::canonicalCategory()`, and `AffiliateProductPlan::providerPlan()`.
- Enforces: unique provider codes per connection and one affiliate offering per provider plan.

- [ ] **Step 1: Write failing tests for provider-code isolation and affiliate-offering uniqueness**

```php
it('allows the same upstream plan code on different provider connections', function () {
    $first = ProviderConnection::factory()->create();
    $second = ProviderConnection::factory()->create();

    ProviderPlan::factory()->for($first, 'connection')->create(['upstream_code' => 'MTN-1GB']);
    ProviderPlan::factory()->for($second, 'connection')->create(['upstream_code' => 'MTN-1GB']);

    expect(ProviderPlan::where('upstream_code', 'MTN-1GB')->count())->toBe(2);
});

it('prevents an affiliate from inheriting the same provider plan twice', function () {
    $offering = AffiliateProductPlan::factory()->create();

    AffiliateProductPlan::factory()->create([
        'affiliate_id' => $offering->affiliate_id,
        'provider_plan_id' => $offering->provider_plan_id,
    ]);
})->throws(QueryException::class);
```

- [ ] **Step 2: Run the focused test and confirm missing-table failures**

Run: `php artisan test tests/Feature/MultiParent/ProviderCatalogSchemaTest.php`

- [ ] **Step 3: Implement the catalogue migrations and models**

Store `cost_price` as `decimal(14, 2)`, `data_size_mb` and `validity_days` as nullable unsigned integers, visibility/status as real booleans or constrained strings, and raw provider fields in JSON. Do not copy the current twelve cost and commission columns into `provider_plans`.

- [ ] **Step 4: Run schema tests and migrations on both SQLite test DB and configured local DB**

Run: `php artisan test tests/Feature/MultiParent/ProviderCatalogSchemaTest.php`

Run: `php artisan migrate --pretend`

Expected: tests PASS; generated SQL contains all FKs, unique keys, and composite indexes.

- [ ] **Step 5: Commit the catalogue schema**

```bash
git add app/Models database/factories database/migrations tests/Feature/MultiParent/ProviderCatalogSchemaTest.php
git commit -m "feat: add provider scoped catalog schema"
```

### Task 3: OresamSub backfill and legacy compatibility bridge

**Files:**
- Create: `app/Console/Commands/BackfillOresamsubParent.php`
- Create: `app/Services/MultiParent/OresamsubBackfillService.php`
- Create: `app/Services/MultiParent/DTO/BackfillResult.php`
- Create: `tests/Feature/MultiParent/OresamsubBackfillTest.php`
- Modify: `routes/console.php`

**Interfaces:**
- Produces: `OresamsubBackfillService::run(): BackfillResult` with counts for parents, connections, categories, provider plans, affiliate links, offerings, and transactions.
- Command: `php artisan multi-parent:backfill-oresamsub --dry-run` and `--commit`.

- [ ] **Step 1: Write a failing idempotent backfill test**

```php
it('backfills OresamSub twice without duplicates', function () {
    seedLegacyOresamsubCatalog();

    app(OresamsubBackfillService::class)->run();
    app(OresamsubBackfillService::class)->run();

    expect(ParentBusiness::where('slug', 'oresamsub')->count())->toBe(1)
        ->and(ProviderConnection::count())->toBe(1)
        ->and(ProviderPlan::count())->toBe(ProductPlan::count())
        ->and(Affiliate::whereNull('parent_business_id')->count())->toBe(0);
});
```

- [ ] **Step 2: Run the test and confirm the service is missing**

Run: `php artisan test tests/Feature/MultiParent/OresamsubBackfillTest.php`

- [ ] **Step 3: Implement chunked, atomic upserts without deleting legacy records**

Map each legacy `product_plan_categories` row into a canonical category plus OresamSub provider category. Map each legacy `product_plans` row into `provider_plans`, populate `affiliate_product_plans.provider_plan_id`, attach every existing affiliate to OresamSub, and populate new transaction FKs from its affiliate/offering. Use chunks of 250 and log counts, not secrets or raw provider payloads.

- [ ] **Step 4: Run dry-run, test, and committed backfill against a disposable database copy**

Run: `php artisan multi-parent:backfill-oresamsub --dry-run`

Run: `php artisan test tests/Feature/MultiParent/OresamsubBackfillTest.php`

Expected: dry-run reports counts without writes; test proves repeatability.

- [ ] **Step 5: Commit the compatibility bridge**

```bash
git add app/Console app/Services/MultiParent routes/console.php tests/Feature/MultiParent/OresamsubBackfillTest.php
git commit -m "feat: backfill oresamsub into multi parent catalog"
```

### Task 4: Explicit tenant context and isolation

**Files:**
- Create: `app/Tenancy/AffiliateContext.php`
- Create: `app/Http/Middleware/ResolveAffiliateContext.php`
- Create: `app/Models/Scopes/AffiliateScope.php`
- Modify: `app/Models/AffiliateScopedModel.php`
- Modify: `bootstrap/app.php`
- Replace: `app/Http/Middleware/SetAffiliatenewest.php` usage where still active
- Test: `tests/Feature/MultiParent/TenantIsolationTest.php`

**Interfaces:**
- Produces: `AffiliateContext::set(Affiliate $affiliate): void`, `affiliate(): Affiliate`, `affiliateId(): int`, `parentBusinessId(): int`, and `clear(): void`.
- Produces: request attribute `affiliate` and container-scoped `AffiliateContext`.

- [ ] **Step 1: Write failing cross-parent isolation and unknown-domain tests**

```php
it('never falls back to the default affiliate for an unknown production host', function () {
    $this->get('https://unknown-vtu.example/dashboard')->assertNotFound();
});

it('scopes affiliate records through explicit context', function () {
    [$affiliateA, $affiliateB] = affiliatesForDifferentParents();
    Transaction::factory()->for($affiliateA)->create();
    Transaction::factory()->for($affiliateB)->create();

    app(AffiliateContext::class)->set($affiliateA);
    expect(Transaction::query()->pluck('affiliate_id')->unique()->all())
        ->toBe([$affiliateA->id]);
});
```

- [ ] **Step 2: Run the tests and capture the current unsafe default-affiliate behaviour**

Run: `php artisan test tests/Feature/MultiParent/TenantIsolationTest.php`

- [ ] **Step 3: Implement host normalization and explicit context**

Resolve `domain_url` and `website_url` after stripping scheme, `www`, port, path, and trailing slash. Permit a configured development fallback only in local/testing environments. Throw a domain-not-found response in production. Replace direct `session('affiliate')` access inside the global scope with `AffiliateContext`; console code must set context explicitly or use `withoutGlobalScope()` with an explicit `affiliate_id` predicate.

- [ ] **Step 4: Run all auth, affiliate, and tenant tests**

Run: `php artisan test tests/Feature/Auth tests/Feature/PlatformAdmin tests/Feature/MultiParent/TenantIsolationTest.php`

Expected: PASS with no cross-parent rows returned.

- [ ] **Step 5: Commit tenant isolation**

```bash
git add app/Tenancy app/Http/Middleware app/Models/Scopes app/Models/AffiliateScopedModel.php bootstrap/app.php tests/Feature/MultiParent/TenantIsolationTest.php
git commit -m "security: enforce explicit affiliate tenant context"
```

### Task 5: Provider adapter contract and normalized DTOs

**Files:**
- Create: `app/Services/Providers/Contracts/ProviderAdapter.php`
- Create: `app/Services/Providers/DTO/PurchaseRequest.php`
- Create: `app/Services/Providers/DTO/PurchaseResult.php`
- Create: `app/Services/Providers/DTO/RequeryResult.php`
- Create: `app/Services/Providers/DTO/ConnectionTestResult.php`
- Create: `app/Services/Providers/Exceptions/UnsupportedProviderAdapter.php`
- Create: `app/Services/Providers/ProviderAdapterRegistry.php`
- Create: `app/Services/Providers/Adapters/FakeProviderAdapter.php`
- Create: `app/Providers/ProviderIntegrationServiceProvider.php`
- Modify: `bootstrap/providers.php`
- Test: `tests/Unit/Providers/ProviderAdapterRegistryTest.php`

**Interfaces:**
- Consumes: `ProviderConnection` and `ProviderPlan` from Tasks 1–2.
- Produces:

```php
interface ProviderAdapter
{
    public function testConnection(ProviderConnection $connection): ConnectionTestResult;
    public function purchase(PurchaseRequest $request): PurchaseResult;
    public function requery(ProviderConnection $connection, string $upstreamReference): RequeryResult;
}
```

- [ ] **Step 1: Write failing registry and DTO normalization tests**

```php
it('resolves only allow-listed provider drivers', function () {
    $adapter = ProviderAdapterModel::factory()->create(['driver' => 'fake']);
    $connection = ProviderConnection::factory()->for($adapter, 'adapter')->create();

    expect(app(ProviderAdapterRegistry::class)->for($connection))
        ->toBeInstanceOf(FakeProviderAdapter::class);
});

it('rejects an arbitrary class name stored in the database', function () {
    $adapter = ProviderAdapterModel::factory()->create([
        'driver' => 'App\\Dangerous\\RuntimeClass',
    ]);
    $connection = ProviderConnection::factory()->for($adapter, 'adapter')->create();

    app(ProviderAdapterRegistry::class)->for($connection);
})->throws(UnsupportedProviderAdapter::class);
```

- [ ] **Step 2: Run the focused unit tests and confirm missing interfaces**

Run: `php artisan test tests/Unit/Providers/ProviderAdapterRegistryTest.php`

- [ ] **Step 3: Implement immutable DTOs and an application-configured driver map**

Normalize statuses to `pending`, `successful`, or `failed`. `PurchaseResult` carries `upstreamReference`, customer-safe message, admin-safe message, provider status, HTTP status, and sanitized metadata. The registry maps short keys such as `fake` and `oresamsub_legacy` to known classes; never instantiate a DB-provided class string.

- [ ] **Step 4: Run unit tests and static syntax checks**

Run: `php artisan test tests/Unit/Providers`

Run: `vendor/bin/pint --test app/Services/Providers app/Providers/ProviderIntegrationServiceProvider.php`

- [ ] **Step 5: Commit the adapter boundary**

```bash
git add app/Services/Providers app/Providers bootstrap/providers.php tests/Unit/Providers
git commit -m "feat: define provider adapter boundary"
```

### Task 6: OresamSub legacy adapter

**Files:**
- Create: `app/Services/Providers/Adapters/OresamsubLegacyAdapter.php`
- Modify: `app/Services/Automation/AutomationLogic.php`
- Test: `tests/Feature/MultiParent/OresamsubAdapterTest.php`

**Interfaces:**
- Implements: `ProviderAdapter` from Task 5.
- Consumes existing automation services behind one compatibility wrapper; controllers must no longer select provider classes with slug conditionals.

- [ ] **Step 1: Write failing data, airtime, cable, electricity, and requery contract tests with `Http::fake()`**

```php
it('normalizes a successful legacy data purchase', function () {
    Http::fake(['oresamsub.test/*' => Http::response([
        'status' => 1,
        'reference' => 'UP-100',
        'message' => 'Delivered',
    ])]);

    $result = app(OresamsubLegacyAdapter::class)->purchase(fakePurchaseRequest('data'));

    expect($result->status)->toBe('successful')
        ->and($result->upstreamReference)->toBe('UP-100');
});
```

- [ ] **Step 2: Run the adapter test and confirm it fails**

Run: `php artisan test tests/Feature/MultiParent/OresamsubAdapterTest.php`

- [ ] **Step 3: Wrap existing automation behaviour and remove OresamSub success masking from the new path**

Do not preserve the current rule that converts provider failures into customer success. A genuinely uncertain result becomes `pending`; a confirmed failure remains `failed`. Keep legacy callers operational until Task 10 cutover.

- [ ] **Step 4: Run adapter and current transaction tests**

Run: `php artisan test tests/Feature/MultiParent/OresamsubAdapterTest.php tests/Feature/PlatformAdmin`

- [ ] **Step 5: Commit the OresamSub adapter**

```bash
git add app/Services/Providers/Adapters/OresamsubLegacyAdapter.php app/Services/Automation/AutomationLogic.php tests/Feature/MultiParent/OresamsubAdapterTest.php
git commit -m "refactor: route oresamsub through provider adapter"
```

### Task 7: Spreadsheet catalogue import and mapping review

**Files:**
- Create: `app/Services/Providers/Catalog/ProviderPlanImportService.php`
- Create: `app/Services/Providers/Catalog/DTO/ImportResult.php`
- Create: `app/Http/Requests/PlatformAdmin/ImportProviderPlansRequest.php`
- Create: `app/Http/Controllers/PlatformAdmin/ProviderCatalogImportController.php`
- Create: `resources/views/platform-admin/providers/import.blade.php`
- Modify: `routes/platform-admin.php`
- Test: `tests/Feature/MultiParent/ProviderPlanImportTest.php`
- Create: `docs/product/provider-plan-import-template.csv`

**Interfaces:**
- Produces: `ProviderPlanImportService::import(ProviderConnection $connection, UploadedFile $file): ImportResult`.
- Template columns: `provider_plan_code`, `global_category_slug`, `plan_name`, `data_size_mb`, `validity_days`, `provider_cost`, `service_type`, `network`, `status`.

- [ ] **Step 1: Write failing tests for create, update, duplicate, unknown-category, and wrong-parent rows**

```php
it('upserts provider plans and queues unknown categories', function () {
    $result = app(ProviderPlanImportService::class)->import(
        $connection,
        csvUpload([
            ['MTN-1G', 'mtn-sme', 'MTN 1GB', 1024, 30, 600, 'data', 'mtn', 'active'],
            ['NEW-1', 'unknown-type', 'New Plan', null, null, 100, 'data', 'mtn', 'active'],
        ])
    );

    expect($result->created)->toBe(2)
        ->and($result->needsMapping)->toBe(1);
});
```

- [ ] **Step 2: Run the import tests and confirm missing-service failures**

Run: `php artisan test tests/Feature/MultiParent/ProviderPlanImportTest.php`

- [ ] **Step 3: Implement validation, transaction-scoped upsert, and review UI**

Reject negative prices, duplicate codes inside one file, unknown service types, and category/network conflicts. Upsert on (`provider_connection_id`, `upstream_code`). Do not publish `mapping_status = pending_review` plans to affiliates.

- [ ] **Step 4: Run import tests and upload the documented sample through the platform-admin route**

Run: `php artisan test tests/Feature/MultiParent/ProviderPlanImportTest.php`

Expected: valid rows import; invalid rows return row-numbered errors; secrets never appear in responses.

- [ ] **Step 5: Commit catalogue import**

```bash
git add app/Services/Providers/Catalog app/Http/Requests/PlatformAdmin app/Http/Controllers/PlatformAdmin/ProviderCatalogImportController.php resources/views/platform-admin/providers routes/platform-admin.php tests/Feature/MultiParent/ProviderPlanImportTest.php docs/product/provider-plan-import-template.csv
git commit -m "feat: import provider scoped plans"
```

### Task 8: Parent and affiliate licence administration

**Files:**
- Create: `app/Http/Controllers/PlatformAdmin/ParentBusinessController.php`
- Create: `app/Http/Controllers/PlatformAdmin/ProviderConnectionController.php`
- Create: `app/Http/Controllers/PlatformAdmin/AffiliateLicenseController.php`
- Create: `app/Http/Resources/ProviderConnectionResource.php`
- Create: `resources/views/platform-admin/parents/index.blade.php`
- Create: `resources/views/platform-admin/parents/show.blade.php`
- Modify: `routes/platform-admin.php`
- Test: `tests/Feature/MultiParent/ParentAdministrationTest.php`

**Interfaces:**
- Produces CRUD routes under `platform-admin.parents.*`.
- `ProviderConnectionResource` returns credential presence/masking only, never decrypted values.

- [ ] **Step 1: Write failing authorization, credential-redaction, attachment, and suspension tests**

```php
it('never serializes provider credentials', function () {
    $connection = ProviderConnection::factory()->create([
        'credentials' => ['api_key' => 'secret-key'],
    ]);

    $this->actingAs(platformAdmin(), 'platform_admin')
        ->getJson(route('platform-admin.parents.connections.show', [$connection->parent_business_id, $connection]))
        ->assertOk()
        ->assertJsonMissing(['api_key' => 'secret-key']);
});
```

- [ ] **Step 2: Run the tests and confirm missing routes/controllers**

Run: `php artisan test tests/Feature/MultiParent/ParentAdministrationTest.php`

- [ ] **Step 3: Implement parent onboarding, connection test, affiliate attachment, licence activation and suspension**

Validate that an affiliate's `parent_business_id` matches its connection and licence. Suspending a licence prevents new purchases but preserves login, reporting, and historical transaction access.

- [ ] **Step 4: Run parent-admin and existing platform-admin test suites**

Run: `php artisan test tests/Feature/MultiParent/ParentAdministrationTest.php tests/Feature/PlatformAdmin`

- [ ] **Step 5: Commit parent administration**

```bash
git add app/Http/Controllers/PlatformAdmin app/Http/Resources resources/views/platform-admin/parents routes/platform-admin.php tests/Feature/MultiParent/ParentAdministrationTest.php
git commit -m "feat: manage parents connections and affiliate licences"
```

### Task 9: Affiliate catalogue inheritance and pricing

**Files:**
- Create: `app/Services/MultiParent/AffiliateCatalogService.php`
- Create: `app/Services/MultiParent/DTO/CatalogSyncResult.php`
- Modify: `app/Http/Controllers/PlatformAdmin/AffiliateOperationsController.php`
- Modify: `app/Http/Services/DataPlansService.php`
- Test: `tests/Feature/MultiParent/AffiliateCatalogInheritanceTest.php`

**Interfaces:**
- Produces: `AffiliateCatalogService::sync(Affiliate $affiliate): CatalogSyncResult` and `availablePlans(Affiliate $affiliate): Builder`.
- Selling price rule: explicit affiliate selling price, else provider cost plus affiliate markup, with a floor at provider cost.

- [ ] **Step 1: Write failing inheritance and cross-parent exclusion tests**

```php
it('inherits only active mapped plans from the affiliates parent connection', function () {
    [$affiliateA, $affiliateB] = affiliatesForDifferentParents();
    $planA = ProviderPlan::factory()->for($affiliateA->providerConnection, 'connection')->create();
    ProviderPlan::factory()->for($affiliateB->providerConnection, 'connection')->create();

    app(AffiliateCatalogService::class)->sync($affiliateA);

    expect(AffiliateProductPlan::withoutGlobalScopes()
        ->where('affiliate_id', $affiliateA->id)->pluck('provider_plan_id')->all())
        ->toBe([$planA->id]);
});
```

- [ ] **Step 2: Run the focused test and confirm legacy catalogue leakage**

Run: `php artisan test tests/Feature/MultiParent/AffiliateCatalogInheritanceTest.php`

- [ ] **Step 3: Implement atomic offering upserts and pricing rules**

Do not delete offerings with historical transactions. Mark offerings unavailable when upstream plans disappear or licences are suspended. Always query by the affiliate's assigned `provider_connection_id`.

- [ ] **Step 4: Run catalogue and platform-admin tests**

Run: `php artisan test tests/Feature/MultiParent/AffiliateCatalogInheritanceTest.php tests/Feature/PlatformAdmin/AffiliateOperationsTest.php`

- [ ] **Step 5: Commit catalogue inheritance**

```bash
git add app/Services/MultiParent/AffiliateCatalogService.php app/Http/Controllers/PlatformAdmin/AffiliateOperationsController.php app/Http/Services/DataPlansService.php tests/Feature/MultiParent/AffiliateCatalogInheritanceTest.php
git commit -m "feat: inherit provider plans into affiliate catalogs"
```

### Task 10: Idempotent purchase routing and provider attempts

**Files:**
- Create: `database/migrations/2026_08_06_000003_add_provider_routing_to_transactions.php`
- Create: `app/Models/ProviderTransactionAttempt.php`
- Create: `app/Services/Transaction/ProviderPurchaseService.php`
- Create: `app/Services/Transaction/DTO/PurchaseInput.php`
- Modify: `app/Models/Transaction.php`
- Modify: `app/Http/Controllers/DataController.php`
- Modify: `app/Http/Controllers/AirtimeController.php`
- Modify: `app/Http/Controllers/CableSubscriptionController.php`
- Modify: `app/Http/Controllers/ElectricitySubscriptionController.php`
- Test: `tests/Feature/MultiParent/ProviderPurchaseRoutingTest.php`

**Interfaces:**
- Produces: `ProviderPurchaseService::purchase(Affiliate $affiliate, User $user, AffiliateProductPlan $offering, PurchaseInput $input, string $idempotencyKey): Transaction`.
- Consumes: `ProviderAdapterRegistry` and normalized DTOs from Task 5.

- [ ] **Step 1: Write failing correct-parent, duplicate-key, wallet, and pending-result tests**

```php
it('sends an affiliate purchase only to its assigned parent connection', function () {
    $affiliate = affiliateWithFundedUserAndOffering();
    FakeProviderAdapter::respondWith(PurchaseResult::successful('UP-1'));

    $transaction = app(ProviderPurchaseService::class)->purchase(
        $affiliate,
        $affiliate->users->first(),
        $affiliate->offering,
        PurchaseInput::data('08012345678'),
        'idem-100'
    );

    expect($transaction->provider_connection_id)->toBe($affiliate->provider_connection_id)
        ->and($transaction->provider_plan_id)->toBe($affiliate->offering->provider_plan_id)
        ->and($transaction->status)->toBe('1');
});

it('returns the original transaction for a repeated idempotency key', function () {
    $first = performPurchase('idem-duplicate');
    $second = performPurchase('idem-duplicate');

    expect($second->is($first))->toBeTrue()
        ->and(ProviderTransactionAttempt::count())->toBe(1);
});
```

- [ ] **Step 2: Run the focused test and confirm routing/idempotency failures**

Run: `php artisan test tests/Feature/MultiParent/ProviderPurchaseRoutingTest.php`

- [ ] **Step 3: Implement a three-stage purchase flow**

Stage A validates the tenant, active licence, offering ownership, price and wallet balance. Stage B reserves/deducts funds and creates the pending transaction in a short locked DB transaction. Stage C calls the external adapter after committing, records a sanitized attempt, then performs a short conditional status/refund update. Never hold the wallet lock during HTTP.

- [ ] **Step 4: Route all four service controllers through the service behind a feature flag**

Add `config('multi_parent.purchase_routing_enabled')`. Enable it in tests, keep production default false until Task 12 parity checks pass, and retain the legacy path for rollback.

- [ ] **Step 5: Run purchase tests and the full suite**

Run: `php artisan test tests/Feature/MultiParent/ProviderPurchaseRoutingTest.php`

Run: `php artisan test`

Expected: PASS; two simultaneous/repeated submissions create one upstream attempt and one wallet deduction.

- [ ] **Step 6: Commit purchase routing**

```bash
git add config app/Models/ProviderTransactionAttempt.php app/Services/Transaction/ProviderPurchaseService.php app/Http/Controllers database/migrations tests/Feature/MultiParent/ProviderPurchaseRoutingTest.php
git commit -m "feat: route purchases through parent provider connections"
```

### Task 11: Pending transaction reconciliation

**Files:**
- Create: `app/Services/Transaction/ProviderReconciliationService.php`
- Create: `app/Services/Transaction/DTO/ReconciliationResult.php`
- Create: `app/Console/Commands/ReconcileProviderTransactions.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/MultiParent/ProviderReconciliationTest.php`

**Interfaces:**
- Produces: `ProviderReconciliationService::reconcile(Transaction $transaction): ReconciliationResult`.
- Command: `php artisan providers:reconcile --limit=100`.

- [ ] **Step 1: Write failing success, failure/refund, still-pending, and wrong-provider tests**

```php
it('requeries through the original provider connection and refunds one confirmed failure', function () {
    $transaction = pendingProviderTransaction();
    FakeProviderAdapter::requeryWith(RequeryResult::failed('Not delivered'));

    app(ProviderReconciliationService::class)->reconcile($transaction);
    app(ProviderReconciliationService::class)->reconcile($transaction->fresh());

    expect($transaction->fresh()->status)->toBe('2')
        ->and(walletRefundLogsFor($transaction))->toHaveCount(1);
});
```

- [ ] **Step 2: Run the focused test and confirm the reconciliation service is missing**

Run: `php artisan test tests/Feature/MultiParent/ProviderReconciliationTest.php`

- [ ] **Step 3: Implement bounded, context-explicit reconciliation**

Select pending rows with the composite provider/status/date index, process at most the requested limit, resolve the original connection, and use conditional updates so repeated cron runs cannot double-refund. Schedule the command at the highest frequency supported by shared-hosting cron.

- [ ] **Step 4: Run reconciliation and concurrency-focused tests**

Run: `php artisan test tests/Feature/MultiParent/ProviderReconciliationTest.php tests/Feature/MultiParent/ProviderPurchaseRoutingTest.php`

- [ ] **Step 5: Commit reconciliation**

```bash
git add app/Services/Transaction/ProviderReconciliationService.php app/Console/Commands/ReconcileProviderTransactions.php routes/console.php tests/Feature/MultiParent/ProviderReconciliationTest.php
git commit -m "feat: reconcile parent provider transactions"
```

### Task 12: Cutover audit, pilot runbook, and release gate

**Files:**
- Create: `tests/Feature/MultiParent/MultiParentEndToEndTest.php`
- Create: `docs/operations/multi-parent-pilot-runbook.md`
- Create: `docs/operations/multi-parent-rollback.md`
- Modify: `config/multi_parent.php`

**Interfaces:**
- Consumes every interface produced by Tasks 1–11.
- Produces a documented go/no-go checklist and rollback procedure.

- [ ] **Step 1: Write the end-to-end test before enabling production routing**

```php
it('isolates two parents from catalogue import through purchase and reporting', function () {
    [$parentA, $affiliateA] = provisionParentAndAffiliate('parent-a');
    [$parentB, $affiliateB] = provisionParentAndAffiliate('parent-b');

    importProviderCatalog($parentA, [['A-1', 'mtn-sme', 'MTN 1GB', 600]]);
    importProviderCatalog($parentB, [['B-1', 'mtn-sme', 'MTN 1GB', 610]]);
    syncAndPurchase($affiliateA, 'A-1');

    expect(parentTransactionCount($parentA))->toBe(1)
        ->and(parentTransactionCount($parentB))->toBe(0)
        ->and(affiliateCanSeePlan($affiliateA, 'B-1'))->toBeFalse();
});
```

- [ ] **Step 2: Run the full test suite, formatting check, and migration rehearsal**

Run: `php artisan test`

Run: `vendor/bin/pint --test`

Run: `php artisan migrate:fresh --seed --env=testing`

Expected: all commands exit 0.

- [ ] **Step 3: Execute the OresamSub staging backfill and parity checklist**

Compare pre/post counts for affiliates, offerings, users, transactions and wallet totals. Test one purchase for data, airtime, cable, and electricity. Confirm legacy and new reports show the same historical totals before turning on `MULTI_PARENT_PURCHASE_ROUTING=true` in staging.

- [ ] **Step 4: Write the shared-hosting deployment and rollback runbooks**

Document database backup/restore, migration order, dry-run backfill, feature-flag activation, cron configuration, log redaction check, smoke tests, performance thresholds, rollback flag, and the rule never to roll back schema destructively while new-path transactions exist.

- [ ] **Step 5: Perform the paid external-parent pilot only after a separate adapter plan is approved**

Required evidence: API documentation, sandbox credentials, plan-list response, purchase response, requery response, webhook specification, error catalogue, rate limits, and IP-whitelisting requirements. The provider-specific follow-up plan must implement and contract-test those exact behaviours before live funds are used.

- [ ] **Step 6: Commit the release gate**

```bash
git add tests/Feature/MultiParent/MultiParentEndToEndTest.php docs/operations config/multi_parent.php
git commit -m "test: add multi parent release gate"
```

## Execution sequence and review gates

1. **Schema gate:** Tasks 1–3. Review migration safety, dry-run output, backfill counts and rollback posture.
2. **Security gate:** Task 4. Do not continue until unknown domains fail closed and cross-tenant tests pass.
3. **Integration boundary gate:** Tasks 5–6. Confirm no controller chooses a provider with hard-coded parent logic on the new path.
4. **Catalogue/administration gate:** Tasks 7–9. Demonstrate parent creation, import, licence activation and affiliate inheritance.
5. **Money-flow gate:** Tasks 10–11. Review wallet locks, idempotency, refunds and sanitized provider attempts.
6. **Release gate:** Task 12. Back up, rehearse, compare parity, then activate through the feature flag.

## Follow-up plans after this foundation

These are deliberately separate because each can be reviewed and released independently:

1. First external parent's concrete adapter, written from its supplied documentation and sandbox.
2. Parent-owner authentication and self-service dashboard beyond platform-admin management.
3. Automatic API catalogue synchronization and category-suggestion workflow.
4. Direct multi-provider routing/failover within one parent.
5. Customer-facing affiliate WhatsApp purchasing bot.
6. Automated custom-domain/SSL provisioning.
