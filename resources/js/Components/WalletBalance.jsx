import { useState } from "react";
import { Link } from "@inertiajs/react";

export default function WalletBalance({ user, balanceColor }) {
  const [showBalance, setShowBalance] = useState(true);
  

  // Ensure color always starts with "#" if it's a hex code
  const validColor = balanceColor?.startsWith("#")
    ? balanceColor
    : `#${balanceColor || "0c9246"}`;

  return (
    <div
      className="relative text-white p-4 rounded-xl shadow-md flex items-center justify-between"
      style={{ backgroundColor: validColor }}
    >
      <div>
        <p className="text-xs text-white/70 font-medium">Wallet Balance</p>
        <div className="flex items-center space-x-1 text-xl font-bold">
          {showBalance ? (
            <span>
              ₦
              {Number(user.main_wallet).toLocaleString("en-NG", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              })}
            </span>
          ) : (
            <span className="tracking-widest">•••••</span>
          )}
          <button
            onClick={() => setShowBalance((prev) => !prev)}
            className="ml-2 hover:text-white/90 transition"
            title="Toggle Balance"
          >
            {showBalance ? "🙈" : "👁️"}
          </button>
        </div>
      </div>
      <Link
        href={route("inertia.virtual_accounts.index")}
        className="text-sm font-semibold underline hover:text-white/90 transition"
      >
        + Top Up
      </Link>
    </div>
  );
}
