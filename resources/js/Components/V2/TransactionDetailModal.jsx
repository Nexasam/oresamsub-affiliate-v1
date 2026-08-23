import { useEffect } from "react";
import { CheckCircle2, Clock3, RotateCcw, X, XCircle } from "lucide-react";
import { resolveTransactionReferences } from "./customerUiPresentation";

const money = (value) => Number(value || 0).toLocaleString("en-NG", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const statusDetails = (status) => ({
  "1": ["Successful", "text-emerald-600 dark:text-emerald-400", CheckCircle2],
  "0": ["Processing", "text-amber-600 dark:text-amber-400", Clock3],
  "-1": ["Unsuccessful", "text-rose-600 dark:text-rose-400", XCircle],
  "2": ["Refunded", "text-blue-600 dark:text-blue-400", RotateCcw],
}[String(status)] || ["Unknown", "text-slate-500", Clock3]);

const DetailRow = ({ label, children, mono = false }) => (
  <div className="flex items-start justify-between gap-5 py-3">
    <dt className="shrink-0 text-xs font-medium text-slate-500 dark:text-slate-400">{label}</dt>
    <dd className={`min-w-0 break-words text-right text-xs font-bold text-slate-900 dark:text-white ${mono ? "font-mono" : ""}`}>{children || "—"}</dd>
  </div>
);

export default function TransactionDetailModal({ transaction, onClose }) {
  useEffect(() => {
    const closeOnEscape = (event) => event.key === "Escape" && onClose();
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    window.addEventListener("keydown", closeOnEscape);
    return () => {
      document.body.style.overflow = previousOverflow;
      window.removeEventListener("keydown", closeOnEscape);
    };
  }, [onClose]);

  if (!transaction) return null;

  const [status, statusColor, StatusIcon] = statusDetails(transaction.status);
  const service = String(transaction.transaction_category || "Transaction").replaceAll("_", " ");
  const references = resolveTransactionReferences(transaction);

  return (
    <div className="fixed inset-0 z-[120] flex items-end justify-center bg-slate-950/65 p-0 backdrop-blur-sm sm:items-center sm:p-5" onMouseDown={(event) => event.target === event.currentTarget && onClose()} role="dialog" aria-modal="true" aria-labelledby="transaction-detail-title">
      <div className="max-h-[92vh] w-full overflow-y-auto rounded-t-[30px] bg-white shadow-2xl dark:bg-[#0d1522] sm:max-w-md sm:rounded-[28px]">
        <div className="relative overflow-hidden border-b border-slate-100 px-6 pb-6 pt-7 text-center dark:border-slate-800">
          <div className="pointer-events-none absolute inset-x-0 top-0 h-24 opacity-10" style={{ background: "linear-gradient(135deg, var(--rg-brand), var(--rg-accent))" }} />
          <button type="button" onClick={onClose} className="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-900 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700" aria-label="Close transaction details"><X size={18} /></button>
          <div className={`relative mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 ${statusColor} dark:bg-slate-800`}><StatusIcon size={28} /></div>
          <p className={`relative mt-4 text-xs font-black uppercase tracking-[.14em] ${statusColor}`}>{status}</p>
          <h2 id="transaction-detail-title" className="relative mt-2 text-3xl font-black tracking-[-.04em] text-slate-950 dark:text-white">₦{money(transaction.discounted_amount ?? transaction.amount)}</h2>
          <p className="relative mt-1 text-sm capitalize text-slate-500 dark:text-slate-400">{service}</p>
        </div>
        <dl className="divide-y divide-slate-100 px-6 dark:divide-slate-800">
          <DetailRow label="Plan">{transaction.product_plan?.product_plan_name}</DetailRow>
          <DetailRow label="Recipient" mono>{transaction.phone_number}</DetailRow>
          <DetailRow label="Original amount">₦{money(transaction.amount)}</DetailRow>
          <DetailRow label="Amount charged">₦{money(transaction.discounted_amount ?? transaction.amount)}</DetailRow>
          <DetailRow label="Transaction reference" mono>{references.transaction}</DetailRow>
          {references.provider ? <DetailRow label="Provider reference" mono>{references.provider}</DetailRow> : null}
          <DetailRow label="Date">{new Date(transaction.created_at).toLocaleString("en-NG", { dateStyle: "medium", timeStyle: "short" })}</DetailRow>
          {transaction.refund_reason ? <DetailRow label="Refund reason">{transaction.refund_reason}</DetailRow> : null}
        </dl>
        <div className="p-6 pt-5"><button type="button" onClick={onClose} className="min-h-12 w-full rounded-xl text-sm font-black text-white shadow-lg transition hover:-translate-y-0.5" style={{ background: "linear-gradient(135deg, var(--rg-brand), var(--rg-accent))" }}>Done</button></div>
      </div>
    </div>
  );
}
