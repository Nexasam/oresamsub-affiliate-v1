# Parent Default Profit Rules and Pricing Filters

## Objective

Allow each parent business to define default profit rules for every active reseller level and service type. Parent product plans inherit the appropriate rule unless the parent admin deliberately creates a plan-level override. Add server-side filters to the parent pricing matrix so the complete parent catalogue remains searchable while paginated.

This feature configures the future multi-parent pricing source. It does not switch the live purchase-processing flow.

## Pricing hierarchy

The effective rule is resolved in this order:

1. A plan-and-reseller-level custom override, when present.
2. The parent-and-reseller-level default for the plan's service type.
3. The platform's initial safe defaults when a parent rule has not yet been customized.

The initial defaults for every reseller level are:

| Service type | Calculation | Value |
| --- | --- | ---: |
| Data | Flat profit | ₦50 |
| Cable | Flat profit | ₦50 |
| Airtime | Percentage discount | 1% |
| Electricity | Percentage discount | 1% |

For fixed-price Data and Cable plans, flat profit is added to the provider cost. For amount-based Airtime and Electricity purchases, the percentage is a discount: a 1% rule makes a ₦1,000 purchase cost the affiliate ₦990.

Defaults are independent for each reseller level. For example, a parent may configure Bronze Data at ₦50, Silver Data at ₦40 and Gold Data at ₦30.

## Persistence design

Introduce a normalized parent default-profit table. Each record belongs to exactly one parent business, one parent reseller level and one platform service/product. It stores:

- calculation type: `flat` or `percent_discount`;
- non-negative decimal value;
- timestamps.

The parent, reseller level and service combination must be unique. Database constraints and application authorization must prevent cross-parent level references.

Extend normalized plan-level parent pricing so a record can distinguish inherited pricing from a deliberate custom override. An override stores the calculation type and value needed to resolve the plan's effective rule. Existing explicit normalized prices are preserved as custom overrides during migration; no current OresamSub price is silently replaced by a default.

The implementation plan must reconcile the existing required `selling_price` and optional `max_profit` columns with inherited rules without losing historical values. Transaction snapshots remain unchanged.

## Parent-admin interface

Add a **Default profit settings** section above the Plan price matrix.

- Rows are Data, Cable, Airtime and Electricity.
- Columns are the parent's active reseller levels, up to six.
- Data and Cable fields use flat naira profit.
- Airtime and Electricity fields use percentage discount.
- Saving changes only the authenticated parent's defaults.
- Changing a default immediately affects every inheriting plan but never overwrites custom plan overrides.
- When a reseller level is created, missing service defaults are generated from the platform initial values.

Each plan/level cell in the price matrix shows whether it is using the parent default or a custom override, along with an effective pricing preview. The parent admin can choose **Customize** to create an override or **Use default** to remove it.

## Filters and pagination

The Plan price matrix provides these combinable filters:

- plan-name search;
- service type;
- network or category;
- pricing status: all, inherited default or custom override.

Filtering is performed by the server against the authenticated parent's complete catalogue before pagination. It must not filter only the 50 plans already loaded in the browser. Changing any filter returns to page one. Pagination continues to show the filtered total and range.

## Validation and safety

- Flat profit values must be numeric and cannot be negative.
- Percentage discounts must be numeric and between 0 and 100 inclusive.
- Fixed-plan effective prices cannot be lower than provider cost.
- Reseller levels, defaults, plans and overrides must belong to the authenticated parent.
- Updates use transactions where multiple rows are changed.
- Removing a custom override restores inheritance; it does not copy the current default into the plan.
- Existing OresamSub normalized prices remain explicit overrides after migration.
- The current live purchase and legacy profit flow remain untouched until the separately planned purchase-processing conversion.

## Testing

Feature and unit tests will cover:

- automatic initial defaults for each active reseller level;
- independent defaults between parents and reseller levels;
- validation of flat and percentage rules;
- default inheritance and effective-value calculation;
- plan overrides and reverting to defaults;
- preservation of existing explicit OresamSub prices;
- all four filters across records beyond the first pagination page;
- combinations of filters and accurate pagination totals;
- cross-parent access rejection;
- no regression in current parent-admin, multi-parent and platform-admin suites.

## Out of scope

- Activating the normalized rules in live purchase processing;
- backup-provider routing;
- changing affiliate or customer pricing screens;
- historical transaction recalculation;
- allowing affiliates to modify parent defaults.
