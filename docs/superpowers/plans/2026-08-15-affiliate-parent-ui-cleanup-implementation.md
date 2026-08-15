# Affiliate and Parent Workspace UI Cleanup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Standardize the affiliate-admin and parent-admin workspaces without changing their workflows.

**Architecture:** Add Blade-first reusable presentation primitives and apply them to the two workspace shells and their highest-use pages. Alpine remains limited to lightweight disclosure and dynamic form interactions already present.

**Tech Stack:** Laravel Blade, Tailwind CSS, Alpine.js, Vite.

## Global Constraints

- Do not modify platform-admin or customer React screens.
- Do not alter routes, authorization, request fields, calculations or persistence.
- Preserve legacy affiliate compatibility.

---

### Task 1: Shared workspace presentation primitives

**Files:**
- Create: `resources/views/components/workspace/page-header.blade.php`
- Create: `resources/views/components/workspace/alert.blade.php`
- Create: `resources/views/components/workspace/status.blade.php`
- Create: `resources/views/components/workspace/pagination.blade.php`
- Modify: `resources/css/app.css`

- [ ] Add compact, accessible Blade components and Tailwind component classes.
- [ ] Compile Blade and CSS.

### Task 2: Parent workspace cleanup

**Files:**
- Modify: `resources/views/parent-admin/layouts/app.blade.php`
- Modify: `resources/views/parent-admin/dashboard.blade.php`
- Modify: `resources/views/parent-admin/transactions/index.blade.php`

- [ ] Standardize shell spacing, mobile navigation, dashboard hierarchy, filters, statuses, tables and manual actions.
- [ ] Verify parent routes, Blade compilation and transaction feature tests.

### Task 3: Affiliate workspace cleanup

**Files:**
- Modify: `resources/views/admin_dashboard.blade.php`
- Modify: `resources/views/admin/transactions/index.blade.php`
- Modify: `resources/views/admin/product_plans/index.blade.php`
- Modify: `resources/views/admin/settlement-funding/index.blade.php`

- [ ] Normalize dashboard cards, action hierarchy, table wrappers, empty states and pagination while preserving the legacy layout.
- [ ] Verify legacy Blade compilation and applicable feature tests.

### Task 4: Production verification

**Files:**
- Test: `tests/Feature/MultiParent`
- Test: `tests/Feature/ParentAdmin`

- [ ] Run focused PHP tests.
- [ ] Run route and Blade cache compilation.
- [ ] Run `npm run build` and `git diff --check`.
