# Hybrid Parent and Affiliate Funding Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver an additive, parent-scoped funding configuration foundation with hybrid credential ownership, approval-controlled switching, webhook idempotency and an unchanged OresamSub legacy default.

**Architecture:** New normalized catalogue and tenant configuration tables sit beside legacy funding tables. A resolver selects only approved active configurations when the feature flag is enabled; virtual-account snapshots and webhook event uniqueness preserve historical routing.

**Tech Stack:** Laravel 11, Eloquent, Blade, Pest, MySQL/SQLite-compatible migrations.

## Global Constraints

- Existing OresamSub funding generation and webhook routes remain unchanged by default.
- Disabling a provider blocks only new virtual-account generation.
- Credentials use encrypted casts and are never serialized.
- Mode changes require parent approval and do not interrupt the currently approved mode.

---

### Task 1: Additive funding schema and domain models

**Files:** Create funding migrations, models and schema tests.

- [ ] Write failing schema, encryption, snapshot and idempotency tests.
- [ ] Run the tests and confirm missing-table failures.
- [ ] Add catalogue, parent provider, affiliate configuration, mode request and webhook event tables.
- [ ] Add nullable routing snapshots to legacy virtual accounts.
- [ ] Run schema tests and commit.

### Task 2: Platform provider catalogue

**Files:** Create platform funding-provider controller/view/routes and feature tests.

- [ ] Write failing platform authorization, creation, editing and deactivation tests.
- [ ] Implement a Blade catalogue with Xixapay and SecurewaveNG seeding.
- [ ] Verify only platform administrators can mutate definitions.

### Task 3: Parent funding workspace

**Files:** Create parent funding controller/view/routes and feature tests.

- [ ] Write failing tests for enablement, encrypted credentials, activation and new-generation disabling.
- [ ] Implement parent-owned configuration and affiliate mode approval.
- [ ] Verify cross-parent records are inaccessible.

### Task 4: Affiliate configuration

**Files:** Create affiliate funding controller/view/routes and feature tests.

- [ ] Write failing tests for affiliate-owned credentials, bank settings and pending mode requests.
- [ ] Implement masked credential editing limited to parent-enabled providers.
- [ ] Verify parent-managed secrets are never exposed to affiliates.

### Task 5: Resolver and webhook safety foundation

**Files:** Create funding resolver, webhook event service, configuration and tests.

- [ ] Write failing legacy-default, enabled-resolution and duplicate-event tests.
- [ ] Implement `MULTI_PARENT_FUNDING_ENABLED=false` default and fail-closed resolution.
- [ ] Implement idempotent event recording and historical configuration lookup.
- [ ] Run focused and regression suites, then commit the feature.
