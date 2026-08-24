import test from "node:test";
import assert from "node:assert/strict";

import {
  resolveAffiliateBranding,
  resolveTransactionReferences,
  resolveNetworkBrand,
} from "../../resources/js/Components/V2/customerUiPresentation.js";
import { resolveInstallMode } from "../../resources/js/Components/V2/pwaInstallState.js";

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

test("affiliate branding uses the configured site logo and business favicon", () => {
  assert.deepEqual(resolveAffiliateBranding({
    affiliate: { name: "Emiplug", logo: "uploads/affiliates/emiplug-icon.png" },
    siteLogo: "emiplug-logo.png",
  }), {
    name: "Emiplug",
    initial: "E",
    logoUrl: "/assets/landing_page_assets/img/site_logo/emiplug-logo.png",
    faviconUrl: "/uploads/affiliates/emiplug-icon.png",
  });
});

test("affiliate branding falls back safely when only one or no asset exists", () => {
  assert.equal(resolveAffiliateBranding({ siteLogo: "brand.png" }).faviconUrl, "/assets/landing_page_assets/img/site_logo/brand.png");
  assert.deepEqual(resolveAffiliateBranding({ affiliate: { name: "Acme" } }), {
    name: "Acme",
    initial: "A",
    logoUrl: null,
    faviconUrl: "/assets/logo_imgs/favicon/android-chrome-192x192.png",
  });
});

test("PWA install mode explains the actual browser state", () => {
  assert.equal(resolveInstallMode({ standalone: true }), "installed");
  assert.equal(resolveInstallMode({ ios: true }), "ios");
  assert.equal(resolveInstallMode({ secureContext: false }), "insecure");
  assert.equal(resolveInstallMode({ serviceWorkerSupported: false }), "unsupported");
  assert.equal(resolveInstallMode({ promptAvailable: false }), "manual");
  assert.equal(resolveInstallMode({ promptAvailable: true }), "install");
});
