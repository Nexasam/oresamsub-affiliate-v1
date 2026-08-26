# Reusable Provider Connections Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move integration configuration into reusable adapters and provider connections so parents only select a provider or adapter, enter credentials, and submit for approval.

**Architecture:** Add a true `provider_adapters` catalogue, retain `provider_connections` as shared provider instances with snapshot configuration, and reduce `parent_provider_connections` to parent credentials and selection state. Runtime prefers the shared connection snapshot and falls back to existing parent settings so current approved parents continue unchanged.

**Tech Stack:** Laravel 11/12-compatible PHP, Blade, Alpine.js for repeatable configuration fields, Eloquent encrypted casts, Pest feature tests, MySQL/SQLite-compatible migrations.

**Spec:** `docs/superpowers/specs/2026-08-26-reusable-provider-connections-design.md`

## Global Constraints

- Existing approved parent connections and provider routes must remain executable without reapproval.
- Parent credentials remain encrypted only on `parent_provider_connections` and are never returned in normal responses or logs.
- Adapter changes never silently mutate connection snapshots.
- A proposed provider creates no shared connection until platform approval.
- Existing legacy OresamSub purchasing and rollout flags remain unchanged.
- Parent connection edits that change credentials or provider selection require platform reapproval.

---

### Task 1: Introduce the adapter and connection schema safely

**Files:**
- Create: `database/migrations/2026_08_26_100000_separate_provider_adapters_from_connections.php`
- Create: `app/Models/ProviderAdapter.php`
- Modify: `app/Models/ProviderConnection.php`
- Modify: `app/Models/ParentProviderConnection.php`
- Test: `tests/Feature/MultiParent/ReusableProviderConnectionSchemaTest.php`

**Interfaces:**
- Produces: `ProviderAdapter::connections()`, `ProviderConnection::providerAdapter()`, `ProviderConnection::effectiveSettings()`, and nullable discovery fields on `ParentProviderConnection`.

- [ ] Write a schema test proving adapter storage, snapshot configuration, nullable parent connection selection, discovery metadata, casts, and relationships.
- [ ] Run `php artisan test tests/Feature/MultiParent/ReusableProviderConnectionSchemaTest.php` and confirm it fails before the migration exists.
- [ ] Create `provider_adapters` with `name`, unique `slug`, unique `adapter_key`, JSON `capabilities`, JSON `settings`, integer `version`, status and timestamps.
- [ ] Add nullable `provider_adapter_id`, `base_url`, JSON `settings`, `adapter_version`, and provider identity URLs to `provider_connections`; add nullable `provider_adapter_id`, discovery identity fields and `request_type` to `parent_provider_connections`; make `provider_connection_id` nullable without changing existing IDs.
- [ ] Backfill one adapter per existing provider connection, attach the existing connection to it, and leave current parent settings/credentials untouched.
- [ ] Add casts and relationships, then rerun the schema test.

### Task 2: Resolve shared settings with legacy fallback

**Files:**
- Create: `app/Services/Providers/ProviderConnectionConfigurationResolver.php`
- Modify: `app/Services/Providers/ConfigurableProviderClient.php`
- Test: `tests/Feature/MultiParent/ProviderConnectionConfigurationResolverTest.php`
- Test: `tests/Feature/MultiParent/ConfigurableProviderClientTest.php`

**Interfaces:**
- Produces: `ProviderConnectionConfigurationResolver::settings(ParentProviderConnection $connection): array` and `baseUrl(ParentProviderConnection $connection): ?string`.

- [ ] Write failing tests showing shared connection settings win for new records while old parent settings and base URL remain the fallback.
- [ ] Implement the resolver without persisting or mutating either record.
- [ ] Inject it into execute, validate-customer and requery paths in `ConfigurableProviderClient`.
- [ ] Run the resolver/client tests and existing purchase-route tests.

### Task 3: Build platform adapter and connection catalogues

**Files:**
- Modify: `app/Http/Controllers/PlatformAdmin/ProviderAdapterController.php`
- Modify: `app/Http/Requests/PlatformAdmin/SaveProviderAdapterRequest.php`
- Create: `app/Http/Controllers/PlatformAdmin/ProviderConnectionCatalogueController.php`
- Create: `app/Http/Requests/PlatformAdmin/SaveProviderConnectionCatalogueRequest.php`
- Create: `app/Services/PlatformAdmin/ProviderConnectionCatalogueService.php`
- Modify: `resources/views/platform-admin/provider-adapters/index.blade.php`
- Create: `resources/views/platform-admin/provider-connections/catalogue.blade.php`
- Modify: `routes/platform-admin.php`
- Test: `tests/Feature/PlatformAdmin/ProviderAdapterManagementTest.php`
- Create: `tests/Feature/PlatformAdmin/ProviderConnectionCatalogueTest.php`

**Interfaces:**
- Adapter saves accept `capabilities` plus complete `settings`.
- `ProviderConnectionCatalogueService::save(array $data, ?ProviderConnection $connection = null)` copies adapter settings for new connections and merges explicit overrides without changing older snapshots after later adapter edits.

- [ ] Update adapter tests to target `ProviderAdapter` and prove complete configuration persists.
- [ ] Write failing catalogue tests for adapter prefill, overrides, uniqueness, activation and snapshot isolation.
- [ ] Implement adapter configuration persistence and the connection catalogue controller/request/service/routes.
- [ ] Build the platform connection catalogue editor with adapter selection, prefilled configuration, and explicit overrides.
- [ ] Run both platform catalogue suites.

### Task 4: Replace parent advanced setup with provider selection and credentials

**Files:**
- Modify: `app/Http/Controllers/ParentAdmin/ProviderConnectionController.php`
- Modify: `app/Http/Requests/ParentAdmin/SaveProviderConnectionRequest.php`
- Modify: `app/Services/ParentAdmin/ProviderConnectionService.php`
- Modify: `resources/views/parent-admin/provider-connections/index.blade.php`
- Test: `tests/Feature/ParentAdmin/ProviderConnectionManagementTest.php`

**Interfaces:**
- Existing request: `provider_adapter_id`, `provider_connection_id`, `credentials`, `name`, `is_primary`.
- Discovery request: the same fields with null `provider_connection_id`, plus `proposed_provider_name`, `proposed_base_url`, optional `proposed_documentation_url` and `discovery_notes`.

- [ ] Write failing tests for filtered adapter/connection choices, dynamic credential validation, existing selection, discovery submission, secret preservation and reapproval.
- [ ] Change the request so technical settings are no longer accepted from new parent submissions.
- [ ] Update the service to store only credentials/preferences/discovery metadata while leaving existing legacy settings untouched on edits.
- [ ] Replace the advanced parent form with adapter selection, matching connection selection, “provider not listed”, credential fields, identity fields and approval status.
- [ ] Run parent connection management tests.

### Task 5: Approve discovery requests atomically and safely

**Files:**
- Modify: `app/Http/Controllers/PlatformAdmin/ParentProviderConnectionController.php`
- Create: `app/Services/PlatformAdmin/ParentConnectionApprovalService.php`
- Modify: `resources/views/platform-admin/provider-connections/index.blade.php`
- Test: `tests/Feature/PlatformAdmin/ParentProviderConnectionReviewTest.php`

**Interfaces:**
- Produces: `ParentConnectionApprovalService::approve(ParentProviderConnection $parentConnection, Admin $reviewer, array $overrides = []): ParentProviderConnection`.

- [ ] Write failing tests proving existing approvals remain simple, discovery approval creates/reuses one provider connection, rejection creates none, credentials are never exposed, and cross-adapter duplicates are prevented.
- [ ] Implement a locked database transaction that deduplicates by adapter and normalized host, snapshots adapter settings, attaches the parent record and records approval.
- [ ] Expand the review presentation with request-type badges, non-secret provider details and per-field credential-presence indicators.
- [ ] Add confirmation/rejection controls while keeping raw credentials inaccessible.
- [ ] Run the approval suite.

### Task 6: Compatibility regression and deployment verification

**Files:**
- Modify tests only if a compatibility expectation must be made explicit.

- [ ] Run all adapter, connection, route-resolver and configurable-client tests.
- [ ] Run parent-managed data, airtime, cable/electricity and reconciliation tests.
- [ ] Run `php artisan view:cache`, `php artisan route:list`, `git diff --check`, and apply the migration locally.
- [ ] Confirm an old parent fixture executes from `parent_provider_connections.settings` and a new parent fixture executes from `provider_connections.settings`.
