# Parent-Scoped VTU Platform Design

## Purpose

Transform the existing OresamSub-specific affiliate platform into a manufacturer platform that supports multiple parent businesses. Each parent owns affiliates, provider connections, plans, reseller pricing, funding configuration, and operational data. Existing production behavior remains available during a phased local migration.

The current OresamSub storefront and every existing affiliate become children of the OresamSub parent. OresamSub is the only operational provider integration in the first release. Other providers such as Gongoz and AffaTech are added only after their integration contracts are supplied.

## Delivery strategy

Use an expand–migrate–validate–switch–clean-up process:

1. Add nullable ownership columns and new tables without changing live behavior.
2. Seed OresamSub and backfill existing ownership in chunks.
3. Validate counts, relationships, prices, funding, and transaction history.
4. Switch one flow at a time behind feature flags.
5. Observe locally and later in production before removing legacy columns.

Every backfill must support rollback-only dry runs, explicit commits, repeat execution, chunked processing, and an audit report. Existing primary IDs must not change. Shared-hosting deployment must run during a low-traffic period after a complete database backup.

## Roles and authority

### Platform administrators

Platform administrators represent the manufacturer. They can create global provider definitions, create and support parent businesses, override or suspend unsafe connections, and access cross-parent operational reporting. Sensitive changes are audited.

### Parent administrators

Parent administrators are separate from affiliate administrators. They manage only their parent business, affiliates, reseller levels, plans, provider connections, and approved funding connections. They cannot access another parent.

OresamSub receives a new parent-admin account from environment values. If values are absent, the seeder uses a default name and local email, generates a random temporary password, prints it once, stores only its hash, and sets `must_change_password`.

### Affiliate administrators

Affiliate administrators manage customer-facing prices, customer reseller plans, branding, and permitted funding settings. They cannot change their parent, parent reseller level, parent plan prices, or parent provider credentials.

## Core ownership schema

### `parent_businesses`

- `id`
- `name`
- unique `slug`
- contact fields
- `status`
- timestamps

### `parent_admins`

- `id`
- `parent_business_id`
- `name`
- unique `email`
- hashed `password`
- `active`
- `must_change_password`
- `last_login_at`
- remember token and timestamps

### `provider_connections`

This requested table name represents the global provider catalogue, not tenant credentials.

- `id`
- `name`
- unique `slug`
- `adapter`
- supported-service capabilities
- `status`
- timestamps

Only platform administrators manage this catalogue.

### `parent_provider_connections`

- `id`
- `parent_business_id`
- `provider_connection_id`
- parent-visible `name`
- `base_url`
- encrypted credentials
- extensible settings
- `status`
- `last_tested_at`
- timestamps

Credentials must be encrypted at rest and omitted from serialization and logs.

### `parent_reseller_levels`

- `id`
- `parent_business_id`
- `name`
- integer `position` from 1 through 6
- `status`
- timestamps

The pair `parent_business_id + position` is unique. A parent may define between one and six levels.

### Affiliate ownership

Add nullable `parent_business_id` and `parent_reseller_level_id` to `affiliates`, then backfill and enforce their consistency. An affiliate belongs to one parent and one active level at a time. Only its parent administrator or a platform administrator may change the level.

All existing affiliates, including the current OresamSub storefront, are assigned to the OresamSub parent and its Basic reseller level.

## Product categories and plans

`product_plan_categories` remains the global platform classification catalogue.

`affiliate_product_plan_categories` remains affiliate-specific. It lets an affiliate rename a global category and control permitted presentation settings. Category and plan synchronization must be scoped through the affiliate's parent and must never import another parent's plans.

Add `parent_business_id` to `product_plans`. All existing plans are assigned to OresamSub. The existing OresamSub plan sync is explicitly restricted to that parent. Other parents initially add plans and prices manually.

### Parent plan prices

Create `product_plan_parent_prices`:

- `parent_business_id`
- `product_plan_id`
- `parent_reseller_level_id`
- decimal `selling_price`
- nullable maximum-profit setting
- timestamps

The product plan and reseller level must belong to the same parent. Composite foreign keys enforce both relationships against `parent_business_id`; the pair `product_plan_id + parent_reseller_level_id` is unique.

Seed OresamSub levels:

1. Basic
2. Bronze
3. Silver
4. Gold
5. Diamond
6. Platinum

Migrate `cost_price_1` through `cost_price_6` into these normalized price records. Retain legacy columns until all pricing paths are proven and switched.

## Affiliate customer reseller plans

`affiliate_user_plans` remains the affiliate-owned list of customer plans. An affiliate can create plans individually up to six or use a generate action that creates only missing positions. Plans can be renamed, activated, or hidden. The pair `affiliate_id + plan_level` is unique, and `plan_level` is limited to 1–6.

Each customer has one `user_plan_id` belonging to the same affiliate. Affiliate administrators control customer assignments.

The legacy database currently contains twelve levels. Migration retains levels 1–6, maps customers assigned to levels 7–12 onto level 6, and writes every reassignment to an audit report. Legacy 7–12 price fields remain temporarily but are no longer generated or displayed. They are removed only after all purchase paths use the six-level flow.

## Profit preservation

Parent scoping must preserve the current profit behavior:

- flat and percentage profit modes;
- affiliate default margins;
- plan- and category-specific profits;
- six customer-level profits;
- maximum-profit restrictions;
- custom pricing overrides;
- commission enablement, flat/percentage upline commissions, and caps;
- provider cost, parent profit, and affiliate profit reporting.

The price chain is provider cost → parent price for the affiliate's assigned level → affiliate customer-plan profit → customer price. A parent price cannot be below provider cost, and an affiliate price cannot be below its acquisition price.

The new pricing service must run in comparison mode locally and prove that representative legacy and parent-scoped calculations produce identical customer prices before switching. Upline/referral commission structures are included in the architecture, but their runtime conversion occurs only after the six-level core pricing flow is proven.

## Provider routing

Do not place a single provider ID directly on a plan because provider plan identifiers differ across primary and backup providers.

Create `product_plan_provider_routes`:

- `id`
- `parent_business_id`
- `product_plan_id`
- `parent_provider_connection_id`
- provider-specific `provider_plan_id`
- positive integer `priority`
- `is_active`
- timestamps

The product plan and connection must belong to the same parent. Composite foreign keys enforce both relationships against `parent_business_id`. Priority is unique per product plan. Priority 1 is primary. Backup routes may be configured and tested, but the first runtime release processes only priority 1.

Purchase routing is customer → affiliate → parent → parent-owned plan → priority-1 route → parent provider connection → adapter → provider API. There is no general OresamSub fallback. Missing or inconsistent ownership fails safely before processing.

## Transactions, failures, and refunds

Backfill historical transactions to OresamSub. New transactions retain immutable routing snapshots:

- `parent_business_id`
- `parent_provider_connection_id`
- `product_plan_provider_route_id`
- provider plan ID snapshot
- provider reference
- routing status
- cost and profit snapshots

Failure behavior:

- explicit provider rejection: fail and issue an automatic one-time refund;
- timeout, lost connection, or ambiguous response: mark pending requery and do not refund;
- requery confirms failure: fail and refund once;
- requery confirms success: complete without refund.

Wallet debits and refunds use database transactions, row locking, and an idempotency marker. Retrying a callback, command, or request cannot debit or refund twice.

## Hybrid funding ownership

Separate global funding-provider definitions from credential-bearing connections.

Create `funding_provider_connections` with:

- `parent_business_id`
- nullable `affiliate_id`
- global funding provider reference
- connection mode (`parent_managed` or `affiliate_managed`)
- encrypted credentials
- status (`pending`, `active`, `rejected`, or `suspended`)
- connection-specific webhook identifier
- settings and timestamps

When `affiliate_id` is null, the parent owns the shared connection. When populated, that affiliate owns the connection, which must still belong to the same parent. Affiliate-managed connections require connection testing and parent-admin approval before activation. Platform administrators may suspend unsafe connections with an audit record.

Affiliate funding options reference the selected connection and contain only permitted customer-facing visibility, charges, and settlement settings. Parent secrets are hidden from affiliates. Webhooks resolve the exact connection before crediting a wallet, and historical funding events retain the connection used.

Existing global provider names remain global. Existing credential-bearing funding configuration migrates to OresamSub-owned connections. Existing affiliate funding settings are linked to the appropriate OresamSub connection.

## Migration stages

1. Create parent, admin, provider, parent connection, and reseller-level structures.
2. Seed OresamSub, its parent admin, provider definition, primary connection, and six levels.
3. Add nullable ownership fields and backfill all existing affiliates, plans, transactions, funding records, and directly owned operational records to OresamSub.
4. Normalize parent plan prices while retaining legacy columns.
5. Consolidate affiliate customer levels 7–12 into level 6 with an audit report.
6. Create one primary OresamSub route for every existing product plan.
7. Convert sync, listing, pricing, data, airtime, cable, electricity, requery, refunds, funding, webhooks, reports, and remaining settings one feature flag at a time.
8. Convert upline/referral runtime calculations after core pricing equivalence is proven.
9. Remove legacy columns only in a later, separately approved clean-up deployment.

## Local testing and acceptance

Before production planning, local tests must prove:

- seeders and backfills are idempotent;
- dry runs leave the database unchanged;
- existing IDs and relationships remain stable;
- no cross-parent admin, affiliate, plan, price, route, transaction, or funding access is possible;
- only authorized parent/platform administrators change affiliate parent levels;
- affiliates cannot exceed six customer plans;
- generate-plans creates only missing positions;
- legacy levels 7–12 consolidate and audit correctly;
- legacy and new core prices match for representative products and customer levels;
- OresamSub sync changes only OresamSub plans;
- purchases use the correct primary route and provider plan ID;
- OresamSub URLs are never used for another parent;
- explicit failures refund once and ambiguous outcomes do not refund prematurely;
- requeries cannot double debit, fulfill, or refund;
- parent and affiliate funding credentials stay isolated;
- webhooks cannot credit a customer through the wrong connection;
- existing platform-admin and affiliate behavior remains intact while feature flags are disabled.

Production deployment is outside the local implementation scope. After local acceptance, create a separate production runbook covering backup, low-traffic migration, dry-run reports, feature-flag rollout, validation, monitoring, and rollback.

## Deferred work

- Automatic backup-provider failover; backup configuration is stored but not invoked initially.
- Gongoz, AffaTech, and other adapters until their contracts are supplied.
- Removal of legacy 7–12 and fixed price columns.
- Simplified modern-classic affiliate landing-page design, handled as a separate design and implementation phase.
