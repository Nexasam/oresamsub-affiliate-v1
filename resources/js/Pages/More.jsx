import React from "react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import { router, usePage } from "@inertiajs/react";

export default function MorePage() {
  const { props } = usePage();
  const primaryColor = props.userDashboardPrimaryColor || "#0C9246";
  const secondaryColor = props.userDashboardSecondaryColor || "#0A7036";

  // helper function to darken or lighten a color
  const adjustColor = (color, amount = -20) => {
    let usePound = false;
    if (color[0] === "#") {
      color = color.slice(1);
      usePound = true;
    }
    let num = parseInt(color, 16);
    let r = (num >> 16) + amount;
    let g = ((num >> 8) & 0x00ff) + amount;
    let b = (num & 0x0000ff) + amount;

    r = Math.min(255, Math.max(0, r));
    g = Math.min(255, Math.max(0, g));
    b = Math.min(255, Math.max(0, b));

    return (usePound ? "#" : "") + ((r << 16) | (g << 8) | b).toString(16).padStart(6, "0");
  };

  const darkerPrimary = adjustColor(primaryColor, -30);

  const ActionBox = ({ onClick, icon, label, color = primaryColor }) => {
    const gradientStyle = {
      background: `linear-gradient(135deg, ${color}, ${adjustColor(color, -30)})`,
      border: `2px solid ${color}`,
    };

    return (
      <button
        onClick={onClick}
        className="group p-3 rounded-xl transition transform hover:scale-[1.03]
                   bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 
                   flex flex-col items-center border-2"
        style={{
          borderColor: color,
          boxShadow: `0 0 6px ${color}40`,
        }}
      >
        <div
          className="w-10 h-10 mx-auto rounded-full flex items-center justify-center 
                     text-white text-xl shadow-sm border-2"
          style={{
            ...gradientStyle,
            borderColor: color,
          }}
        >
          {icon}
        </div>
        <div className="mt-2 font-medium text-[13px] md:text-sm group-hover:text-gray-900 dark:group-hover:text-white">
          {label}
        </div>
      </button>
    );
  };

  return (
    <DashboardLayout title="More">
      <div className="text-center pt-6 pb-20">
        <h2 className="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">
          More Options
        </h2>

        <div className="grid grid-cols-3 sm:grid-cols-4 gap-3 text-center text-sm md:text-base">

          {/* Airtime */}
          <ActionBox
            onClick={() => router.get(route("inertia.data.index"))}
            icon="📱"
            label="Airtime"
            color={primaryColor}
          />

          {/* Data */}
          <ActionBox
            onClick={() => router.get(route("inertia.data.index"))}
            icon="🌐"
            label="Data"
            color={primaryColor}
          />

          {/* Cable */}
          <ActionBox
            onClick={() => router.get(route("inertia.data.index"))}
            icon="📺"
            label="Cable"
            color={primaryColor}
          />

          {/* Pricing */}
          <ActionBox
            onClick={() => router.get(route("inertia.data.index"))}
            icon="💰"
            label="Pricing"
            color={secondaryColor}
          />

          {/* Language */}
          <ActionBox
            onClick={() => router.get(route("inertia.data.index"))}
            icon="🌍"
            label="Language"
            color={secondaryColor}
          />

          {/* Profile */}
          <ActionBox
            onClick={() => router.get(route("inertia.data.index"))}
            icon="👤"
            label="Profile"
            color={primaryColor}
          />

          {/* Transactions */}
          <ActionBox
            onClick={() => router.get(route("inertia.data.index"))}
            icon="💳"
            label="Transactions"
            color={primaryColor}
          />

          {/* Referrals */}
          <ActionBox
            onClick={() => router.get(route("inertia.data.index"))}
            icon="🎁"
            label="Referrals"
            color={primaryColor}
          />

          {/* Settings */}
          <ActionBox
            onClick={() => router.get(route("inertia.data.index"))}
            icon="⚙️"
            label="Settings"
            color={primaryColor}
          />

          {/* Terms & Policy */}
          <ActionBox
            onClick={() => router.get(route("inertia.data.index"))}
            icon="📄"
            label="Terms & Policy"
            color={secondaryColor}
          />

          {/* Support */}
          <ActionBox
            onClick={() => router.get(route("inertia.data.index"))}
            icon="💬"
            label="Support"
            color={secondaryColor}
          />

       {/* Logout */}
        <ActionBox
          onClick={() =>
            router.post("/logout2", {}, {
              preserveState: false,
              preserveScroll: true,
              onSuccess: () => {
                window.location.href = "/login";
              }
            })
          }
          icon="🚪"
          label="Logout"
          color="#e3342f"
        />


        </div>
      </div>
    </DashboardLayout>
  );
}
