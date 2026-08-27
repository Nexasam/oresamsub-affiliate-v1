# Parent Plan Health and Provider Switching Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add threshold-based parent health notifications and safe remembered provider-route switching from the parent dashboard.

**Architecture:** Extend the existing health aggregation service, persist deduplicated database notifications for parent admins, and add a focused route-switching service that retains one external plan mapping per connection. The Blade dashboard receives prepared switch options and submits through a parent-scoped controller.

**Tech Stack:** Laravel 11, Eloquent, database notifications, Blade, Alpine.js, Pest.

**Spec:** `docs/superpowers/specs/2026-08-27-parent-plan-health-routing-design.md`

## Global Constraints

- Preserve legacy OresamSub processing.
- Never expose provider credentials.
- Do not bulk-update inherited affiliate plans.
- Do not retry purchase requests during provider switching.

---

### Task 1: Correct threshold-qualified health aggregation

**Files:**
- Modify: `app/Services/ParentAdmin/PlanHealthAlertService.php`
- Test: `tests/Feature/ParentAdmin/PlanHealthAlertTest.php`

**Interfaces:**
- Produces: `PlanHealthAlertService::forParent(ParentBusiness $parent, int $limit = 10): Collection`

- [ ] Write failing tests proving manually successful transactions are excluded and 3/30-minute or 5/24-hour groups qualify.
- [ ] Run the focused test and verify the expected failure.
- [ ] Implement time-window aggregation and qualification.
- [ ] Run the focused test and verify it passes.

### Task 2: Persist deduplicated parent notifications

**Files:**
- Create: `app/Notifications/ParentPlanHealthNotification.php`
- Create: `app/Services/ParentAdmin/PlanHealthNotificationService.php`
- Modify: `app/Models/ParentAdmin.php`
- Modify: `app/Http/Controllers/ParentAdmin/DashboardController.php`
- Test: `tests/Feature/ParentAdmin/PlanHealthNotificationTest.php`

**Interfaces:**
- Consumes: qualified alerts from `PlanHealthAlertService::forParent()`.
- Produces: one unread database notification per parent/plan/connection incident key.

- [ ] Write a failing test for creation, deduplication, and parent isolation.
- [ ] Run the focused test and verify the expected failure.
- [ ] Implement the database notification and synchronisation service.
- [ ] Run the focused test and verify it passes.

### Task 3: Switch routes while retaining provider plan IDs

**Files:**
- Create: `app/Services/ParentAdmin/ProductPlanRouteSwitchService.php`
- Create: `app/Http/Requests/ParentAdmin/SwitchProductPlanRouteRequest.php`
- Create: `app/Http/Controllers/ParentAdmin/ProductPlanRouteController.php`
- Modify: `routes/parent-admin.php`
- Test: `tests/Feature/ParentAdmin/ProductPlanRouteSwitchTest.php`

**Interfaces:**
- Produces: `ProductPlanRouteSwitchService::switch(ParentBusiness $parent, ProductPlan $plan, ParentProviderConnection $connection, string $providerPlanId): ProductPlanProviderRoute`.

- [ ] Write failing tests for remembered mappings, first-time IDs, ownership, approval, service support, and atomic single-primary switching.
- [ ] Run the focused test and verify the expected failures.
- [ ] Implement validation, controller, route, and locked switch transaction.
- [ ] Run the focused test and verify it passes.

### Task 4: Add the health-notification and switch drawer UI

**Files:**
- Modify: `app/Services/ParentAdmin/PlanHealthAlertService.php`
- Modify: `resources/views/parent-admin/dashboard.blade.php`
- Test: `tests/Feature/ParentAdmin/PlanHealthAlertTest.php`

**Interfaces:**
- Consumes: alert route options containing connection ID, label, readiness, and saved provider plan ID.

- [ ] Write a failing dashboard test for threshold copy, notification count, switch button, remembered IDs, and first-time mapping input.
- [ ] Run the focused test and verify the expected failure.
- [ ] Render the compact Alpine confirmation drawer and normal Blade switch form.
- [ ] Run the focused test and verify it passes.

### Task 5: Regression verification

**Files:**
- Test: `tests/Feature/ParentAdmin/PlanHealthAlertTest.php`
- Test: `tests/Feature/ParentAdmin/PlanHealthNotificationTest.php`
- Test: `tests/Feature/ParentAdmin/ProductPlanRouteSwitchTest.php`
- Test: `tests/Feature/MultiParent/PurchaseRouteResolverTest.php`

- [ ] Run all focused parent health and routing tests.
- [ ] Run syntax checks for changed PHP files.
- [ ] Run `git diff --check`.
- [ ] Report migrations, cache-clear commands, and operational behaviour.

