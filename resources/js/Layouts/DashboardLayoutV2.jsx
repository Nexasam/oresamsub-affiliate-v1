import { useEffect, useState } from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
import {
  Activity, CircleUserRound, CreditCard, Headphones, Home, Menu,
  Moon, PackageOpen, ReceiptText, Smartphone, Sun, Wifi, X, Zap,
} from "lucide-react";
import PwaInstallPopup from "@/Components/PwaInstallPopup";
import UiVersionSwitch from "@/Components/V2/UiVersionSwitch";

const normalizeColor = (color, fallback) => {
  if (!color) return fallback;
  return color.startsWith("#") ? color : `#${color}`;
};

const navItems = [
  { label: "Home", routeName: "dashboard", icon: Home },
  { label: "Buy data", routeName: "inertia.data.index", icon: Wifi },
  { label: "Buy airtime", routeName: "inertia.airtime.index", icon: Smartphone },
  { label: "Cable TV", routeName: "inertia.cable.index", icon: PackageOpen },
  { label: "Electricity", routeName: "inertia.electricity.index", icon: Zap },
  { label: "Fund wallet", routeName: "inertia.virtual_accounts.index", icon: CreditCard },
  { label: "Transactions", routeName: "inertia.transactions.index", icon: ReceiptText },
  { label: "Pricing", routeName: "inertia.pricing.index", icon: Activity },
  { label: "Profile", routeName: "inertia.profile.index", icon: CircleUserRound },
];

const initialDarkMode = () => {
  if (typeof window === "undefined") return false;
  const stored = localStorage.getItem("theme");
  return stored ? stored === "dark" : window.matchMedia("(prefers-color-scheme: dark)").matches;
};

export default function DashboardLayoutV2({ children, title }) {
  const { props, url } = usePage();
  const { auth, affiliate, impersonator, sitename, userDashboardPrimaryColor, userDashboardSecondaryColor } = props;
  const [darkMode, setDarkMode] = useState(initialDarkMode);
  const [menuOpen, setMenuOpen] = useState(false);
  const primary = normalizeColor(userDashboardPrimaryColor, "#2563eb");
  const accent = normalizeColor(userDashboardSecondaryColor, "#14b8a6");
  const supportNumber = affiliate?.support_whatsapp_number || "2349163128718";

  useEffect(() => {
    document.documentElement.classList.toggle("dark", darkMode);
    localStorage.setItem("theme", darkMode ? "dark" : "light");
  }, [darkMode]);

  useEffect(() => setMenuOpen(false), [url]);

  const isActive = (routeName) => {
    try {
      return new URL(route(routeName), window.location.origin).pathname === window.location.pathname;
    } catch {
      return false;
    }
  };

  return (
    <div
      className="rg-v2-app"
      style={{ "--rg-brand": primary, "--rg-accent": accent }}
    >
      <Head title={`${sitename || "ResellGrid"} | ${title}`} />
      <PwaInstallPopup />

      {impersonator ? (
        <button
          type="button"
          onClick={() => impersonator.platformAdmin ? router.post(impersonator.exitUrl) : window.location.assign(impersonator.exitUrl)}
          className="rg-v2-impersonation"
        >
          Viewing {impersonator.username || impersonator.first_name}. Tap to exit impersonation.
        </button>
      ) : null}

      <aside className={`rg-v2-sidebar ${menuOpen ? "is-open" : ""}`}>
        <div className="rg-v2-brand">
          <div className="rg-v2-brand-mark">{String(sitename || "R").slice(0, 1).toUpperCase()}</div>
          <div className="min-w-0">
            <div className="truncate text-[15px] font-bold text-slate-950 dark:text-white">{sitename || "ResellGrid"}</div>
            <div className="text-xs text-slate-500 dark:text-slate-400">Customer app</div>
          </div>
          <button type="button" onClick={() => setMenuOpen(false)} className="rg-v2-icon-button ml-auto lg:hidden" aria-label="Close menu">
            <X size={19} />
          </button>
        </div>

        <nav className="rg-v2-side-nav" aria-label="Customer navigation">
          {navItems.map(({ label, routeName, icon: Icon }) => (
            <Link key={routeName} href={route(routeName)} className={`rg-v2-side-link ${isActive(routeName) ? "is-active" : ""}`}>
              <Icon size={19} strokeWidth={1.9} />
              <span>{label}</span>
            </Link>
          ))}
        </nav>

        <div className="mt-auto space-y-2 border-t border-slate-200 pt-4 dark:border-slate-800">
          <a href={`https://wa.me/${supportNumber}`} target="_blank" rel="noreferrer" className="rg-v2-side-link">
            <Headphones size={19} strokeWidth={1.9} />
            <span>Get support</span>
          </a>
          <UiVersionSwitch />
        </div>
      </aside>

      {menuOpen ? <button type="button" className="rg-v2-backdrop" onClick={() => setMenuOpen(false)} aria-label="Close navigation" /> : null}

      <div className="rg-v2-main">
        <header className="rg-v2-header">
          <div className="flex min-w-0 items-center gap-3">
            <button type="button" onClick={() => setMenuOpen(true)} className="rg-v2-icon-button lg:hidden" aria-label="Open menu">
              <Menu size={20} />
            </button>
            <div className="min-w-0">
              <div className="truncate text-xs font-medium text-slate-500 dark:text-slate-400">Welcome back, {auth.user.first_name || auth.user.username}</div>
              <h1 className="truncate text-lg font-bold tracking-tight text-slate-950 dark:text-white">{title}</h1>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <div className="hidden sm:block"><UiVersionSwitch compact /></div>
            <button type="button" onClick={() => setDarkMode(value => !value)} className="rg-v2-icon-button" aria-label="Toggle dark mode">
              {darkMode ? <Sun size={18} /> : <Moon size={18} />}
            </button>
            <Link href={route("inertia.profile.index")} className="rg-v2-avatar" aria-label="Open profile">
              {(auth.user.first_name || auth.user.username || "U").slice(0, 1).toUpperCase()}
            </Link>
          </div>
        </header>

        <main className="rg-v2-content">{children}</main>
      </div>

      <nav className="rg-v2-bottom-nav" aria-label="Mobile navigation">
        {[
          { label: "Home", routeName: "dashboard", icon: Home },
          { label: "Data", routeName: "inertia.data.index", icon: Wifi },
          { label: "Fund", routeName: "inertia.virtual_accounts.index", icon: CreditCard },
          { label: "Activity", routeName: "inertia.transactions.index", icon: ReceiptText },
          { label: "More", routeName: "inertia.more.index", icon: Menu },
        ].map(({ label, routeName, icon: Icon }) => (
          <Link key={routeName} href={route(routeName)} className={`rg-v2-bottom-link ${isActive(routeName) ? "is-active" : ""}`}>
            <Icon size={20} strokeWidth={isActive(routeName) ? 2.4 : 1.8} />
            <span>{label}</span>
          </Link>
        ))}
      </nav>
    </div>
  );
}
