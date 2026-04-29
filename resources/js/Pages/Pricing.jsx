import React, { useState } from "react";
import { usePage } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import PrimaryLink from "@/Components/PrimaryLink";

export default function Pricing({ productPlans = [] }) {
  const [expanded, setExpanded] = useState('data');
  const { props } = usePage();

  // Brand colors
  const networkColors = {
    MTN: "#fbbf24",
    AIRTEL: "#ef4444",
    GLO: "#10b981",
    "9MOBILE": "#22c55e",
    GOTV: "#f59e0b",
    DSTV: "#3b82f6",
    STARTIMES: "#9333ea",
    PREPAID: "#2563eb",
    DEFAULT: "#3b82f6",
  };

  // Display order for networks within each category
  const displayOrder = {
    data: ["MTN", "GLO", "AIRTEL", "9MOBILE"],
    airtime: ["MTN", "GLO", "AIRTEL", "9MOBILE"],
    cable_subscription: ["GOTV", "DSTV", "STARTIMES"],
    electricity: ["PREPAID"],
    utility_bills: ["PREPAID"],
  };

  // Group by category and network
  const grouped = productPlans.reduce((acc, plan) => {
    const category = plan.category?.toLowerCase() || "others";
    const network = plan.network?.toUpperCase() || "OTHER";

    acc[category] = acc[category] || {};
    acc[category][network] = acc[category][network] || [];
    acc[category][network].push(plan);

    return acc;
  }, {});

  // Plan card
  const PlanCard = ({ plan, color, category }) => {
    // For airtime and utility_bills, show "% discount" literally
    const showStaticDiscount = ["airtime", "utility_bills"].includes(category);
    const valueDisplay = showStaticDiscount
      ? `${Number(plan.selling_price).toLocaleString()}% discount`
      : `₦${Number(plan.selling_price).toLocaleString()}`;

    return (
      <div
        className="p-4 rounded-xl border shadow-sm bg-white dark:bg-gray-800 hover:shadow-md hover:scale-[1.02] transition"
        style={{ borderColor: color }}
      >
        <div className="font-semibold text-sm text-gray-900 dark:text-gray-100">
          {plan.product_plan_name}
        </div>

        <div
          className={`mt-2 font-bold ${
            showStaticDiscount ? "text-sm" : "text-lg"
          }`}
          style={{ color }}
        >
          {valueDisplay}
        </div>
      </div>
    );
  };

  // Category accordion section
  const CategorySection = ({ title, networks }) => {
    const colors = {
      data: "#0ea5e9",
      airtime: "#10b981",
      cable_subscription: "#f59e0b",
      electricity: "#8b5cf6",
      utility_bills: "#2563eb",
      others: "#64748b",
    };
    const color = colors[title] || "#64748b";

    const order = displayOrder[title] || Object.keys(networks);

    // Title prettification
    const displayTitle =
      title === "utility_bills"
        ? "Utility Bills"
        : title === "cable_subscription"
        ? "Cable Subscription"
        : title.charAt(0).toUpperCase() + title.slice(1);

    return (
      <div className="rounded-2xl overflow-hidden mb-6 border dark:border-gray-700">
        <button
          onClick={() => setExpanded(expanded === title ? null : title)}
          className="w-full flex justify-between items-center px-4 py-3 font-semibold text-white"
          style={{
            background: `linear-gradient(90deg, ${color}, #00000033)`,
          }}
        >
          <span>{displayTitle} Plans</span>
          <span>{expanded === title ? "−" : "+"}</span>
        </button>

        {expanded === title && (
          <div className="p-4 bg-gray-50 dark:bg-gray-900">
            {order.map((network) => {
              const plans = networks[network];
              if (!plans) return null;
              return (
                <div key={network} className="mb-5">
                  <h3
                    className="font-semibold mb-2 text-sm uppercase tracking-wide"
                    style={{
                      color: networkColors[network] || networkColors.DEFAULT,
                    }}
                  >
                    {network}
                  </h3>
                  <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    {plans.map((plan) => (
                      <PlanCard
                        key={plan.product_plan_id}
                        plan={plan}
                        color={networkColors[network] || networkColors.DEFAULT}
                        category={title}
                      />
                    ))}
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    );
  };

  return (
    <DashboardLayout title="Pricing">
      <div className="pt-6 pb-20 px-4 space-y-4">
        {/* ✅ Back to Dashboard Button */}
        <div className="flex justify-start mb-3">
          <PrimaryLink
            href={route("dashboard")}
            primaryColor={props.userDashboardPrimaryColor}
          >
            Back to Dashboard
          </PrimaryLink>
        </div>

        <h2 className="text-xl font-bold text-center text-gray-900 dark:text-gray-100 mb-4">
          Pricing Overview
        </h2>

        {Object.entries(grouped).map(([category, networks]) => (
          <CategorySection
            key={category}
            title={category}
            networks={networks}
          />
        ))}
      </div>
    </DashboardLayout>
  );
}
