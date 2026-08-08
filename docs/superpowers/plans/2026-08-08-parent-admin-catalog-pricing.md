# Parent-Admin Catalog and Pricing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give the seeded OresamSub parent administrator a secure minimal workspace for managing only OresamSub-owned product plans and one-to-six reseller-level prices, while making the same workflow reusable by future parents.

**Architecture:** Add an isolated `parent-admin` route and Blade/Alpine workspace backed by the existing `parent_admin` guard. A focused parent-catalog service always receives the authenticated `ParentBusiness` and scopes every plan, level and normalized price query to it; global categories remain read-only choices. OresamSub API synchronization remains unchanged and OresamSub-only, legacy pricing columns remain intact, and no purchase path is switched.

**Tech Stack:** PHP 8.3, Laravel 13, Eloquent, Blade, Alpine.js, Tailwind CSS, Axios, Pest, MySQL/MariaDB locally and SQLite in tests.

## Global Constraints

- Work on the current `main` branch only because the user explicitly authorized it.
- Do not touch `docs/.DS_Store` or `docs/research/`.
- Do not deploy, connect to production, or enable any multi-parent runtime feature flag.
- `product_plan_categories` remains global and parent administrators may select but not mutate it.
- Every plan and normalized price read/write must be scoped to `auth('parent_admin')->user()->parent_business_id`.
- OresamSub synchronization remains restricted to the parent with slug `oresamsub`; this plan does not add synchronization for another parent.
- A parent may have one through six reseller levels; position is unique per parent and never exceeds six.
- Deleting levels or plans is outside this scope; use active/inactive state so historical references remain intact.
- Preserve `cost_price_1` through `cost_price_6`, existing affiliate prices, profit modes, maximum-profit restrictions, commissions and transaction history.
- Store normalized prices in `product_plan_parent_prices`; do not switch live purchase pricing reads in this plan.
- Parent plan prices must be non-negative and cannot be below the plan's provider `cost_price` when that cost is numeric.
- Cross-parent route-model binding must return 404 and cross-parent submitted IDs must return validation errors without partial writes.
- Use database transactions for multi-row level and price updates.
- Use TDD for every task and run the full MultiParent, ParentAdmin and PlatformAdmin regression set before completion.

---

## File map

### Parent-admin shell

- `routes/parent-admin.php` — isolated guest/auth routes under `/parent-admin`.
- `routes/web.php` — requires the new route file once, outside affiliate middleware.
- `app/Http/Controllers/ParentAdmin/AuthController.php` — login/logout for the `parent_admin` guard.
- `app/Http/Controllers/ParentAdmin/DashboardController.php` — redirects the minimal workspace root to catalogue management.
- `resources/views/parent-admin/auth/login.blade.php` — parent login form.
- `resources/views/parent-admin/layouts/app.blade.php` — parent-branded navigation exposing only Plans, Pricing and logout.

### Catalogue and pricing domain

- `app/Services/ParentAdmin/ParentCatalogService.php` — parent-scoped plan creation/update and transactional level/price persistence.
- `app/Http/Requests/ParentAdmin/StoreProductPlanRequest.php` — validates parent plan creation.
- `app/Http/Requests/ParentAdmin/UpdateProductPlanRequest.php` — validates editable plan fields.
- `app/Http/Requests/ParentAdmin/UpdateResellerLevelsRequest.php` — validates a complete ordered level set of length 1-6.
- `app/Http/Requests/ParentAdmin/UpdateProductPlanPricesRequest.php` — validates prices keyed by the authenticated parent's active level IDs.
- `app/Http/Controllers/ParentAdmin/ProductPlanController.php` — plan listing, creation and update endpoints.
- `app/Http/Controllers/ParentAdmin/PricingController.php` — level and normalized-price endpoints.
- `app/Models/ParentBusiness.php` — adds product-plan relationship if absent.
- `app/Models/ParentResellerLevel.php` — adds parent and price relationships.
- `app/Models/ProductPlanParentPrice.php` — adds parent, plan and level relationships.

### Screens and tests

- `resources/views/parent-admin/product-plans/index.blade.php` — searchable parent-owned plan table and create/edit forms.
- `resources/views/parent-admin/pricing/index.blade.php` — level editor and price matrix for up to six levels.
- `tests/Feature/ParentAdmin/AuthenticationTest.php` — guard isolation and active-account authentication.
- `tests/Feature/ParentAdmin/ProductPlanManagementTest.php` — scoped list/create/update/category behavior.
- `tests/Feature/ParentAdmin/PricingManagementTest.php` — one-to-six levels, price rules, atomicity and tenant isolation.

---

### Task 1: Minimal parent-admin authentication shell

**Files:**
- Create: `routes/parent-admin.php`
- Modify: `routes/web.php`
- Create: `app/Http/Controllers/ParentAdmin/AuthController.php`
- Create: `app/Http/Controllers/ParentAdmin/DashboardController.php`
- Create: `resources/views/parent-admin/auth/login.blade.php`
- Create: `resources/views/parent-admin/layouts/app.blade.php`
- Test: `tests/Feature/ParentAdmin/AuthenticationTest.php`

**Interfaces:**
- Produces named routes `parent-admin.login`, `parent-admin.login.store`, `parent-admin.dashboard`, and `parent-admin.logout`.
- Produces authenticated parent identity through `Auth::guard('parent_admin')->user(): ParentAdmin`.

- [ ] **Step 1: Write failing authentication and isolation tests**

Cover guest redirect, active parent-admin login, inactive rejection, logout, `last_login_at`, redirect to `/parent-admin`, and isolation from both affiliate and `platform_admin` guards. Assert an authenticated platform admin cannot enter `/parent-admin` without a parent-admin session.

- [ ] **Step 2: Run the tests and verify RED**

```bash
php artisan test tests/Feature/ParentAdmin/AuthenticationTest.php
```

Expected: failures because parent-admin routes and controllers do not exist.

- [ ] **Step 3: Implement isolated routes and authentication**

Define `/parent-admin/login` under `guest:parent_admin`, and dashboard/logout under `auth:parent_admin`. Validate email/password, add `active => true`, regenerate the session after login, forget any unrelated `url.intended`, update `last_login_at`, and redirect to `parent-admin.dashboard`. Logout must invalidate the session and regenerate the CSRF token.

- [ ] **Step 4: Build the minimal parent layout**

Render the authenticated parent business name, parent admin name/email and logout. Add the Plans and Pricing navigation links in Tasks 2 and 3 when their named routes exist. Do not include affiliates, wallets, reports, transactions or provider management.

- [ ] **Step 5: Verify GREEN and format**

```bash
vendor/bin/pint app/Http/Controllers/ParentAdmin routes/parent-admin.php tests/Feature/ParentAdmin/AuthenticationTest.php
php artisan test tests/Feature/ParentAdmin/AuthenticationTest.php
```

- [ ] **Step 6: Commit Task 1**

```bash
git add routes/web.php routes/parent-admin.php app/Http/Controllers/ParentAdmin resources/views/parent-admin tests/Feature/ParentAdmin/AuthenticationTest.php
git commit -m "feat: add minimal parent admin workspace"
```

---

### Task 2: Parent-scoped product-plan management

**Files:**
- Create: `app/Services/ParentAdmin/ParentCatalogService.php`
- Create: `app/Http/Requests/ParentAdmin/StoreProductPlanRequest.php`
- Create: `app/Http/Requests/ParentAdmin/UpdateProductPlanRequest.php`
- Create: `app/Http/Controllers/ParentAdmin/ProductPlanController.php`
- Modify: `app/Models/ParentBusiness.php`
- Create: `resources/views/parent-admin/product-plans/index.blade.php`
- Modify: `routes/parent-admin.php`
- Test: `tests/Feature/ParentAdmin/ProductPlanManagementTest.php`

**Interfaces:**
- Produces `ParentCatalogService::plans(ParentBusiness $parent, int $perPage = 50): LengthAwarePaginator`.
- Produces `ParentCatalogService::createPlan(ParentBusiness $parent, array $attributes): ProductPlan`.
- Produces `ParentCatalogService::updatePlan(ParentBusiness $parent, ProductPlan $plan, array $attributes): ProductPlan`.
- Produces routes `parent-admin.product-plans.index|data|store|update`.

- [ ] **Step 1: Write failing tenant and CRUD tests**

Create OresamSub and a second parent with plans in the same global category. Assert OresamSub's admin sees only OresamSub plans, can create a plan assigned automatically to OresamSub, can update its name/category/cost/visibility fields, and receives 404 when updating the second parent's plan. Assert a submitted `parent_business_id` is ignored or rejected and cannot move a plan. Assert global categories are selectable but cannot be edited through these endpoints.

- [ ] **Step 2: Run the tests and verify RED**

```bash
php artisan test tests/Feature/ParentAdmin/ProductPlanManagementTest.php
```

- [ ] **Step 3: Implement request validation and service scoping**

Creation accepts `product_plan_name`, `product_plan_category_id`, nullable numeric `cost_price`, `profit_category` in `flat|percent`, and boolean visibility fields. Category validation uses an existing global category ID. The service sets `parent_business_id` from `$parent->id`; updates first verify `$plan->parent_business_id === $parent->id` and abort with 404 otherwise. Do not expose automation IDs, provider routes or legacy six price columns for editing here.

- [ ] **Step 4: Implement controller endpoints and relationships**

Load categories with their product/network labels and paginate parent-owned plans with category relations. Add `ParentBusiness::productPlans(): HasMany`. Return consistent JSON messages and refreshed resources after mutations.

- [ ] **Step 5: Build the product-plan screen**

Use the existing Blade/Alpine/Axios admin pattern. Include search/filter, category, plan name, cost, flat/percentage mode, active/affiliate/public visibility, create and save controls. Clearly label global categories as platform-defined and show the authenticated parent name. Do not offer delete, provider mapping or API synchronization controls.

- [ ] **Step 6: Verify GREEN and format**

```bash
vendor/bin/pint app/Services/ParentAdmin app/Http/Requests/ParentAdmin app/Http/Controllers/ParentAdmin app/Models/ParentBusiness.php tests/Feature/ParentAdmin/ProductPlanManagementTest.php
php artisan test tests/Feature/ParentAdmin/ProductPlanManagementTest.php tests/Feature/ParentAdmin/AuthenticationTest.php
```

- [ ] **Step 7: Commit Task 2**

```bash
git add app/Services/ParentAdmin app/Http/Requests/ParentAdmin app/Http/Controllers/ParentAdmin/ProductPlanController.php app/Models/ParentBusiness.php resources/views/parent-admin/product-plans routes/parent-admin.php tests/Feature/ParentAdmin/ProductPlanManagementTest.php
git commit -m "feat: add parent scoped product plan management"
```

---

### Task 3: Variable reseller levels and normalized price management

**Files:**
- Modify: `app/Services/ParentAdmin/ParentCatalogService.php`
- Create: `app/Http/Requests/ParentAdmin/UpdateResellerLevelsRequest.php`
- Create: `app/Http/Requests/ParentAdmin/UpdateProductPlanPricesRequest.php`
- Create: `app/Http/Controllers/ParentAdmin/PricingController.php`
- Modify: `app/Models/ParentResellerLevel.php`
- Modify: `app/Models/ProductPlanParentPrice.php`
- Create: `resources/views/parent-admin/pricing/index.blade.php`
- Modify: `routes/parent-admin.php`
- Test: `tests/Feature/ParentAdmin/PricingManagementTest.php`

**Interfaces:**
- Produces `ParentCatalogService::replaceLevels(ParentBusiness $parent, array $levels): Collection`.
- Produces `ParentCatalogService::updatePrices(ParentBusiness $parent, ProductPlan $plan, array $prices): Collection`.
- Produces routes `parent-admin.pricing.index|data`, `parent-admin.pricing.levels.update`, and `parent-admin.pricing.plans.update`.

- [ ] **Step 1: Write failing level and pricing tests**

Assert a parent can retain one level, expand by one click to the default six names, rename positions, and deactivate surplus unreferenced levels without deleting them. Reject zero levels, seven levels, duplicate positions, blank names and foreign level IDs. Assert normalized prices upsert by `(parent_business_id, product_plan_id, parent_reseller_level_id)`, reject foreign plans/levels, reject negative values, and reject a selling price below numeric provider cost. Verify an invalid matrix leaves every existing price unchanged.

- [ ] **Step 2: Run the tests and verify RED**

```bash
php artisan test tests/Feature/ParentAdmin/PricingManagementTest.php
```

- [ ] **Step 3: Implement transactional level replacement**

Validate an ordered `levels` array containing 1-6 entries with exact positions `1..count(levels)`, optional existing same-parent IDs, names and active status. In one transaction, lock the parent's levels, update/create submitted positions, and mark omitted levels inactive only when doing so does not invalidate an affiliate or normalized-price reference. Return a validation error explaining referenced levels must be retained. The generate-six action submits the canonical missing names Basic, Bronze, Silver, Gold, Diamond and Platinum without overwriting existing names.

- [ ] **Step 4: Implement transactional price upserts**

Validate `prices` as an array of `{parent_reseller_level_id, selling_price, max_profit}`. Lock and verify the plan and each level belong to the authenticated parent. Require exactly one entry for every active parent level, compare monetary values with `Brick\Math\BigDecimal`, then `updateOrCreate` normalized records inside one transaction. Never write `cost_price_1..6` or other legacy fields.

- [ ] **Step 5: Build the pricing matrix**

Show an editable level strip above a paginated parent plan matrix. Each active level becomes one price column with selling price and optional maximum profit. Include save-per-plan, save-levels and one-click “Complete six levels” actions, loading/error/success feedback, provider-cost context and a warning that live purchase pricing is not switched yet.

- [ ] **Step 6: Verify GREEN and format**

```bash
vendor/bin/pint app/Services/ParentAdmin app/Http/Requests/ParentAdmin app/Http/Controllers/ParentAdmin/PricingController.php app/Models/ParentResellerLevel.php app/Models/ProductPlanParentPrice.php tests/Feature/ParentAdmin/PricingManagementTest.php
php artisan test tests/Feature/ParentAdmin
```

- [ ] **Step 7: Commit Task 3**

```bash
git add app/Services/ParentAdmin app/Http/Requests/ParentAdmin app/Http/Controllers/ParentAdmin/PricingController.php app/Models/ParentResellerLevel.php app/Models/ProductPlanParentPrice.php resources/views/parent-admin/pricing routes/parent-admin.php tests/Feature/ParentAdmin/PricingManagementTest.php
git commit -m "feat: add parent reseller pricing workspace"
```

---

### Task 4: Regression, OresamSub acceptance and operator documentation

**Files:**
- Modify: `docs/product/affiliate-network-product-roadmap.md`
- Create: `docs/runbooks/parent-admin-catalog-local-acceptance.md`
- Modify tests only if verification exposes a scoped defect.

**Interfaces:**
- Consumes all prior task routes and services.
- Produces a local acceptance checklist; it does not authorize production deployment.

- [ ] **Step 1: Add an OresamSub acceptance test**

Seed the OresamSub parent and admin, authenticate using controlled test credentials, verify 147 backfilled plans are parent-visible when the populated fixture provides them, update representative flat and percentage plans, persist prices for all six seeded levels, and prove another parent's plan and level remain inaccessible. In isolated SQLite tests, use a smaller representative dataset while the runbook checks populated local counts.

- [ ] **Step 2: Run all relevant regression tests**

```bash
php artisan test tests/Feature/ParentAdmin tests/Feature/MultiParent tests/Feature/PlatformAdmin
```

Expected: all relevant tests pass. Run `php artisan test` separately and record any unrelated pre-existing failures without expanding this task's scope.

- [ ] **Step 3: Run frontend and formatting checks**

```bash
npm run build
vendor/bin/pint --test app/Http/Controllers/ParentAdmin app/Http/Requests/ParentAdmin app/Services/ParentAdmin app/Models/ParentBusiness.php app/Models/ParentResellerLevel.php app/Models/ProductPlanParentPrice.php routes/parent-admin.php tests/Feature/ParentAdmin
git diff --check
```

- [ ] **Step 4: Perform local-only browser acceptance**

Document and verify: parent login, parent name in layout, plan list isolation, plan creation/editing, one-to-six level editing, six-level generation, price persistence, validation messages, mobile table behavior and logout. Confirm the legacy affiliate purchase flow and every multi-parent runtime flag remain unchanged.

- [ ] **Step 5: Update roadmap status and write the runbook**

Mark only the two immediate priorities complete after verification. Record local URLs, seed command, safe test credentials procedure, expected checks and the explicit statement that provider mapping and purchase conversion are still pending.

- [ ] **Step 6: Commit Task 4**

```bash
git add docs/product/affiliate-network-product-roadmap.md docs/runbooks/parent-admin-catalog-local-acceptance.md tests/Feature/ParentAdmin
git commit -m "docs: add parent catalog local acceptance"
```

---

## Completion gate

This plan is complete only when OresamSub's parent admin can log in and manage OresamSub-owned plans and one-to-six normalized prices; a second parent is demonstrably isolated; global categories remain platform-owned; legacy prices, profits and purchase processing remain untouched; relevant tests and asset build pass; and production has not been accessed.

The next plan after this one is Phase 1 provider connection management followed by Phase 3 plan-to-provider mapping. Runtime purchase conversion remains a separate, explicitly approved plan.
