# Multi-Parent Remaining Go-Live Checklist

The core new-parent data purchase flow is operational. The following work remains before the multi-parent flow is considered fully validated.

## Required service tests

- Test airtime purchasing end to end.
- Test cable subscription purchasing end to end.
- Test electricity purchasing end to end.
- For each service, verify:
  - Request endpoint and HTTP method.
  - Request parameters and credential headers.
  - Provider plan or service identifier.
  - Customer-number mapping.
  - Success-condition evaluation.
  - Customer success message.
  - Redacted provider response for parent diagnostics.
  - Conclusive failure, settlement release and customer refund exactly once.
  - Ambiguous response reconciliation without an unsafe duplicate request.

## Production validation

- Run a legacy OresamSub data purchase after every deployment affecting routing or pricing.
- Confirm existing OresamSub affiliates still use the legacy route while their rollout flag is disabled.
- Test platform-admin impersonation into a parent workspace.
- Test parent-admin impersonation into an owned affiliate Admin account.
- Confirm cross-parent and nested impersonation are rejected.
- Import a two-plan CSV and review the preview before importing a complete catalogue.
- Verify imported plans, provider routes and all six acquisition prices.
- Enable multi-parent routing only for the selected test parent and affiliate.
- Keep OresamSub's new purchase route disabled until regression testing is complete.

## Operational monitoring

- Confirm `parent-purchases:reconcile` runs from the scheduler.
- Run the parent financial audit against successful test references.
- Confirm settlement reservation, capture and release entries balance correctly.
- Test affiliate settlement virtual-account funding.
- Verify funding webhook signatures and exactly-once wallet crediting.
- Verify duplicate webhook delivery is rejected safely.
- Review provider request and redacted response logs after each test.

## Optional improvement

- Add `.xlsx` import/export support with real dropdown cells for global categories, approved provider connections and visibility fields. CSV files cannot provide genuine spreadsheet dropdown controls. The current CSV includes readable reference names alongside IDs and validates all IDs against the signed-in parent.

## Current immediate priority

Validate airtime, cable and electricity one service at a time. Do not enable a service for real customers until its success, failure, settlement and reconciliation paths have all been verified.
