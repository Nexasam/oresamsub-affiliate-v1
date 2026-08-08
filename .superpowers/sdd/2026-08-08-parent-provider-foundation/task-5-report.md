# Task 5 report

## Status

Complete. Legacy customer levels are consolidated tenant-safely to level 6, legacy plans remain present but hidden, generation is capped at positions 1–6, and affiliate/level uniqueness is enforced after audited duplicate cleanup.

## TDD evidence

- RED: `php artisan test tests/Feature/MultiParent/LegacyLevelConsolidationTest.php` failed because the consolidation method did not exist, generation created level 7, and the plan update accepted level 7.
- GREEN: the focused suite passed with 3 tests and 21 assertions.

## Verification

- `vendor/bin/pint ...` completed successfully for all relevant implementation and test files.
- Focused plus platform-admin regression: 17 tests passed, 122 assertions.
- Existing backfill plus parent schema regression: 14 tests passed, 109 assertions.
- `git diff --check` completed without errors.

## Implementation notes

- Customer rows and affected plans are locked inside an affiliate-scoped transaction.
- Per-user audits use the unique database key `customer-plan-consolidation:{user_id}:{old_plan_id}`; duplicate plan mappings also receive a unique plan-level audit.
- Re-running consolidation moves no customers and creates no duplicate audits.
- Levels 7–12 are hidden, never deleted by consolidation.
- The composite unique index covers only the nullable canonical slot; historical duplicate and legacy rows remain stored with a null slot.
- `generateUserPlans(Affiliate $affiliate): array` and its `created`/`existing` contract are preserved.

## Concerns

- None blocking. Migration up/down preserves all historical plan rows and user assignments; committed backfill performs the separately audited and reversible-in-dry-run customer reassignment.

## Review round 1

- Replaced the destructive `(affiliate_id, plan_level)` migration with a reversible nullable `canonical_plan_level` slot. Migration up selects a visible/default row with lowest-ID tie-breaking for each affiliate/level 1–6 without changing users, visibility, or row count; down removes only the auxiliary index and column.
- Added a nullable unique `deterministic_key` audit column. Customer moves now use database-backed deterministic audit keys instead of scanning JSON metadata.
- Moved duplicate resolution into the committed/dry-run backfill transaction. Customers move only within their affiliate, duplicate and legacy rows are hidden but retained, and dry-run rolls everything back.
- Added a preflight that rejects cross-affiliate customer-plan corruption before any candidate affiliate is claimed.
- Removed the misleading `plan_level` input from the rename-only affiliate controller. The platform-admin editor persists validated levels through the canonical slot.
- Review verification: focused, Task 4 backfill, and platform-admin suites passed together (29 tests, 220 assertions). A fresh SQLite migration and one-step rollback both completed successfully.

## Review round 2

- Canonical selection now prefers visible rows, then default rows, then lowest ID. Backfill re-evaluates the same rule under lock and ensures each canonical plan remains visible and usable.
- Every retained duplicate gets a deterministic plan-level audit mapping its plan ID to the canonical plan ID, including duplicates with no customers. Per-user move audits remain separate.
- Existing null-slot duplicates retain that slot during rename and visibility edits. Only creation or an explicitly validated level change computes a canonical slot, so promotion conflicts return validation errors instead of database exceptions.

## Review round 3

- Every moved customer's audit is asserted independently for its deterministic key and exact old/new plan IDs.
- Audit creation now uses `firstOrCreate` on the database-unique deterministic key. Reprocessing retained duplicates leaves the complete plan-audit row byte-for-byte unchanged, including batch UUID, timestamps, values, and metadata.
- Retained null-slot duplicates may be renamed or kept hidden, but reactivation is rejected by model validation before any database write; the platform endpoint returns 422 without mutation.

## Review round 4

- RED: a focused regression for retained legacy levels 7 and 12 failed because the platform-admin visibility endpoint returned 200 when asked to reactivate level 7.
- GREEN: reactivation validation now applies to every retained null-canonical-slot row, including legacy levels above 6. Rename/hidden updates remain allowed, and a normal canonical level 6 can still be made visible.
- The regression covers both the platform endpoint and direct model updates for levels 7 and 12.
- Verification: Pint passed; Task 5 focused, backfill, and platform-admin suites passed together (31 tests, 250 assertions); `git diff --check` passed.
