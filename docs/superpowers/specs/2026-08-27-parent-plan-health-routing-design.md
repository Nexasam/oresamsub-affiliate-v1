# Parent Plan Health and Provider Switching Design

## Goal

Notify parent administrators about meaningful provider-route failures and let them safely switch a plan to another approved connection without memorising provider plan IDs.

## Health rules

- Health events include conclusive failures plus unresolved `reconciliation_required` and `reconciliation_exhausted` transactions.
- Transactions ultimately marked successful are excluded, including manually reconciled successes.
- An alert becomes actionable at three events within 30 minutes or five events within 24 hours.
- Alerts are grouped by parent, source product plan, and provider connection.
- One open database notification is retained per grouped incident; its count and latest reason are refreshed instead of creating notification spam.

## Switching rules

- A source product plan may retain one provider route per parent connection, each with its own external provider plan ID.
- The active primary route has priority `1`. Saved alternatives use priorities greater than `1` and remain inactive.
- The switch drawer lists approved active parent connections that support the plan's service.
- Existing route mappings display their saved external plan ID and are immediately selectable.
- A connection without a saved mapping requires an external plan ID before switching.
- Switching runs in a locked database transaction, demotes the current route, activates the selected route at priority `1`, and preserves all mappings.
- Parent ownership, adapter service capability, connection approval, and connection activation are validated server-side.

## Interface

- Parent dashboard shows threshold-qualified grouped incidents only.
- Each incident exposes transaction review, disable-plan, and switch-provider actions.
- The switch action uses a compact Alpine drawer and a normal Blade form submission.
- Parent dashboard includes an unread health-notification summary.

## Safety and performance

- No affiliate-plan rows are bulk-updated.
- Queries are parent-scoped, time-bounded, grouped, and indexed through existing transaction route fields.
- Switching never deletes historical mappings and never mutates transaction snapshots.
- All state-changing actions require the parent-admin guard and CSRF protection.

