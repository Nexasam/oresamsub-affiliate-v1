import { useState } from "react";
import { Link } from "@inertiajs/react";
import {
  ArrowDownToLine, ArrowUpRight, ChevronRight, CreditCard, Eye, EyeOff,
  PackageOpen, ReceiptText, Smartphone, Wifi, Zap,
} from "lucide-react";

const money = (value) => Number(value || 0).toLocaleString("en-NG", {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

const statusLabel = (status) => ({
  "1": ["Successful", "text-emerald-600 dark:text-emerald-400"],
  "0": ["Processing", "text-amber-600 dark:text-amber-400"],
  "-1": ["Unsuccessful", "text-rose-600 dark:text-rose-400"],
  "2": ["Refunded", "text-blue-600 dark:text-blue-400"],
}[String(status)] || ["Unknown", "text-slate-500"]);

const services = [
  { label: "Buy data", note: "All networks", routeName: "inertia.data.index", icon: Wifi },
  { label: "Airtime", note: "Instant top-up", routeName: "inertia.airtime.index", icon: Smartphone },
  { label: "Cable TV", note: "Renew subscription", routeName: "inertia.cable.index", icon: PackageOpen },
  { label: "Electricity", note: "Pay meter bills", routeName: "inertia.electricity.index", icon: Zap },
];

export default function CustomerDashboardV2({ user, transactions, fundingAccounts }) {
  const [showBalance, setShowBalance] = useState(true);

  return (
    <div className="space-y-6">
      <section className="relative overflow-hidden rounded-[28px] p-6 text-white shadow-[0_24px_60px_rgba(15,23,42,.18)] sm:p-8" style={{ background: "linear-gradient(135deg, var(--rg-brand), var(--rg-accent))" }}>
        <div className="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full border-[38px] border-white/10" />
        <div className="pointer-events-none absolute -bottom-24 right-24 h-48 w-48 rounded-full bg-white/10 blur-2xl" />
        <div className="relative flex items-start justify-between gap-5">
          <div>
            <p className="text-xs font-semibold text-white/70">Available balance</p>
            <div className="mt-2 flex items-center gap-3">
              <div className="text-3xl font-black tracking-[-.04em] sm:text-4xl">
                {showBalance ? `₦${money(user.main_wallet)}` : "₦••••••"}
              </div>
              <button type="button" onClick={() => setShowBalance(value => !value)} className="rounded-lg p-2 text-white/75 transition hover:bg-white/10 hover:text-white" aria-label="Toggle wallet balance">
                {showBalance ? <Eye size={19} /> : <EyeOff size={19} />}
              </button>
            </div>
            <p className="mt-3 text-xs text-white/65">Secure wallet • Ready for instant purchases</p>
          </div>
          <div className="hidden h-12 w-12 items-center justify-center rounded-2xl bg-white/15 sm:flex"><CreditCard size={23} /></div>
        </div>
        <div className="relative mt-7 flex flex-wrap gap-3">
          <Link href={route("inertia.virtual_accounts.index")} className="inline-flex min-h-11 items-center gap-2 rounded-xl bg-white px-4 text-sm font-bold text-slate-950 shadow-sm transition hover:-translate-y-0.5">
            <ArrowDownToLine size={17} /> Fund wallet
          </Link>
          <Link href={route("inertia.transactions.index")} className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 text-sm font-bold text-white transition hover:bg-white/20">
            <ReceiptText size={17} /> View activity
          </Link>
        </div>
      </section>

      <section>
        <div className="mb-3 flex items-end justify-between">
          <div>
            <p className="rg-v2-kicker">Quick services</p>
            <h2 className="mt-1 text-xl font-bold tracking-tight text-slate-950 dark:text-white">What would you like to do?</h2>
          </div>
          <Link href={route("inertia.more.index")} className="text-xs font-bold" style={{ color: "var(--rg-brand)" }}>See all</Link>
        </div>
        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
          {services.map(({ label, note, routeName, icon: Icon }) => (
            <Link key={routeName} href={route(routeName)} className="group rg-v2-panel flex min-h-[144px] flex-col justify-between p-4 transition hover:-translate-y-1 hover:shadow-xl">
              <div className="flex h-11 w-11 items-center justify-center rounded-2xl" style={{ color: "var(--rg-brand)", background: "color-mix(in srgb, var(--rg-brand) 11%, transparent)" }}>
                <Icon size={21} strokeWidth={2} />
              </div>
              <div className="mt-5 flex items-end justify-between gap-2">
                <div><div className="text-sm font-bold text-slate-950 dark:text-white">{label}</div><div className="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{note}</div></div>
                <ArrowUpRight size={16} className="text-slate-300 transition group-hover:text-slate-700 dark:text-slate-600 dark:group-hover:text-white" />
              </div>
            </Link>
          ))}
        </div>
      </section>

      {fundingAccounts.length ? (
        <section className="rg-v2-panel overflow-hidden">
          <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-800">
            <div><p className="rg-v2-kicker">Funding</p><h2 className="mt-1 text-base font-bold text-slate-950 dark:text-white">Your virtual accounts</h2></div>
            <Link href={route("inertia.virtual_accounts.index")} className="flex items-center gap-1 text-xs font-bold" style={{ color: "var(--rg-brand)" }}>View all <ChevronRight size={15} /></Link>
          </div>
          <div className="grid gap-px bg-slate-100 dark:bg-slate-800 sm:grid-cols-2">
            {fundingAccounts.slice(0, 2).map(account => (
              <div key={account.id} className="bg-white p-5 dark:bg-[#0d1522]">
                <div className="text-[11px] font-bold uppercase tracking-wider text-slate-400">{account.bank_name}</div>
                <div className="mt-2 font-mono text-xl font-black tracking-wide text-slate-950 dark:text-white">{account.account_number}</div>
                <div className="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{account.account_name}</div>
              </div>
            ))}
          </div>
        </section>
      ) : null}

      <section className="rg-v2-panel overflow-hidden">
        <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-800">
          <div><p className="rg-v2-kicker">Activity</p><h2 className="mt-1 text-base font-bold text-slate-950 dark:text-white">Recent transactions</h2></div>
          <Link href={route("inertia.transactions.index")} className="flex items-center gap-1 text-xs font-bold" style={{ color: "var(--rg-brand)" }}>View all <ChevronRight size={15} /></Link>
        </div>
        <div className="divide-y divide-slate-100 dark:divide-slate-800">
          {transactions.length ? transactions.slice(0, 6).map(tx => {
            const [status, color] = statusLabel(tx.status);
            return (
              <Link href={route("inertia.transactions.index")} key={tx.id} className="flex items-center gap-3 px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-900/60">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300"><ReceiptText size={18} /></div>
                <div className="min-w-0 flex-1"><div className="truncate text-sm font-bold capitalize text-slate-900 dark:text-white">{String(tx.transaction_category || "Transaction").replaceAll("_", " ")}</div><div className="mt-0.5 text-[11px] text-slate-400">{new Date(tx.created_at).toLocaleString()}</div></div>
                <div className="text-right"><div className="text-sm font-black text-slate-950 dark:text-white">₦{money(tx.discounted_amount ?? tx.amount)}</div><div className={`mt-0.5 text-[11px] font-bold ${color}`}>{status}</div></div>
              </Link>
            );
          }) : <div className="px-5 py-12 text-center text-sm text-slate-500">Your transactions will appear here.</div>}
        </div>
      </section>
    </div>
  );
}
