# Parent-Managed Manual Cable and Electricity Design

## Scope

For controlled multi-parent affiliates, cable and electricity purchases are accepted as manual pending transactions. No vending request is sent automatically. Existing legacy OresamSub behavior remains unchanged whenever the parent-managed feature or affiliate rollout is disabled.

## Customer validation

Each `cable_subscription` and `utility_bills` product configuration may contain a separate `validation` operation with:

- endpoint and HTTP method;
- request parameters and headers using the existing runtime, credential and literal mapping types;
- success conditions and success/failure message paths;
- customer-name path and optional address path.

Cable validation supplies `smartcard_number`, plan and service-provider runtime values. Electricity validation supplies `meter_number`, meter type, plan and service-provider runtime values. The generic provider client executes validation without invoking the vending endpoint and returns a normalized, redacted result. Connection changes require the existing platform reapproval flow.

## Pending purchase flow

An eligible request must use one cable slot/smartcard or one electricity meter and the main wallet. The application validates the supplied customer identifier through the approved parent connection, calculates the unified customer/acquisition prices, debits the customer wallet, reserves the affiliate settlement amount, and creates one transaction with `routing_status = manual_pending` and status pending. It does not call the purchase executor.

The transaction stores the validated customer name/address and normal financial snapshots. Customer-facing responses clearly say the request is pending manual processing.

## Manual completion

Only the owning parent admin may complete a `manual_pending` transaction:

- Success captures the settlement reservation and retains the customer debit.
- Failure releases the settlement reservation and refunds the customer exactly once.

Repeated or conflicting completion attempts do not duplicate captures, releases or refunds. Parent admins cannot operate on another parent's transaction.

## Compatibility and verification

Legacy validation and purchases are untouched outside the controlled gate. Tests cover configurable validation, pending creation, no provider vending call, cable/electricity pricing, insufficient balances, parent scoping, success/failure completion, idempotency and legacy fallback.
