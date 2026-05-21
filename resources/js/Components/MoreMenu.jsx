import React, { useState } from "react";
import { Link } from "@inertiajs/react";

export default function MoreMenu() {
  const [open, setOpen] = useState(false);

  const menuItems = [
    { label: "Profile Settings", hint: "Profile", route: "inertia.profile.index" },
    { label: "Virtual Accounts", hint: "Banks", route: "inertia.virtual_accounts.index" },
    { label: "Transactions", hint: "History", route: "inertia.transactions.index" },
  ];

  return (
    <div className="relative">
      <button
        type="button"
        onClick={() => setOpen((value) => !value)}
        className="flex flex-col items-center px-2 py-1 focus:outline-none"
        aria-expanded={open}
        aria-label="More"
      >
        <div className="text-xl">...</div>
        <span className="text-[11px]">More</span>
      </button>

      {open && (
        <div className="absolute bottom-12 right-0 mb-2 w-48 rounded-xl border border-gray-200 bg-white py-2 shadow-lg dark:border-gray-700 dark:bg-gray-800">
          {menuItems.map((item) => (
            <Link
              key={item.label}
              href={route(item.route)}
              className="flex items-center justify-between gap-3 px-4 py-2 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700"
              onClick={() => setOpen(false)}
            >
              <span className="text-sm font-medium">{item.label}</span>
              <span className="text-[10px] font-semibold uppercase tracking-wide text-gray-400">
                {item.hint}
              </span>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
