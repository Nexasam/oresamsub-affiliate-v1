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

