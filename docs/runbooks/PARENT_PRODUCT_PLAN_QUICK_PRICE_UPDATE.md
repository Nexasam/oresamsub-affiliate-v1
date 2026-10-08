# Parent Product Plan Quick Price Update

This guide documents the text format and review process for bulk-creating or updating parent product plans from **Parent Admin > Product Plans > Quick price update**.

## Required text format

Use one plan per line. Separate the four fields with a pipe (`|`) and include the header:

```text
Plan name | api_id | cost price | selling price | data size MB | validity days
75MB AIRTEL CG (1 DAY) | 25 | 70.50 | 74.00 | 75 | 1
110MB AIRTEL CG (1 DAY) | 35 | 94.00 | 99.00 | 110 | 1
```

Field definitions:

| Field | Meaning |
| --- | --- |
| `Plan name` | Customer-facing product plan name. |
| `api_id` | The provider external plan ID, such as `205`. It is stored on the selected provider route, not as the plan's internal reference. It must be unique within the pasted batch. |
| `cost price` | Parent acquisition price. This is saved as both cost price and admin cost. |
| `selling price` | Base selling price from the source list. The value entered in **Add to pasted selling price** is added to this amount. |
| `data size MB` | Optional equivalent size in MB. The importer also derives this from plan names; `1 GB` is treated as `1000 MB`. |
| `validity days` | Optional validity period in days. The importer also recognizes day values, `WEEKLY` as 7, and `MONTHLY` as 30 in plan names. |

Example: a pasted selling price of `74.00` with an added amount of `2.00` produces a final selling price of `76.00`.

For newly created plans, the internal reference is generated as `{parent/affiliate-owner slug}-{provider external plan ID}`. For example, external ID `205` under the `tommyt` parent becomes `tommyt-205`. Existing plans keep their current internal references so active customer API integrations are not disrupted.

## Update procedure

1. Open **Parent Admin > Product Plans** and find **Quick price update**.
2. Select the product, network, and product plan category. Every pasted row is assigned to this category.
3. Select the provider connection. Each pasted `api_id` is saved as that provider's external plan ID.
4. Enter the amount to add to each pasted selling price, such as `2.00`.
5. Choose whether the plans should be available to affiliates and publicly visible.
6. Paste the formatted records and select **Preview price changes**.
7. Review whether each row is marked **Create** or **Update**.
8. Correct any plan name, provider external plan ID, cost/admin cost, or final selling price directly in the preview.
9. Select **Confirm and save changes**, then approve the confirmation prompt.

Nothing is saved before the final confirmation. The preview rejects duplicate API IDs and any selling price that is not greater than its cost price.

## Size and validity correction only

Use **Correction only: update size and validity** when plans have already been imported and only their metadata needs correction. The provider connection, category, and provider external plan ID must match the existing plans.

In this mode:

- Only `data_size_in_mb` and `validity_in_days` are updated.
- Names, internal references, costs, selling prices, visibility, reseller-level pricing, and provider routes remain unchanged.
- Missing provider external plan IDs are shown as **Create** and create complete plans after confirmation.
- Size and validity remain editable in the preview before confirmation.
- Explicit `data size MB` and `validity days` columns take precedence over values derived from the plan name.
- Repeated provider external plan IDs in the same paste are ignored after their first occurrence and shown as one preview warning.

## Pricing behavior

- The final selling price is assigned to every active parent reseller pricing level.
- Maximum profit is recalculated as `final selling price - cost price` for every active level.
- Existing plans are matched by parent, selected plan category, selected provider connection, and provider external plan ID.
- Existing plans are updated and enabled. Their primary provider route is enabled when one exists.
- Missing provider external plan IDs create new parent plans and primary routes for the selected provider connection.
- New plans receive an internal reference in the `{parent-slug}-{provider-external-plan-id}` format.
- Do not mix products from different categories in one import. For example, an airtime plan must not be imported into an Airtel data category.
- The preview expires after 30 minutes. Generate a new preview if it expires.

## Airtel 50-record example

The source list below includes one airtime record, `AIRTEL Virtual Top Up.`. Remove that row when importing the remaining records into an Airtel data category.

```text
Plan name | api_id | cost price | selling price
75MB AIRTEL CG (1 DAY) | 25 | 70.50 | 74.00
110MB AIRTEL CG (1 DAY) | 35 | 94.00 | 99.00
200MB AIRTEL CG (2 DAYS) | 28 | 94.00 | 99.00
230MB AIRTEL GIFTING (2 DAYS) | 190 | 196.00 | 200.00
250MB AIRTEL CG (1 DAY) | 6 | 47.00 | 50.00
300MB AIRTEL CG (2 DAYS) | 265 | 300.00 | 400.00
300MB AIRTEL CG (2 DAYS) | 15 | 282.00 | 298.00
500MB AIRTEL CG (1 DAY) | 3 | 329.00 | 347.00
500MB AIRTEL CG (7 DAYS) | 33 | 470.00 | 496.00
500MB AIRTEL GIFTING (7 DAYS) | 295 | 490.00 | 550.00
500MB AIRTEL CG (MONTHLY) | 203 | 500.00 | 560.00
1GB AIRTEL CG (1 DAY) | 24 | 470.00 | 496.00
AIRTEL Virtual Top Up. | 284 | 2.00 | 1.00
1GB AIRTEL AWOOF SOCIAL (3DAYS) | 280 | 294.00 | 300.00
1GB AIRTEL-AWOOF SOCIAL (3 DAYS) | 279 | 320.00 | 390.00
1GB AIRTEL CG (7 DAYS) | 32 | 752.00 | 794.00
1GB AIRTEL CG (WEEKLY) | 255 | 783.00 | 790.00
1.5GB AIRTEL CG (7 DAYS) | 4 | 470.00 | 496.00
1.5GB AIRTEL CG (7 DAYS) | 13 | 940.00 | 992.00
2GB AIRTEL CG (2 DAYS) | 5 | 564.00 | 595.00
2GB AIRTEL GIFTING (MONTHLY) | 286 | 1475.00 | 1490.00
3GB AIRTEL CG (2 DAYS) | 20 | 705.00 | 744.00
3GB AIRTEL CG (30 DAYS) | 21 | 1880.00 | 1984.00
3.2GB AIRTEL CG (7 DAYS) | 18 | 470.00 | 496.00
4GB AIRTEL CG (2 DAYS) | 14 | 940.00 | 992.00
4GB AIRTEL CG (7 DAYS) | 11 | 1410.00 | 1488.00
4GB AIRTEL CG (30 DAYS) | 27 | 2350.00 | 2480.00
5GB AIRTEL CG (30 DAYS) | 208 | 4750.00 | 4850.00
5GB AIRTEL CG (30 DAYS) | 262 | 4200.00 | 4400.00
6GB AIRTEL CG (2 DAYS) | 34 | 1410.00 | 1488.00
6GB AIRTEL CG (7 DAYS) | 19 | 1880.00 | 1984.00
7GB AIRTEL AWOOF (7DAYS) | 206 | 2150.00 | 2210.00
8GB AIRTEL CG (7 DAYS) | 2 | 2350.00 | 2480.00
8GB AIRTEL CG (30 DAYS) | 10 | 2820.00 | 2976.00
10GB AIRTEL CG (7 DAYS) | 1 | 2820.00 | 2976.00
10GB AIRTEL AWOOF (30 DAYS) | 207 | 3300.00 | 3400.00
10GB AIRTEL CG (30 DAYS) | 16 | 3760.00 | 3968.00
13GB AIRTEL AWOOF (30DAYS) | 209 | 5100.00 | 5300.00
13GB AIRTEL CG (30 DAYS) | 31 | 4700.00 | 4960.00
18GB AIRTEL CG (30 DAYS) | 22 | 5640.00 | 5952.00
20GB AIRTEL CG (7 DAYS) | 36 | 4700.00 | 4960.00
25GB AIRTEL CG (30 DAYS) | 17 | 7520.00 | 7936.00
35GB AIRTEL CG (30 DAYS) | 30 | 9400.00 | 9920.00
60GB AIRTEL CG (30 DAYS) | 29 | 14100.00 | 14880.00
100GB AIRTEL CG (30 DAYS) | 12 | 18800.00 | 19840.00
160GB AIRTEL CG (30 DAYS) | 23 | 28200.00 | 29760.00
210GB AIRTEL CG (30 DAYS) | 7 | 37600.00 | 39680.00
300GB AIRTEL CG (90 DAYS) | 8 | 47000.00 | 49600.00
350GB AIRTEL CG (120 DAYS) | 9 | 56400.00 | 59520.00
685GB AIRTEL CG (365 DAYS) | 26 | 94000.00 | 99200.00
```

The airtime row has a selling price below its cost price and will fail validation even in the correct airtime category. Correct its prices before previewing it separately.
