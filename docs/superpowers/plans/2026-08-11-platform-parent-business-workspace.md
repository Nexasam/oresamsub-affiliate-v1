# Platform Parent Business Workspace Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a platform-admin frontend and API that atomically creates a parent business, its first administrator, and six default reseller levels.

**Architecture:** A dedicated form request validates tenant and administrator fields. A focused service performs transactional creation and presentation. A platform controller exposes Blade, JSON listing, and creation endpoints, while an Alpine-powered Blade workspace follows the existing platform-admin interface.

**Tech Stack:** Laravel, Eloquent, Blade, Alpine.js, Axios, Pest, Tailwind CSS.

## Global Constraints

- Only authenticated platform administrators may access the workspace.
- Provider credentials, provider mappings, affiliates, and product plans are outside this form.
- Passwords must be hashed and excluded from responses.
- Creation must be atomic.
- Preserve unrelated workspace files.

---

### Task 1: Transactional parent foundation creation

**Files:**
- Create: `tests/Feature/PlatformAdmin/ParentBusinessManagementTest.php`
- Create: `app/Http/Requests/PlatformAdmin/StoreParentBusinessRequest.php`
- Create: `app/Services/PlatformAdmin/ParentBusinessService.php`

**Interfaces:**
- Consumes: validated business and administrator fields.
- Produces: `ParentBusinessService::create(array $data): ParentBusiness` and `ParentBusinessService::present(ParentBusiness $parent): array`.

- [ ] Write tests proving atomic business, administrator, and six-level creation; password hashing; duplicate validation; and no partial writes.
- [ ] Run the tests and verify route/service absence causes the expected failure.
- [ ] Implement validation and the transactional service with level names `Basic`, `Bronze`, `Silver`, `Gold`, `Diamond`, `Platinum`.
- [ ] Run the focused tests and verify they pass.

### Task 2: Protected platform endpoints and workspace

**Files:**
- Create: `app/Http/Controllers/PlatformAdmin/ParentBusinessController.php`
- Create: `resources/views/platform-admin/parent-businesses/index.blade.php`
- Modify: `routes/platform-admin.php`
- Modify: `resources/views/platform-admin/layouts/app.blade.php`
- Test: `tests/Feature/PlatformAdmin/ParentBusinessManagementTest.php`

**Interfaces:**
- Consumes: `ParentBusinessService`.
- Produces: `GET /admin/parent-businesses`, `GET /admin/parent-businesses/data`, and `POST /admin/parent-businesses`.

- [ ] Add failing authentication, rendering, listing, sidebar, and redacted JSON tests.
- [ ] Run the focused tests and verify they fail because the endpoints and view are absent.
- [ ] Add controller routes, sidebar entry, responsive list, and combined Alpine creation form.
- [ ] Run focused tests and verify the complete flow passes.

### Task 3: Verification and delivery

**Files:**
- Modify generated `public/build` assets only through `npm run build`.

**Interfaces:**
- Consumes: completed backend and frontend.
- Produces: verified committed feature.

- [ ] Run Pint on changed PHP files.
- [ ] Run `php artisan test tests/Feature/PlatformAdmin tests/Feature/ParentAdmin tests/Feature/MultiParent`.
- [ ] Run `npm run build` and record existing unrelated warnings separately.
- [ ] Check scoped diffs, commit only feature files, and leave `docs/.DS_Store` and `docs/research/` untouched.
