# Effective Plan Availability and Health Alerts Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make parent plan availability immediately authoritative across affiliate catalogues and purchases without bulk row updates, while giving parent admins grouped failure alerts and a safe disable action.

**Architecture:** Add a reusable effective-availability scope/state API to `AffiliateProductPlan`, backed by parent plan flags and its active primary route. Use it in customer catalogue queries and purchase resolvers, preserve affiliate-local visibility during sync, and expose parent-disabled state in affiliate administration. Aggregate recent failures by source product plan and provider connection in a focused dashboard service; a dedicated parent-scoped endpoint disables the parent plan and primary route atomically.

**Tech Stack:** Laravel 11, Eloquent, Blade, Alpine.js, Pest/PHPUnit.

**Spec:** Accepted conversation design: `parent active AND affiliate visibility allowed AND affiliate plan active AND primary route active`.

## Global Constraints

- Preserve legacy OresamSub behavior; effective parent controls apply to multi-parent-owned catalogue records.
- Do not bulk-update affiliate product-plan rows when a parent plan changes.
- Preserve affiliate prices, profit settings, and local visibility preferences.
- Enforce availability both when listing plans and immediately before purchase execution.
- Scope health alerts and disable actions to the authenticated parent business.

---

### Task 1: Effective availability contract

**Files:**
- Modify: `app/Models/AffiliateProductPlan.php`
- Modify: `app/Services/Providers/PurchaseRouteResolver.php`
- Test: `tests/Feature/MultiParent/EffectivePlanAvailabilityTest.php`

**Interfaces:**
- Produces: `AffiliateProductPlan::scopeCustomerAvailable(Builder $query)` and `availabilityState(): array`.
- Consumes: parent plan `visibility`, `affiliate_visibility`, affiliate plan `visibility`, `visibility_from_admin`, and an active priority-1 provider route.

- [ ] Write tests proving each false input makes a plan unavailable and local state remains unchanged.
- [ ] Run the test and verify it fails because the scope/state API does not exist.
- [ ] Implement the Eloquent scope and state method, reusing it in `PurchaseRouteResolver` validation.
- [ ] Run the test and existing route-resolver tests.

### Task 2: Catalogue visibility and affiliate controls

**Files:**
- Modify: `app/Http/Services/DataPlansService.php`
- Modify: `app/Http/Controllers/ProductPlanController.php`
- Modify: `resources/views/admin/product_plans/index.blade.php`
- Test: `tests/Feature/MultiParent/EffectivePlanAvailabilityTest.php`

**Interfaces:**
- Consumes: `scopeCustomerAvailable()` and `availabilityState()` from Task 1.
- Produces: DataTable fields `effective_availability`, `parent_availability`, and `affiliate_toggle_enabled`.

- [ ] Write tests proving parent-disabled plans stay in affiliate administration but disappear from customer plan results.
- [ ] Run the tests and verify the expected failures.
- [ ] Apply the effective scope to the shared four-service customer catalogue query.
- [ ] Stop plan sync from overwriting affiliate-local `visibility`; synchronize only source attributes.
- [ ] Reject affiliate activation while the parent plan is unavailable and render `Disabled by parent` with a disabled toggle.
- [ ] Run catalogue, pricing, and controller tests.

### Task 3: Parent plan health alerts and disable action

**Files:**
- Create: `app/Services/ParentAdmin/PlanHealthAlertService.php`
- Modify: `app/Http/Controllers/ParentAdmin/DashboardController.php`
- Modify: `app/Http/Controllers/ParentAdmin/ProductPlanController.php`
- Modify: `routes/parent-admin.php`
- Modify: `resources/views/parent-admin/dashboard.blade.php`
- Test: `tests/Feature/ParentAdmin/PlanHealthAlertTest.php`

**Interfaces:**
- Produces: `PlanHealthAlertService::forParent(ParentBusiness $parent, int $hours = 24): Collection`.
- Produces: route `parent-admin.product-plans.disable` accepting a parent-owned `ProductPlan`.

- [ ] Write tests for grouping failures by plan/connection, parent isolation, diagnostics links, and disabling only the parent plan/primary route.
- [ ] Run tests and verify missing service/route failures.
- [ ] Implement an aggregate query limited to recent failed/reconciliation states, then enrich only grouped latest records.
- [ ] Derive a safe HTTP(S) provider-dashboard origin from the configured connection base URL.
- [ ] Render grouped alerts with counts, latest reason, affiliate, provider, transaction filter link, provider link, and confirmation-backed disable action.
- [ ] Implement the transactional parent-scoped disable endpoint without touching affiliate rows.
- [ ] Run the focused parent-admin suite.

### Task 4: Full verification

**Files:**
- Test: relevant MultiParent and ParentAdmin suites.

- [ ] Run `php artisan view:cache` and `git diff --check`.
- [ ] Run effective availability, route resolver, parent purchases, customer catalogue, and health-alert tests.
- [ ] Confirm no migration is required and existing legacy tests remain green.
