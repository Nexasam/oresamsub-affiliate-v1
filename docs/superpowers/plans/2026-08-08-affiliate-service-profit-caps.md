# Affiliate Service Profit Caps Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let each parent administrator manage service-level maximum profits across an affiliate's six customer pricing levels and reject conflicting reductions.

**Architecture:** Store normalized parent-owned caps per affiliate, global product, and customer level. A focused service initializes migration-safe values, validates complete matrices, reports conflicts, and enforces caps when affiliate plan margins change. A dedicated parent-admin controller exposes only affiliates belonging to the authenticated parent, while the existing pricing page hosts the new matrix.

**Tech Stack:** Laravel 13, PHP 8.3, Eloquent, MySQL/SQLite migrations, Pest, Blade, Alpine.js, Axios.

## Global Constraints

- Data and Cable start at ₦70 flat for new affiliates.
- Airtime and Electricity start at 1% for new affiliates.
- Existing affiliates initialize to the greater of the default and current highest matching profit.
- A rejected reduction reports all conflicts and writes nothing.
- Parent administrators can access only their own affiliates.
- There are exactly six customer pricing levels.

---

### Task 1: Cap schema and relationships

**Files:**
- Create: `database/migrations/2026_08_08_140000_create_affiliate_service_profit_caps.php`
- Create: `app/Models/AffiliateServiceProfitCap.php`
- Modify: `app/Models/Affiliate.php`
- Modify: `app/Models/ParentBusiness.php`
- Test: `tests/Feature/ParentAdmin/AffiliateServiceProfitCapsTest.php`

- [ ] Write failing tests for columns, unique affiliate/product/level rows, decimal casts, and parent ownership.
- [ ] Run the focused test and verify it fails because the table is absent.
- [ ] Add the table, composite ownership key, model, casts, and relationships.
- [ ] Re-run the focused test and verify it passes.
- [ ] Commit `feat: add affiliate service profit cap schema`.

### Task 2: Initialization and conflict service

**Files:**
- Create: `app/Services/ParentAdmin/AffiliateProfitCapService.php`
- Modify: `tests/Feature/ParentAdmin/AffiliateServiceProfitCapsTest.php`

- [ ] Add failing tests for 24 new-affiliate defaults, migration-safe existing values, idempotency, complete updates, and structured conflicts.
- [ ] Run focused tests and verify missing-service failures.
- [ ] Implement `ensureCaps`, `replaceCaps`, `violations`, and `assertPlanMarginsWithinCaps` with transactional writes.
- [ ] Re-run focused tests and verify they pass.
- [ ] Commit `feat: resolve affiliate service profit caps`.

### Task 3: Parent-owned API

**Files:**
- Create: `app/Http/Controllers/ParentAdmin/AffiliateProfitCapController.php`
- Create: `app/Http/Requests/ParentAdmin/UpdateAffiliateProfitCapsRequest.php`
- Modify: `routes/parent-admin.php`
- Modify: `tests/Feature/ParentAdmin/AffiliateServiceProfitCapsTest.php`

- [ ] Add failing request tests for affiliate listing, loading, saving, foreign access rejection, and 422 conflict payloads.
- [ ] Run focused tests and verify missing routes.
- [ ] Add authenticated parent-scoped routes, controller, and complete-matrix validation.
- [ ] Re-run focused tests and verify they pass.
- [ ] Commit `feat: expose parent affiliate profit cap api`.

### Task 4: Enforce caps on affiliate plan changes

**Files:**
- Modify: `app/Models/AffiliateProductPlan.php`
- Modify: `app/Http/Controllers/PlatformAdmin/AffiliateOperationsController.php`
- Modify: `tests/Feature/ParentAdmin/AffiliateServiceProfitCapsTest.php`
- Modify: `tests/Feature/PlatformAdmin/AffiliateOperationsTest.php`

- [ ] Add failing tests proving at-cap updates succeed, above-cap updates fail without partial writes, and unrelated fields remain editable.
- [ ] Run focused tests and verify above-cap values currently persist.
- [ ] Add centralized model/service enforcement and convert administration failures into structured validation responses.
- [ ] Re-run both focused suites and verify they pass.
- [ ] Commit `feat: enforce affiliate service profit caps`.

### Task 5: Parent pricing interface

**Files:**
- Modify: `resources/views/parent-admin/pricing/index.blade.php`
- Modify: `tests/Feature/ParentAdmin/AffiliateServiceProfitCapsTest.php`

- [ ] Add failing rendering assertions for affiliate selector, four services, six levels, and conflict presentation.
- [ ] Run the rendering test and verify missing copy.
- [ ] Add the visually separate Affiliate maximum pricing section, load/save actions, and structured violation list.
- [ ] Re-run focused tests and verify they pass.
- [ ] Commit `feat: manage affiliate caps in parent pricing workspace`.

### Task 6: Local migration and regression verification

**Files:**
- Modify only scoped files required by discovered failures.

- [ ] Preview and apply the new migration to the local database.
- [ ] Run formatter checks on changed PHP files.
- [ ] Run `php artisan test tests/Feature/ParentAdmin tests/Feature/MultiParent tests/Feature/PlatformAdmin` with zero failures.
- [ ] Run `npm run build` with exit code zero.
- [ ] Run `git diff --check`, preserve `docs/.DS_Store` and `docs/research/`, and commit scoped fixes.
