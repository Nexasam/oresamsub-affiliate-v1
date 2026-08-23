import { useEffect, useState } from "react";
import { Link } from "@inertiajs/react";
import {
  ArrowDownToLine, ArrowUpRight, ChevronRight, CreditCard, Eye, EyeOff,
  CheckCircle2, Clock3, PackageOpen, ReceiptText, RotateCcw, Smartphone,
  Wifi, X, XCircle, Zap,
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

const DetailRow = ({ label, children, mono = false }) => (
  <div className="flex items-start justify-between gap-5 py-3">
    <dt className="shrink-0 text-xs font-medium text-slate-500 dark:text-slate-400">{label}</dt>
    <dd className={`min-w-0 break-words text-right text-xs font-bold text-slate-900 dark:text-white ${mono ? "font-mono" : ""}`}>{children || "—"}</dd>
  </div>
);

const StatusIcon = ({ status }) => {
  const normalized = String(status);
  if (normalized === "1") return <CheckCircle2 size={28} />;
  if (normalized === "0") return <Clock3 size={28} />;
  if (normalized === "2") return <RotateCcw size={28} />;
  return <XCircle size={28} />;
};

export default function CustomerDashboardV2({ user, transactions, fundingAccounts }) {
  const [showBalance, setShowBalance] = useState(true);
  const [selectedTransaction, setSelectedTransaction] = useState(null);

  useEffect(() => {
    if (!selectedTransaction) return undefined;

    const closeOnEscape = (event) => {
      if (event.key === "Escape") setSelectedTransaction(null);
    };
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    window.addEventListener("keydown", closeOnEscape);

    return () => {
      document.body.style.overflow = previousOverflow;
      window.removeEventListener("keydown", closeOnEscape);
    };
  }, [selectedTransaction]);

  return (
    <div className="space-y-6">
      <section className="relative overflow-hidden rounded-[22px] p-4 text-white shadow-[0_18px_45px_rgba(15,23,42,.16)] sm:p-5" style={{ background: "linear-gradient(135deg, var(--rg-brand), var(--rg-accent))" }}>
        <div className="pointer-events-none absolute -right-12 -top-14 h-36 w-36 rounded-full border-[26px] border-white/10" />
        <div className="pointer-events-none absolute -bottom-16 right-20 h-32 w-32 rounded-full bg-white/10 blur-2xl" />
        <div className="relative flex items-start justify-between gap-5">
          <div>
            <p className="text-xs font-semibold text-white/70">Available balance</p>
            <div className="mt-2 flex items-center gap-3">
              <div className="text-2xl font-black tracking-[-.04em] sm:text-3xl">
                {showBalance ? `₦${money(user.main_wallet)}` : "₦••••••"}
              </div>
              <button type="button" onClick={() => setShowBalance(value => !value)} className="rounded-lg p-2 text-white/75 transition hover:bg-white/10 hover:text-white" aria-label="Toggle wallet balance">
                {showBalance ? <Eye size={19} /> : <EyeOff size={19} />}
              </button>
            </div>
            <p className="mt-2 text-[11px] text-white/65">Secure wallet • Ready for instant purchases</p>
          </div>
          <div className="hidden h-12 w-12 items-center justify-center rounded-2xl bg-white/15 sm:flex"><CreditCard size={23} /></div>
        </div>
        <div className="relative mt-4 flex gap-2">
          <Link href={route("inertia.virtual_accounts.index")} className="inline-flex min-h-10 items-center gap-2 rounded-xl bg-white px-3 text-xs font-bold text-slate-950 shadow-sm transition hover:-translate-y-0.5">
            <ArrowDownToLine size={17} /> Fund wallet
          </Link>
          <Link href={route("inertia.transactions.index")} className="inline-flex min-h-10 items-center gap-2 rounded-xl border border-white/25 bg-white/10 px-3 text-xs font-bold text-white transition hover:bg-white/20">
            <ReceiptText size={17} /> View activity
          </Link>
        </div>
      </section>

      <section>
        <div className="mb-3 flex items-end justify-between">
          <div>
            <p className="rg-v2-kicker">Quick services</p>
            <h2 className="mt-1 text-lg font-bold tracking-tight text-slate-950 dark:text-white">What would you like to do?</h2>
          </div>
          <Link href={route("inertia.more.index")} className="text-xs font-bold" style={{ color: "var(--rg-brand)" }}>See all</Link>
        </div>
        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
          {services.map(({ label, note, routeName, icon: Icon }) => (
            <Link key={routeName} href={route(routeName)} className="group rg-v2-panel flex min-h-[112px] flex-col justify-between p-3 transition hover:-translate-y-1 hover:shadow-xl sm:min-h-[132px] sm:p-4">
              <div className="flex h-9 w-9 items-center justify-center rounded-xl sm:h-11 sm:w-11 sm:rounded-2xl" style={{ color: "var(--rg-brand)", background: "color-mix(in srgb, var(--rg-brand) 11%, transparent)" }}>
                <Icon size={21} strokeWidth={2} />
              </div>
              <div className="mt-3 flex items-end justify-between gap-2 sm:mt-5">
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
              <button type="button" onClick={() => setSelectedTransaction(tx)} key={tx.id} className="flex w-full items-center gap-3 px-5 py-4 text-left transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset dark:hover:bg-slate-900/60" style={{ "--tw-ring-color": "var(--rg-brand)" }}>
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300"><ReceiptText size={18} /></div>
                <div className="min-w-0 flex-1"><div className="truncate text-sm font-bold capitalize text-slate-900 dark:text-white">{String(tx.transaction_category || "Transaction").replaceAll("_", " ")}</div><div className="mt-0.5 text-[11px] text-slate-400">{new Date(tx.created_at).toLocaleString()}</div></div>
                <div className="text-right"><div className="text-sm font-black text-slate-950 dark:text-white">₦{money(tx.discounted_amount ?? tx.amount)}</div><div className={`mt-0.5 text-[11px] font-bold ${color}`}>{status}</div></div>
              </button>
            );
          }) : <div className="px-5 py-12 text-center text-sm text-slate-500">Your transactions will appear here.</div>}
        </div>
      </section>

      {selectedTransaction ? (() => {
        const [status, statusColor] = statusLabel(selectedTransaction.status);
        const service = String(selectedTransaction.transaction_category || "Transaction").replaceAll("_", " ");
        const reference = selectedTransaction.api_id
          ?? selectedTransaction.transaction_reference
          ?? selectedTransaction.reference
          ?? selectedTransaction.id;

        return (
          <div
            className="fixed inset-0 z-[120] flex items-end justify-center bg-slate-950/65 p-0 backdrop-blur-sm sm:items-center sm:p-5"
            onMouseDown={(event) => {
              if (event.target === event.currentTarget) setSelectedTransaction(null);
            }}
            role="dialog"
            aria-modal="true"
            aria-labelledby="transaction-detail-title"
          >
            <div className="max-h-[92vh] w-full overflow-y-auto rounded-t-[30px] bg-white shadow-2xl dark:bg-[#0d1522] sm:max-w-md sm:rounded-[28px]">
              <div className="relative overflow-hidden border-b border-slate-100 px-6 pb-6 pt-7 text-center dark:border-slate-800">
                <div className="pointer-events-none absolute inset-x-0 top-0 h-24 opacity-10" style={{ background: "linear-gradient(135deg, var(--rg-brand), var(--rg-accent))" }} />
                <button type="button" onClick={() => setSelectedTransaction(null)} className="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-900 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700" aria-label="Close transaction details">
                  <X size={18} />
                </button>
                <div className={`relative mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 ${statusColor} dark:bg-slate-800`}>
                  <StatusIcon status={selectedTransaction.status} />
                </div>
                <p className={`relative mt-4 text-xs font-black uppercase tracking-[.14em] ${statusColor}`}>{status}</p>
                <h2 id="transaction-detail-title" className="relative mt-2 text-3xl font-black tracking-[-.04em] text-slate-950 dark:text-white">₦{money(selectedTransaction.discounted_amount ?? selectedTransaction.amount)}</h2>
                <p className="relative mt-1 text-sm capitalize text-slate-500 dark:text-slate-400">{service}</p>
              </div>

              <dl className="divide-y divide-slate-100 px-6 dark:divide-slate-800">
                <DetailRow label="Plan">{selectedTransaction.product_plan?.product_plan_name}</DetailRow>
                <DetailRow label="Recipient" mono>{selectedTransaction.phone_number}</DetailRow>
                <DetailRow label="Original amount">₦{money(selectedTransaction.amount)}</DetailRow>
                <DetailRow label="Amount charged">₦{money(selectedTransaction.discounted_amount ?? selectedTransaction.amount)}</DetailRow>
                <DetailRow label="Reference" mono>{reference}</DetailRow>
                <DetailRow label="Date">{new Date(selectedTransaction.created_at).toLocaleString("en-NG", { dateStyle: "medium", timeStyle: "short" })}</DetailRow>
                {selectedTransaction.refund_reason ? <DetailRow label="Refund reason">{selectedTransaction.refund_reason}</DetailRow> : null}
              </dl>

              <div className="p-6 pt-5">
                <button type="button" onClick={() => setSelectedTransaction(null)} className="min-h-12 w-full rounded-xl text-sm font-black text-white shadow-lg transition hover:-translate-y-0.5" style={{ background: "linear-gradient(135deg, var(--rg-brand), var(--rg-accent))" }}>
                  Done
                </button>
              </div>
            </div>
          </div>
        );
      })() : null}
    </div>
  );
}
