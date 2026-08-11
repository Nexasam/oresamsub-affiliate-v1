# Affiliate Settlement Funding Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give each affiliate a parent-scoped settlement wallet that its parent can fund safely and auditably.

**Architecture:** Add an aggregate wallet and immutable ledger, mutate them only through a transaction-safe service, and expose parent-owned wallets through guarded parent-admin routes. Existing customer funding remains independent.

**Tech Stack:** Laravel 11, PHP 8.4, Eloquent, MySQL, Blade, Pest.

## Global Constraints

- `parent_managed` will be the eventual default purchase mode, but live purchase execution is unchanged here.
- Existing OresamSub customer funding, provider credentials, webhooks and legacy purchases remain authoritative.
- All wallet writes are atomic and idempotent.

---

### Task 1: Settlement wallet schema and models

**Files:**
- Create: `database/migrations/2026_08_11_120000_create_affiliate_settlement_wallets.php`
- Create: `app/Models/AffiliateSettlementWallet.php`
- Create: `app/Models/AffiliateSettlementLedgerEntry.php`
- Modify: `app/Models/Affiliate.php`
- Test: `tests/Feature/MultiParent/AffiliateSettlementWalletSchemaTest.php`

- [ ] Write schema/relationship tests and verify they fail.
- [ ] Add decimal wallet balances, immutable ledger fields, tenant foreign keys and unique idempotency constraint.
- [ ] Add model casts and relationships.
- [ ] Run the focused test and commit.

### Task 2: Atomic settlement credit service

**Files:**
- Create: `app/Services/Wallet/AffiliateSettlementWalletService.php`
- Test: `tests/Feature/MultiParent/AffiliateSettlementFundingTest.php`

- [ ] Write tests for successful credit, duplicate reference and tenant mismatch; verify failure.
- [ ] Implement `credit(Affiliate $affiliate, ParentAdmin $actor, string $amount, string $reference, string $reason): AffiliateSettlementWallet` with row locking and decimal-safe arithmetic.
- [ ] Verify focused tests pass and commit.

### Task 3: Parent-admin funding endpoint and UI

**Files:**
- Create: `app/Http/Controllers/ParentAdmin/AffiliateSettlementWalletController.php`
- Create: `app/Http/Requests/ParentAdmin/CreditAffiliateSettlementWalletRequest.php`
- Create: `resources/views/parent-admin/affiliates/settlement-wallet.blade.php`
- Modify: `routes/parent-admin.php`
- Modify: `resources/views/parent-admin/affiliates/index.blade.php`
- Test: `tests/Feature/ParentAdmin/AffiliateSettlementWalletManagementTest.php`

- [ ] Write authorization, validation, credit and history tests; verify failure.
- [ ] Add owned-affiliate show and credit routes under `parent.affiliate` middleware.
- [ ] Build the compact wallet balance, credit form and ledger page.
- [ ] Run focused parent-admin tests and commit.

### Task 4: Regression verification

**Files:**
- Test: existing multi-parent, funding and onboarding suites.

- [ ] Run settlement-wallet tests.
- [ ] Run existing funding-provider and webhook tests.
- [ ] Run the full repository test suite.
- [ ] Confirm no customer wallet or legacy purchase behaviour changed.
