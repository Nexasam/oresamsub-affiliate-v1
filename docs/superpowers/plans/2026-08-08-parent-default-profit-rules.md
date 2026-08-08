# Parent Default Profit Rules Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add parent- and reseller-level service defaults, plan overrides, and server-side pricing-matrix filters without changing live purchase processing.

**Architecture:** Persist one default rule per parent, reseller level, and global product. Treat normalized plan-level price rows as explicit overrides and expose a resolver that returns inherited or custom effective pricing for the parent-admin workspace. Keep filtering inside the parent-scoped catalogue query before Laravel pagination.

**Tech Stack:** Laravel 13, PHP 8.3, Eloquent, MySQL/SQLite-compatible migrations, Pest, Blade, Alpine.js, Axios, Brick Math.

## Global Constraints

- A parent can have one through six active reseller levels.
- Initial Data and Cable defaults are ₦50 flat profit.
- Initial Airtime and Electricity defaults are 1% discount.
- Existing normalized OresamSub prices remain explicit custom overrides.
- All reads and writes are scoped to the authenticated parent.
- The live purchase-processing and legacy profit paths remain unchanged.

---

### Task 1: Default-profit persistence and relationships

**Files:**
- Create: `database/migrations/2026_08_08_130000_create_parent_default_profit_rules.php`
- Create: `app/Models/ParentDefaultProfitRule.php`
- Modify: `app/Models/ParentBusiness.php`
- Modify: `app/Models/ParentResellerLevel.php`
- Test: `tests/Feature/ParentAdmin/DefaultProfitRulesTest.php`

**Interfaces:**
- Produces `ParentDefaultProfitRule` with `parent_business_id`, `parent_reseller_level_id`, `product_id`, `calculation_type`, and `value`.
- Produces unique parent/level/product enforcement and decimal casting.

- [ ] Write schema tests proving the table, relationships, uniqueness, casts, and cross-parent application validation requirements.
- [ ] Run `php artisan test tests/Feature/ParentAdmin/DefaultProfitRulesTest.php` and verify failure because the model/table do not exist.
- [ ] Add the migration, model, and relationships. Use `flat` and `percent_discount` calculation types and a non-negative decimal value.
- [ ] Re-run the test and verify it passes.
- [ ] Commit with `feat: add parent default profit rule schema`.

### Task 2: Default generation, validation, and inheritance resolver

**Files:**
- Create: `app/Services/ParentAdmin/ParentProfitRuleService.php`
- Create: `app/Http/Requests/ParentAdmin/UpdateDefaultProfitRulesRequest.php`
- Modify: `app/Services/ParentAdmin/ParentCatalogService.php`
- Modify: `tests/Feature/ParentAdmin/DefaultProfitRulesTest.php`

**Interfaces:**
- Produces `ensureDefaults(ParentBusiness $parent): Collection`.
- Produces `replaceDefaults(ParentBusiness $parent, array $rules): Collection`.
- Produces `effectiveRules(ParentBusiness $parent, Collection $plans, Collection $levels): array` for workspace serialization.
- `flat` values accept decimals greater than or equal to zero; `percent_discount` values accept zero through 100.

- [ ] Add failing tests for four defaults per active level, idempotency, independent per-level edits, percentage bounds, and cross-parent rejection.
- [ ] Run the test and verify the expected missing-service failures.
- [ ] Implement default creation by matching case-insensitive global product names `data`, `cable`, `airtime`, and `electricity`; do not create duplicate global products.
- [ ] Implement transactional replacement and effective-rule resolution, where a plan-level price row is marked custom and an absent row inherits its service default.
- [ ] Re-run the focused test and verify it passes.
- [ ] Commit with `feat: resolve parent default profit rules`.

### Task 3: Parent-admin API for defaults and overrides

**Files:**
- Modify: `app/Http/Controllers/ParentAdmin/PricingController.php`
- Modify: `app/Http/Requests/ParentAdmin/UpdateProductPlanPricesRequest.php`
- Modify: `app/Services/ParentAdmin/ParentCatalogService.php`
- Modify: `routes/parent-admin.php`
- Modify: `tests/Feature/ParentAdmin/PricingManagementTest.php`

**Interfaces:**
- `GET /parent-admin/pricing/data` returns `levels`, `defaults`, `products`, and paginated plans containing effective per-level pricing state.
- `PUT /parent-admin/pricing/defaults` replaces the authenticated parent's submitted default rules.
- `DELETE /parent-admin/pricing/plans/{plan}/levels/{level}` removes one authenticated-parent override and restores inheritance.

- [ ] Add failing request tests for fetching/saving defaults, clearing an override, restoring inheritance, and rejecting foreign plans or levels.
- [ ] Run the focused pricing tests and verify route/response failures.
- [ ] Add routes and controller actions, keeping multi-row updates transactional.
- [ ] Ensure newly created or generated reseller levels receive missing service defaults.
- [ ] Re-run pricing and default-rule tests and verify they pass.
- [ ] Commit with `feat: expose parent pricing inheritance api`.

### Task 4: Server-side catalogue filters

**Files:**
- Modify: `app/Services/ParentAdmin/ParentCatalogService.php`
- Modify: `app/Http/Controllers/ParentAdmin/PricingController.php`
- Modify: `tests/Feature/ParentAdmin/PricingManagementTest.php`

**Interfaces:**
- Extend `plans(ParentBusiness $parent, int $perPage = 50, array $filters = []): LengthAwarePaginator`.
- Accepted filters are `search`, `product_id`, `category_id`, and `pricing_status` (`inherited` or `custom`).

- [ ] Add failing tests with matching records beyond page one for plan search, service, category/network, inherited/custom status, combined filters, parent isolation, and filtered totals.
- [ ] Run focused tests and verify results are currently unfiltered.
- [ ] Add parenthesized search predicates and relationship filters before pagination; implement custom status with parent-scoped `whereHas` and inherited status with `whereDoesntHave`.
- [ ] Validate query values in the controller and pass only accepted filters.
- [ ] Re-run focused tests and verify they pass.
- [ ] Commit with `feat: filter parent pricing catalogue`.

### Task 5: Default settings and filtered matrix UI

**Files:**
- Modify: `resources/views/parent-admin/pricing/index.blade.php`
- Modify: `tests/Feature/ParentAdmin/PricingManagementTest.php`

**Interfaces:**
- Alpine state contains `defaults`, `products`, `filters`, and debounced/filter-triggered page-one loading.
- Each matrix cell exposes `pricing_source`, default/effective rule details, `Customize`, and `Use default` actions.

- [ ] Add failing rendering tests for `Default profit settings`, all four filter controls, `Using default`, `Customize`, and `Use default` copy.
- [ ] Run the rendering test and verify missing-interface failures.
- [ ] Add the responsive default-rule table, validation-aware save action, full-catalogue filters, source badges, effective preview, override editing, and clear-override action.
- [ ] Ensure filter changes reset pagination to page one and are sent as Axios query parameters.
- [ ] Re-run pricing tests and verify they pass.
- [ ] Commit with `feat: add parent default profit pricing workspace`.

### Task 6: Migration and regression verification

**Files:**
- Modify only files required by failures discovered within this feature's scope.

**Interfaces:**
- Existing `product_plan_parent_prices` rows continue to serialize as custom overrides.
- No purchase service consumes `ParentDefaultProfitRule` in this phase.

- [ ] Run `php artisan migrate:fresh --seed` against the local test-safe database configuration and confirm defaults can be generated without changing legacy purchase code.
- [ ] Run `php artisan test tests/Feature/ParentAdmin tests/Feature/MultiParent tests/Feature/PlatformAdmin` and require zero failures.
- [ ] Run `npm run build` and require exit code zero.
- [ ] Run `git diff --check` and inspect `git status --short`, preserving `docs/.DS_Store` and `docs/research/`.
- [ ] Commit any scoped verification fixes with `fix: stabilize parent profit inheritance`.
