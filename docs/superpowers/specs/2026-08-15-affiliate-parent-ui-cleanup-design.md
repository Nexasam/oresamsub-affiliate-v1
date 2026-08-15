# Affiliate and Parent Workspace UI Cleanup

## Scope

Clean up only the affiliate-admin and parent-admin workspaces. Platform-admin and customer React screens are excluded.

## Design

- Preserve the existing slate/blue visual language and existing Blade layouts.
- Make page headers, actions, alerts, forms, filters, tables, badges, empty states and pagination visually consistent.
- Keep screens compact enough for shared-hosting business operators using small laptops and phones.
- Use Blade for all initial rendering. Alpine is limited to sidebar state, disclosure panels, searchable selectors and repeatable mapping rows.
- Preserve every route, permission, form name, controller contract and business calculation.
- Prioritize dashboards, transactions, provider connections, product plans/pricing, affiliates, settlement/funding and onboarding.
- Tables retain a table layout on desktop and use safe horizontal scrolling on narrow screens.
- Destructive or financial actions remain visually distinct and retain confirmation behaviour.

## Verification

- Existing parent and multi-parent feature tests must remain green.
- Blade and route caches must compile.
- The production frontend bundle must build.
- The key dashboard and transaction pages must remain responsive and free of clipped actions.

