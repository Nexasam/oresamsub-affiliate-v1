# Parent Provider Connection Approval Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Require platform approval for every new or materially changed parent provider connection while keeping credentials encrypted and hidden.

**Architecture:** Preserve `status` for parent-controlled operational activation and add a separate approval state to `parent_provider_connections`. Parent submissions own provider configuration; platform administrators review redacted structure and approve or reject it. Sensitive changes invalidate prior approval, while display name, operational status, and primary preference do not.

**Tech Stack:** Laravel 13, PHP 8.3, Blade, Alpine.js, Pest, Vite.

## Global Constraints

- New parent connections are always `pending`.
- Existing connections are grandfathered as `approved` during migration.
- Credentials remain encrypted and are never returned to platform-admin responses.
- Sensitive fields are adapter, base URL, credentials, HTTP method, endpoints, mappings, headers, network mapping, success conditions, response paths, expected codes, funding bank fields, and support URL.
- Only platform admins may approve or reject.
- Rejection requires a reason; a corrected rejected connection returns to `pending`.
- Purchase processing is not switched in this feature.
- Adapter services and parent endpoint rows are driven by global `products.slug` values, covering data, airtime, utility bills, cable subscriptions, E-Pins, result checker, and future products.

---

### Task 1: Approval state and parent submission lifecycle

**Files:**
- Create: `database/migrations/2026_08_10_160000_add_approval_to_parent_provider_connections.php`
- Modify: `app/Models/ParentProviderConnection.php`
- Modify: `app/Services/ParentAdmin/ProviderConnectionService.php`
- Modify: `tests/Feature/ParentAdmin/ProviderConnectionManagementTest.php`

- [ ] Add failing tests for pending creation, non-sensitive edits retaining approval, sensitive edits requiring reapproval, and rejected correction resubmission.
- [ ] Add approval schema, casts, relationships, and lifecycle logic.
- [ ] Run focused parent tests until green.

### Task 1B: Product-driven service configuration

**Files:**
- Modify: `app/Http/Requests/PlatformAdmin/SaveProviderAdapterRequest.php`
- Modify: `app/Http/Controllers/PlatformAdmin/ProviderAdapterController.php`
- Modify: `app/Http/Requests/ParentAdmin/SaveProviderConnectionRequest.php`
- Modify: `resources/views/platform-admin/provider-adapters/index.blade.php`
- Modify: `resources/views/parent-admin/provider-connections/index.blade.php`

- [ ] Add failing tests proving global product slugs appear in adapter choices and all selected product endpoints are accepted.
- [ ] Replace fixed service choices and endpoint validation with global product slugs, retaining legacy cable/electricity aliases.
- [ ] Run focused adapter and parent tests until green.

### Task 2: Platform review API and queue

**Files:**
- Create: `app/Http/Controllers/PlatformAdmin/ParentProviderConnectionController.php`
- Create: `app/Http/Requests/PlatformAdmin/ReviewParentProviderConnectionRequest.php`
- Create: `resources/views/platform-admin/provider-connections/index.blade.php`
- Modify: `routes/platform-admin.php`
- Modify: `resources/views/platform-admin/layouts/app.blade.php`
- Create: `tests/Feature/PlatformAdmin/ParentProviderConnectionReviewTest.php`

- [ ] Add failing tests for guard protection, redacted queue data, approval, rejection reason, and invalid review actions.
- [ ] Implement the platform-only review endpoints and redacted presenter.
- [ ] Build the queue with pending-first filtering, configuration inspection, approve, and reject controls.
- [ ] Run focused platform tests until green.

### Task 3: Parent status UX and verification

**Files:**
- Modify: `resources/views/parent-admin/provider-connections/index.blade.php`

- [ ] Show pending/approved/rejected badges, rejection feedback, and reapproval warnings on the parent workspace.
- [ ] Run ParentAdmin, PlatformAdmin, and MultiParent suites.
- [ ] Run Pint, the production build, and `git diff --check`.
- [ ] Apply the migration locally and commit only scoped files.

### Task 4: Generic all-product provider client

**Files:**
- Create: `app/Services/Providers/ConfigurableProviderClient.php`
- Create: `app/Support/ProviderProductRegistry.php`
- Create: `tests/Feature/MultiParent/ConfigurableProviderClientTest.php`

- [ ] Add failing tests for product endpoint selection, GET/POST, runtime and credential mapping, network mapping, nested success rules, timeouts, invalid JSON, approval enforcement, and normalized results.
- [ ] Implement the generic client with Laravel HTTP finite timeouts and no live purchase-flow wiring.
- [ ] Run the focused client tests until green.
