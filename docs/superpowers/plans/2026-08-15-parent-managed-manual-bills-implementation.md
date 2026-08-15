# Parent-Managed Manual Cable and Electricity Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add configurable cable/electricity customer validation and safe pending/manual purchase processing.

**Architecture:** Extend provider product settings with an independent validation operation executed by the generic provider client. Add a manual-purchase service that reuses unified pricing and settlement reservations without vending, then expose parent-scoped idempotent completion actions.

**Tech Stack:** Laravel 11, Eloquent transactions, Laravel HTTP client, Blade/Alpine, Pest.

## Global Constraints

- One smartcard/slot or meter per controlled request.
- Main wallet only.
- Never send a vending request for manual-pending cable/electricity.
- Success captures settlement; failure releases settlement and refunds exactly once.
- Legacy traffic remains unchanged outside the feature and rollout gate.

---

### Task 1: Configurable customer validation

**Files:**
- Modify: `app/Http/Requests/ParentAdmin/SaveProviderConnectionRequest.php`
- Modify: `resources/views/parent-admin/provider-connections/index.blade.php`
- Modify: `app/Services/Providers/ConfigurableProviderClient.php`
- Test: `tests/Feature/MultiParent/ConfigurableProviderClientTest.php`

**Interfaces:**
- Produces: `ConfigurableProviderClient::validateCustomer(ParentProviderConnection $connection, string $productSlug, array $runtime): array`.

- [ ] Write failing tests for validation endpoint/method/mappings and normalized customer details.
- [ ] Run the focused tests and confirm failure because validation operations are unsupported.
- [ ] Add nested validation-operation request rules and semantic mapping checks.
- [ ] Add the cable/electricity validation editor to each product configuration.
- [ ] Implement validation execution using existing credential resolution, timeouts, response rules and redaction.
- [ ] Run the focused tests.

### Task 2: Pending cable/electricity purchases

**Files:**
- Create: `app/Services/Providers/ParentManagedManualPurchaseService.php`
- Modify: `app/Http/Controllers/CableSubscriptionController.php`
- Modify: `app/Http/Controllers/ElectricitySubscriptionController.php`
- Test: `tests/Feature/MultiParent/ParentManagedManualBillsTest.php`

**Interfaces:**
- Produces: `submit(User $customer, AffiliateProductPlan $plan, array $runtime, int $customerLevel, ?string $faceAmount): Transaction`.

- [ ] Write failing tests for validation, pricing, customer debit, settlement reservation and `manual_pending` creation.
- [ ] Verify the tests fail because the service and controller gates do not exist.
- [ ] Implement transactional submission without invoking `ParentPurchaseExecutor`.
- [ ] Add controlled controller branches for one cable slot/smartcard and one electricity meter.
- [ ] Return a pending response containing the transaction reference.
- [ ] Test insufficient balances, invalid validation and legacy fallback.

### Task 3: Parent manual completion

**Files:**
- Modify: `app/Services/Providers/ParentManagedManualPurchaseService.php`
- Modify: `app/Http/Controllers/ParentAdmin/TransactionController.php`
- Modify: `routes/parent-admin.php`
- Modify: `resources/views/parent-admin/transactions/index.blade.php`
- Test: `tests/Feature/MultiParent/ParentManagedManualBillsTest.php`

**Interfaces:**
- Produces: `complete(Transaction $transaction, ParentBusiness $parent, string $outcome, ?string $message): Transaction`.

- [ ] Write failing tests for parent scoping, success capture, failure refund/release and repeated completion.
- [ ] Verify the tests fail because no completion endpoint exists.
- [ ] Implement row-locked idempotent completion.
- [ ] Add parent-admin routes/controller validation and pending-row actions.
- [ ] Run focused and complete multi-parent suites.
- [ ] Run PHP lint, route cache, Blade cache and `git diff --check`.
