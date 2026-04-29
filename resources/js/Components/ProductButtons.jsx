import { router } from "@inertiajs/react";
import { Link, usePage } from "@inertiajs/react";
import ButtonBox from "@/Components/ButtonBox";

export default function ProductButtons({ loggingOut, setLoggingOut, primaryColor = "#0d6efd" }) {
  const { props } = usePage();

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

  const ActionBox = ({ onClick, icon, label, color = "#0C9246" }) => {
    const gradientStyle = {
      background: `linear-gradient(135deg, ${color}, ${adjustColor(color, -30)})`,
      border: `2px solid ${color}`,
    };

    return (
      <button
        onClick={onClick}
        className="group p-3 rounded-xl shadow transition transform hover:scale-[1.05] 
                   bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 
                   flex flex-col items-center border-2"
        style={{
          borderColor: color,
          boxShadow: `0 0 6px ${color}30`,
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
    <div className="grid grid-cols-3 sm:grid-cols-4 gap-3 text-center text-sm md:text-base mt-4">
      {/* Airtime */}
      <ButtonBox
        href={route("inertia.airtime.index")}
        icon="📞"
        label="Airtime"
        primaryColor={props.userDashboardPrimaryColor}
      />

      {/* Data */}
      <ButtonBox
        href={route("inertia.data.index")}
        icon="📶"
        label="Data"
        primaryColor={props.userDashboardPrimaryColor}
      />

      {/* Power */}
      <ButtonBox
        href={route("inertia.electricity.index")}
        icon="⚡"
        label="Utility Bills"
        primaryColor={props.userDashboardPrimaryColor}
      />

      {/* Cable */}
      <ButtonBox
        href={route("inertia.cable.index")}
        icon="📺"
        label="Cable"
        primaryColor={props.userDashboardPrimaryColor}
      />

      {/* ✅ Pricing */}
      <ButtonBox
        href={route("inertia.pricing.index")}
        icon="💰"
        label="Pricing"
        primaryColor={props.userDashboardPrimaryColor}
      />

      {/* Transactions */}
      <ActionBox
        onClick={() => router.get(route("inertia.transactions.index"))}
        icon="🧾"
        label="Transactions"
        color={props.userDashboardPrimaryColor}
      />

      {/* Logout */}
      <ActionBox
        onClick={() => {
          if (!loggingOut) {
            setLoggingOut(true);
            router.post("/logout2", {}, { replace: true, preserveState: false });
          }
        }}
        icon="🚪"
        label={loggingOut ? "Logging out…" : "Logout"}
        color="#e3342f"
      />
    </div>
  );
}
