import React, { useState } from "react";
import { Link } from "@inertiajs/react";

export default function MoreMenu() {
  const [open, setOpen] = useState(false);

  const menuItems = [
    { label: "Airtime to Cash", icon: "💵", route: "inertia.data.index" },
    { label: "Travels", icon: "✈️", route: "inertia.data.index" },
    { label: "Commissions", icon: "💰", route: "inertia.data.index" },
    { label: "Settings", icon: "⚙️", route: "inertia.data.index" },
  ];

  return (
    <div className="relative">
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="flex flex-col items-center px-2 py-1 focus:outline-none"
        aria-expanded={open}
        aria-label="More"
      >
        <div className="text-xl">⋯</div>
        <span className="text-[11px]">More</span>
      </button>

      {open && (
        <div
          className="absolute bottom-12 right-0 mb-2 w-44 bg-white dark:bg-gray-800 
                     border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg py-2 z-50"
        >
          {menuItems.map((item) => (
            <Link
              key={item.label}
              href={route(item.route)}
              className="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              onClick={() => setOpen(false)}
            >
              <span className="text-lg">{item.icon}</span>
              <span className="text-sm">{item.label}</span>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
