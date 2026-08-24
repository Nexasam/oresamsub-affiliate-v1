const NETWORK_BRANDS = {
  mtn: {
    key: "mtn",
    label: "MTN",
    logo: "/assets/landing_page_assets/img/landing/mtn.jpg",
  },
  glo: {
    key: "glo",
    label: "Glo",
    logo: "/assets/landing_page_assets/img/landing/glo.png",
  },
  airtel: {
    key: "airtel",
    label: "Airtel",
    logo: "/assets/landing_page_assets/img/landing/airtel.png",
  },
  "9mobile": {
    key: "9mobile",
    label: "9mobile",
    logo: "/assets/landing_page_assets/img/landing/9mobile.jpg",
  },
};

export function resolveTransactionReferences(transaction = {}) {
  const transactionReference = transaction.txn_reference
    ?? transaction.transaction_reference
    ?? transaction.reference
    ?? transaction.id;

  return {
    transaction: transactionReference == null ? "—" : String(transactionReference),
    provider: transaction.provider_reference ? String(transaction.provider_reference) : null,
  };
}

export function resolveNetworkBrand(networkName = "") {
  const normalized = String(networkName).trim().toLowerCase();
  const key = normalized.includes("9mobile") || normalized.includes("etisalat")
    ? "9mobile"
    : Object.keys(NETWORK_BRANDS).find((brand) => normalized.includes(brand));

  return NETWORK_BRANDS[key] ?? {
    key: normalized || "network",
    label: networkName || "Network",
    logo: null,
  };
}

const normalizeAssetUrl = (path) => {
  if (!path) return null;
  const value = String(path).trim();
  if (/^https?:\/\//i.test(value)) return value;
  return value.startsWith("/") ? value : `/${value}`;
};

export function resolveAffiliateBranding({ affiliate = null, siteLogo = null } = {}) {
  const name = affiliate?.name || "ResellGrid";
  const siteLogoUrl = siteLogo
    ? normalizeAssetUrl(`assets/landing_page_assets/img/site_logo/${siteLogo}`)
    : null;
  const affiliateIconUrl = normalizeAssetUrl(affiliate?.logo);

  return {
    name,
    initial: String(name).slice(0, 1).toUpperCase() || "R",
    logoUrl: siteLogoUrl || affiliateIconUrl,
    faviconUrl: affiliateIconUrl || siteLogoUrl || "/assets/logo_imgs/favicon/android-chrome-192x192.png",
  };
}
