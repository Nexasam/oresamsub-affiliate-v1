# Parent-Managed Airtime Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Route eligible single-number airtime purchases through the proven parent-managed provider and financial workflow while preserving the legacy fallback.

**Architecture:** Generalize `ParentManagedPurchaseOrchestrator` to derive the transaction service from the selected plan and accept airtime face amount separately from customer price. Add an early controlled branch to `AirtimeController::buy_airtime_action()` matching the data controller's gate, leaving the legacy loop untouched.

**Tech Stack:** Laravel 11, Eloquent transactions, Pest, Laravel HTTP/provider abstractions.

## Global Constraints

- Parent-managed airtime initially supports one phone number and `main_wallet` only.
- Never duplicate provider requests, customer debits, settlement reservations or refunds.
- Ambiguous responses remain pending reconciliation and are not immediately refunded.
- Legacy OresamSub airtime remains the fallback whenever the feature or rollout gate is disabled.

---

### Task 1: Service-aware parent purchase accounting

**Files:**
- Modify: `app/Services/Providers/ParentManagedPurchaseOrchestrator.php`
- Test: `tests/Feature/MultiParent/ParentManagedAirtimePurchaseTest.php`

**Interfaces:**
- Consumes: `purchase(User, AffiliateProductPlan, array $runtime, int $customerLevel, ?string $faceAmount)`
- Produces: transactions and wallet logs labelled from the resolved product slug (`data` or `airtime`).

- [ ] Write a failing test proving an airtime plan creates an airtime transaction, preserves face amount, debits the discounted customer price, and captures settlement.
- [ ] Run the focused test and verify it fails because the orchestrator hard-codes data.
- [ ] Derive the normalized service from `affiliatePlan.product_plan.product_plan_category.product.slug`.
- [ ] Store service-aware transaction category, description, debit and refund log labels.
- [ ] Run the focused test and the existing data executor tests.

### Task 2: Controlled airtime controller branch

**Files:**
- Modify: `app/Http/Controllers/AirtimeController.php`
- Test: `tests/Feature/MultiParent/ParentManagedAirtimePurchaseTest.php`

**Interfaces:**
- Consumes: feature flags, `ProviderRoutingRolloutService::enabledFor()`, affiliate processing profile, and the generalized orchestrator.
- Produces: the existing JSON purchase response contract.

- [ ] Write failing HTTP tests for successful routing, multi-number rejection and legacy fallback.
- [ ] Run tests and verify the controller still enters legacy logic.
- [ ] Add the early parent-managed gate after validation, PIN, plan, discount and phone normalization.
- [ ] Pass face amount, discounted customer price runtime data, network and reference to the orchestrator.
- [ ] Return success, failure or reconciliation responses without entering the legacy wallet loop.
- [ ] Run focused controller tests.

### Task 3: Financial failure and reconciliation regression

**Files:**
- Test: `tests/Feature/MultiParent/ParentManagedAirtimePurchaseTest.php`
- Verify: `app/Services/Providers/ParentManagedPurchaseOrchestrator.php`

**Interfaces:**
- Consumes: existing executor result states (`successful`, `failed`, `reconciliation_required`).
- Produces: exactly-once capture/release/refund behavior for airtime.

- [ ] Add a conclusive failure test asserting one customer refund and one settlement release.
- [ ] Add an ambiguous response test asserting no refund and a reconciliation-required transaction.
- [ ] Add insufficient customer and settlement balance tests asserting no provider call.
- [ ] Run the complete multi-parent suite.

### Task 4: Final compatibility verification

**Files:**
- Verify: `app/Http/Controllers/AirtimeController.php`
- Verify: `app/Services/Providers/ParentManagedPurchaseOrchestrator.php`

- [ ] Run PHP lint, route cache and Blade cache.
- [ ] Run parent purchase, pricing, provider mapping and legacy airtime-related tests.
- [ ] Run `git diff --check` and inspect the final diff for legacy-path changes.
