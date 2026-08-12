# Production Test Affiliate Reset Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Safely purge one explicitly confirmed affiliate tenant and recreate its domain beneath a new parent business.

**Architecture:** A service inventories and deletes tenant records in dependency order inside one transaction, then uses the existing parent creation service and creates a fresh affiliate plus parent-managed processing profile. An Artisan command is dry-run by default and requires `--execute` plus an exact `--confirm-domain` match.

**Tech Stack:** Laravel 11/12 conventions, Eloquent, Query Builder, Pest, MySQL/SQLite.

## Global Constraints

- Never infer a target from the current session or numeric affiliate ID.
- Never delete unless normalized domain and confirmation domain match exactly.
- Refuse OresamSub parent deletion.
- Parent-admin password is read from an environment variable or hidden prompt, never a CLI option.
- All destructive database work and replacement creation occur in one transaction.

### Task 1: Guarded reset service and command

**Files:**
- Create: `app/Services/MultiParent/ResetAffiliateTenantService.php`
- Create: `app/Console/Commands/ResetAffiliateForParentTest.php`
- Test: `tests/Feature/MultiParent/ResetAffiliateForParentTestTest.php`

- [ ] Write failing tests for dry-run, confirmation refusal, scoped deletion, and fresh parent-managed recreation.
- [ ] Implement dependency-ordered inventory and deletion.
- [ ] Implement guarded command and password handling.
- [ ] Run focused tests and full regression suite.
