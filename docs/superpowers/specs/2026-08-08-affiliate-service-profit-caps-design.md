# Affiliate Service Profit Caps Design

## Objective

Allow a parent administrator to set maximum customer-profit values for each of an affiliate's six customer pricing levels, independently for Data, Cable, Airtime, and Electricity. The feature extends the parent pricing workspace while preserving the distinction between parent acquisition pricing and affiliate customer pricing.

## Ownership and pricing layers

The parent administrator may view and update caps only for affiliates whose `parent_business_id` matches the authenticated parent administrator's business.

The layers remain separate:

1. Parent service defaults and plan overrides determine parent-to-affiliate acquisition pricing.
2. Affiliate service profit caps limit the affiliate's permitted customer profit.
3. `affiliate_product_plans.user_level_1_profit` through `user_level_6_profit` store the affiliate's actual customer profit settings.

An affiliate's actual customer profit must not exceed the cap for the plan's service and customer level.

## Persistence

Create a normalized `affiliate_service_profit_caps` table with:

- `parent_business_id`;
- `affiliate_id`;
- `product_id` for the global service;
- `customer_level`, constrained to 1 through 6;
- `calculation_type`, either `flat` or `percent`;
- non-negative decimal `max_value`;
- timestamps.

The combination of affiliate, product, and customer level is unique. Composite ownership constraints must ensure the affiliate belongs to the recorded parent business. Application authorization must independently enforce the same rule.

## Initial values

New affiliates receive these values for all six customer levels:

| Service | Type | Maximum |
| --- | --- | ---: |
| Data | Flat | ₦70 |
| Cable | Flat | ₦70 |
| Airtime | Percentage | 1% |
| Electricity | Percentage | 1% |

For an existing affiliate, each initial cap is the greater of the configured default and the highest existing profit for that service and customer level. This avoids making current pricing invalid during migration. Missing services do not cause duplicate global products to be created.

## Parent-admin interface

Add an **Affiliate maximum pricing** section to `/parent-admin/pricing`.

- The parent administrator selects one affiliate belonging to their business.
- The screen displays a four-service by six-customer-level matrix.
- Data and Cable values display as flat naira maximums.
- Airtime and Electricity values display as percentage maximums.
- Changing the selected affiliate loads only that affiliate's caps.
- Saving is transactional and never modifies actual affiliate plan profits.

The section must remain visually distinct from parent reseller-level acquisition prices.

## Rejection behavior

Before saving, the server compares every proposed cap with all existing `affiliate_product_plans` for the selected affiliate and matching service.

If any `user_level_N_profit` exceeds the proposed cap, the entire update is rejected with HTTP 422. The response contains structured violations including:

- affiliate product plan ID and name;
- service/product ID and name;
- customer level;
- existing profit;
- proposed maximum.

No cap or affiliate plan value changes during a rejected request. There is no automatic clamp option in this phase.

## Enforcement

Every application path that updates `user_level_1_profit` through `user_level_6_profit` must resolve and enforce the affiliate's service cap before persisting. Parent and platform administration endpoints must return validation errors rather than partially updating rows. Existing purchase calculations continue to consume the actual affiliate plan profit fields; the cap is a configuration constraint, not a new purchase-price source.

## Validation

- Customer level must be an integer from 1 through 6.
- Flat maximum must be zero or greater.
- Percentage maximum must be between 0 and 100 inclusive.
- Calculation type is derived from the service: Data/Cable are flat; Airtime/Electricity are percentage.
- Submitted affiliates, products, and cap rows must belong to the authenticated parent context.
- A request must provide exactly one cap for every supported service and customer level for the selected affiliate.

## Testing

Tests must prove:

- schema, uniqueness, relationships, decimal casts, and composite ownership;
- exact ₦70 and 1% defaults for new affiliates;
- migration-safe initial values for existing profits above those defaults;
- independent cap values across affiliates and customer levels;
- parent administrators cannot read or update another parent's affiliate;
- valid cap updates succeed atomically;
- reductions below existing profits return all structured violations and write nothing;
- affiliate plan updates at the cap succeed and updates above it fail;
- current parent-admin, multi-parent, and platform-admin regression suites remain green.

## Out of scope

- Automatically reducing existing affiliate plan profits;
- individual-plan cap overrides;
- changing live purchase routing or provider processing;
- allowing affiliates to increase their own caps;
- adding more than six customer levels.
