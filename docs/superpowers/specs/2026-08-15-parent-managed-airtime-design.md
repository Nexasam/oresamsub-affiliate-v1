# Parent-Managed Airtime Processing Design

## Objective

Complete automatic airtime processing for multi-parent affiliates using the same pricing, settlement, provider-routing and exactly-once financial safeguards already proven by the parent-managed data flow.

## Initial scope

- One phone number per request.
- Main customer wallet only.
- Parent-managed affiliates whose processing profile is active and uses the multi-parent engine.
- Affiliates enabled by the existing provider-routing rollout control.
- Configurable primary provider route only; backup routing remains inactive.
- Existing OresamSub and non-enabled affiliate airtime requests continue through the legacy flow unchanged.

Bulk or comma-separated phone numbers are not included in the new route. They remain on the legacy path until separately designed and tested.

## Routing decision

The airtime controller will select the new flow only when all of these are true:

1. `PARENT_MANAGED_PURCHASES_ENABLED` is enabled.
2. The selected affiliate plan is enabled by the provider-routing rollout service.
3. The affiliate processing profile is active.
4. The processing engine is `multi_parent`.
5. The management mode is `parent_managed`.

If any condition is false, the existing legacy airtime implementation runs without modification.

## Purchase flow

1. Validate the network, phone number, plan, PIN, amount and wallet selection.
2. Reject the controlled parent-managed route unless exactly one phone number and the main wallet are selected.
3. Resolve the authenticated customer's reseller level.
4. Resolve the airtime affiliate plan and verify it belongs to the active affiliate.
5. Calculate the customer's airtime charge using the existing percentage-discount rule.
6. Generate one unique `AIRTIME` transaction reference.
7. Call the shared parent-managed purchase orchestrator with:
   - `service = airtime`
   - requested airtime amount
   - customer price after discount
   - phone number
   - network name
   - provider plan identifier where configured
   - generated reference
8. The route resolver selects the plan's active primary route, parent provider connection and configurable adapter.
9. The provider client builds the airtime-specific payload and headers from the connection's `product_configs.airtime` configuration.

## Financial behavior

Before the provider request:

- Verify the customer has the discounted selling amount.
- Verify the affiliate settlement wallet has the resolved affiliate acquisition amount.
- Deduct/reserve the customer amount exactly once.
- Reserve the affiliate settlement amount exactly once.
- Store provider cost, affiliate acquisition cost, customer price, parent profit and affiliate profit snapshots.

On provider success:

- Capture the settlement reservation once.
- Keep the customer debit.
- Mark the transaction successful.
- Store the provider reference and redacted provider response.

On conclusive provider failure:

- Release the settlement reservation once.
- Refund the customer once.
- Mark the transaction failed/refunded according to the existing parent-managed state machine.
- Preserve the safe failure reason for the parent admin.

On timeout or ambiguous response:

- Keep the transaction pending reconciliation.
- Do not send a duplicate vending request.
- Do not immediately refund the customer.
- Let the scheduled reconciliation command requery using the original reference when configured.

## API response behavior

- Success returns the current customer-compatible success structure and provider message.
- Conclusive failure returns a safe customer message without credentials or raw provider diagnostics.
- Reconciliation-required responses clearly state that the transaction is pending review.
- Provider responses remain visible only as redacted diagnostics in authorized transaction views.

## Backward compatibility

- The legacy OresamSub endpoint, wallet logic and pending-airtime command remain available for legacy traffic.
- The new branch executes before the legacy wallet loop, preventing double customer deductions or duplicate transaction creation.
- Disabling either the global feature flag or the affiliate rollout immediately returns that affiliate to the legacy implementation.
- No existing database column is removed or repurposed.

## Automated verification

Tests will cover:

- Successful automatic airtime purchase and financial snapshots.
- Correct percentage discount/customer price.
- Correct parent and affiliate profit calculation.
- Conclusive failure with exactly-one settlement release and customer refund.
- Ambiguous timeout with reconciliation and no immediate refund.
- Incorrect PIN rejection before any financial mutation.
- Insufficient customer wallet rejection.
- Insufficient affiliate settlement wallet rejection.
- Multiple phone numbers rejected by the controlled parent-managed route.
- Cross-affiliate plan rejection.
- Legacy fallback when the feature or rollout is disabled.
- Airtime-specific endpoint, mapping, success condition and response extraction.

## Production rollout

1. Keep the global and OresamSub rollout flags unchanged during deployment.
2. Configure and approve the test parent's airtime endpoint and mapping.
3. Map the airtime plan to the correct provider route and external identifier.
4. Enable airtime routing only for the test affiliate.
5. Run one low-value success test.
6. Run one controlled conclusive-failure test and confirm exactly-one refund/release.
7. Confirm reconciliation scheduling without intentionally timing out a customer-funded live purchase.
8. Run a legacy OresamSub airtime regression transaction.
