import test from "node:test";
import assert from "node:assert/strict";

import { resolveTransactionReferences, resolveNetworkBrand } from "../../resources/js/Components/V2/customerUiPresentation.js";

test("receipt uses the purchase reference instead of the plan api id", () => {
  const references = resolveTransactionReferences({
    id: 91,
    api_id: "281",
    txn_reference: "AIRTIME_20260820_175000_ABC",
    provider_reference: "ORE-88210",
  });

  assert.deepEqual(references, {
    transaction: "AIRTIME_20260820_175000_ABC",
    provider: "ORE-88210",
  });
});

test("receipt falls back safely for legacy transactions", () => {
  assert.deepEqual(resolveTransactionReferences({ id: 91, api_id: "281" }), {
    transaction: "91",
    provider: null,
  });
});

test("network aliases resolve to local logo assets", () => {
  assert.equal(resolveNetworkBrand("MTN").logo, "/assets/landing_page_assets/img/landing/mtn.jpg");
  assert.equal(resolveNetworkBrand("Glo Mobile").key, "glo");
  assert.equal(resolveNetworkBrand("AIRTEL").key, "airtel");
  assert.equal(resolveNetworkBrand("9mobile").key, "9mobile");
});

