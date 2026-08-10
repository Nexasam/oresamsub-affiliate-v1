# Platform Provider Adapters Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give authenticated platform administrators a safe catalogue workspace for creating and editing provider adapter definitions consumed by parent provider connections.

**Architecture:** Keep `provider_connections` as the platform-owned adapter catalogue and `parent_provider_connections` as tenant-owned API configuration. Catalogue records contain identity and an allow-list of capabilities only; credentials, URLs, mappings, headers, network IDs, success rules, and other provider configuration remain parent-scoped. Referenced catalogue records are deactivated instead of deleted.

**Tech Stack:** Laravel 13, PHP 8.3, Blade, Alpine.js, Pest, Vite.

## Global Constraints

- Only the `platform_admin` guard may manage catalogue records.
- `slug` and `adapter` are globally unique lowercase machine keys.
- Supported services are limited to `data`, `airtime`, `cable`, and `electricity`.
- Supported HTTP methods are limited to `GET` and `POST`.
- Credential fields are limited to `api_public_key`, `api_secret_key`, and `api_password`.
- No credentials, URLs, executable PHP class names, purchase routing, or deletion is part of this feature.

---

### Task 1: Platform adapter API

**Files:**
- Create: `tests/Feature/PlatformAdmin/ProviderAdapterManagementTest.php`
- Create: `app/Http/Requests/PlatformAdmin/SaveProviderAdapterRequest.php`
- Create: `app/Http/Controllers/PlatformAdmin/ProviderAdapterController.php`
- Modify: `app/Models/ProviderConnection.php`
- Modify: `routes/platform-admin.php`

**Interfaces:**
- Produces authenticated index, data, store, and update routes under `/admin/provider-adapters`.
- Stores capabilities as `{services: string[], methods: string[], credential_fields: string[]}`.

- [ ] Write feature tests for guest protection, rendering/data, creation, normalization, uniqueness, editing, deactivation, and rejection of unsupported capabilities.
- [ ] Run the focused test and verify it fails because the routes do not exist.
- [ ] Implement request normalization/validation and controller CRUD without a destroy route.
- [ ] Run the focused tests and make them pass.

### Task 2: Catalogue workspace

**Files:**
- Create: `resources/views/platform-admin/provider-adapters/index.blade.php`
- Modify: `resources/views/platform-admin/layouts/app.blade.php`
- Modify: `resources/views/platform-admin/dashboard.blade.php`

**Interfaces:**
- Consumes the JSON endpoints from Task 1.
- Produces list, create, edit, activate, and deactivate controls with repeatable capability selections.

- [ ] Extend the rendering test with the expected workspace copy and navigation.
- [ ] Run the focused test and verify the new assertion fails.
- [ ] Build the Blade/Alpine workspace using existing platform-admin styling.
- [ ] Run focused tests and the frontend build.

### Task 3: Parent visibility and regression verification

**Files:**
- Modify: `tests/Feature/ParentAdmin/ProviderConnectionManagementTest.php`

**Interfaces:**
- Confirms parent creation choices include only active adapters while saved connections remain readable after catalogue deactivation.

- [ ] Write the visibility regression test and verify the expected failure if current behavior is incomplete.
- [ ] Make the smallest required query/presentation adjustment.
- [ ] Run ParentAdmin, PlatformAdmin, and MultiParent feature suites.
- [ ] Run Pint, Vite production build, and `git diff --check`.
- [ ] Commit the scoped implementation without touching unrelated user files.
