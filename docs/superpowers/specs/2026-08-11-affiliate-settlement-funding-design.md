# Affiliate Settlement Funding Design

## Scope

Add a parent-scoped business settlement wallet for every affiliate. Parent admins can manually record verified funding for affiliates they own. This balance will later fund `parent_managed` purchases, but this increment does not change live purchase execution.

Customer wallets and `affiliate_funding_provider_configs` remain independent and unchanged.

## Data model

`affiliate_settlement_wallets` contains one NGN wallet per affiliate and parent, with decimal available and reserved balances and an active/frozen status.

`affiliate_settlement_ledger_entries` is immutable. Each entry records its parent, affiliate, wallet, type, amount, before/after balances, unique idempotency reference, actor, reason and metadata.

## Manual credit flow

The authenticated parent admin selects an owned affiliate, enters a positive amount, an externally meaningful reference and a reason. A database transaction locks the wallet, rejects a duplicate reference, increases the available balance and appends the ledger entry. Cross-parent access is rejected.

New affiliate approval creates a wallet automatically; existing affiliates receive wallets lazily or through the migration backfill.

## Safety

- Money uses `decimal(18,2)`, never float arithmetic in the service.
- The ledger cannot be edited or deleted through application routes.
- A unique `(parent_business_id, reference)` constraint enforces idempotency.
- Parent ownership is checked in both controller queries and the service.
- Existing OresamSub funding and purchases remain untouched.
- No customer funding webhook can credit a settlement wallet in this increment.

## Interface

The parent affiliate workspace shows available/reserved balances, a manual credit form and paginated ledger history. Platform administrators receive read-only settlement-wallet and ledger visibility in a later operational screen; the schema already records all necessary audit data.

## Tests

Feature tests cover wallet creation, successful credit, immutable audit data, duplicate reference rejection, cross-parent rejection, invalid input and isolation from customer wallets.
