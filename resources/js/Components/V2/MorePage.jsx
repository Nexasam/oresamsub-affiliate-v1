import { Link, router } from "@inertiajs/react";
import {
  Activity, CircleUserRound, CreditCard, Headphones, LogOut, PackageOpen,
  ReceiptText, Smartphone, Wifi, Zap,
} from "lucide-react";
import InstallAppButton from "@/Components/V2/InstallAppButton";
import UiVersionSwitch from "@/Components/V2/UiVersionSwitch";

const services = [
  { label: "Data", note: "Buy data bundles", routeName: "inertia.data.index", icon: Wifi },
  { label: "Airtime", note: "Top up any network", routeName: "inertia.airtime.index", icon: Smartphone },
  { label: "Cable TV", note: "Renew subscriptions", routeName: "inertia.cable.index", icon: PackageOpen },
  { label: "Electricity", note: "Pay meter bills", routeName: "inertia.electricity.index", icon: Zap },
];

const accountLinks = [
  { label: "Fund wallet", note: "Accounts and funding history", routeName: "inertia.virtual_accounts.index", icon: CreditCard },
  { label: "Transactions", note: "Track purchases and refunds", routeName: "inertia.transactions.index", icon: ReceiptText },
  { label: "Pricing", note: "Review available service prices", routeName: "inertia.pricing.index", icon: Activity },
  { label: "Profile", note: "Security and personal details", routeName: "inertia.profile.index", icon: CircleUserRound },
];

export default function MorePageV2({ supportNumber }) {
  return (
    <div className="space-y-6">
      <section>
        <p className="rg-v2-kicker">Services</p>
        <h2 className="mt-1 text-xl font-black tracking-tight text-slate-950 dark:text-white">Buy and pay</h2>
        <div className="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
          {services.map(({ label, note, routeName, icon: Icon }) => (
            <Link key={routeName} href={route(routeName)} className="rg-v2-panel flex min-h-[132px] flex-col justify-between p-4 transition hover:-translate-y-1 hover:shadow-xl">
              <div className="flex h-10 w-10 items-center justify-center rounded-2xl" style={{ color: "var(--rg-brand)", background: "color-mix(in srgb, var(--rg-brand) 11%, transparent)" }}><Icon size={20} /></div>
              <div><div className="text-sm font-black text-slate-950 dark:text-white">{label}</div><div className="mt-0.5 text-[10px] text-slate-500 dark:text-slate-400">{note}</div></div>
            </Link>
          ))}
        </div>
      </section>

      <section className="rg-v2-panel overflow-hidden">
        <div className="border-b border-slate-100 px-5 py-4 dark:border-slate-800"><p className="rg-v2-kicker">Account</p><h2 className="mt-1 text-base font-black text-slate-950 dark:text-white">Manage your account</h2></div>
        <div className="divide-y divide-slate-100 dark:divide-slate-800">
          {accountLinks.map(({ label, note, routeName, icon: Icon }) => (
            <Link key={routeName} href={route(routeName)} className="flex items-center gap-4 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-900/60">
              <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300"><Icon size={18} /></div>
              <div className="min-w-0 flex-1"><div className="text-sm font-black text-slate-950 dark:text-white">{label}</div><div className="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{note}</div></div>
              <span className="text-lg text-slate-300 dark:text-slate-600">›</span>
            </Link>
          ))}
        </div>
      </section>

      <section className="rg-v2-panel space-y-2 p-4">
        <InstallAppButton />
        <a href={`https://wa.me/${supportNumber}`} target="_blank" rel="noreferrer" className="rg-v2-side-link"><Headphones size={19} /><span>Customer support</span></a>
        <UiVersionSwitch />
        <button type="button" onClick={() => router.post("/logout2", {}, { replace: true, preserveState: false })} className="rg-v2-side-link w-full text-rose-600 hover:text-rose-700 dark:text-rose-400"><LogOut size={19} /><span>Log out</span></button>
      </section>
    </div>
  );
}
