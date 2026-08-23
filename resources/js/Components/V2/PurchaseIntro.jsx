import { CreditCard, PackageOpen, ShieldCheck, Smartphone, Wifi, Zap } from "lucide-react";

const icons = { data: Wifi, airtime: Smartphone, cable: PackageOpen, electricity: Zap };

const money = (value) => Number(value || 0).toLocaleString("en-NG", {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

export default function V2PurchaseIntro({ service, title, description, user }) {
  const Icon = icons[service] || CreditCard;

  return (
    <section className="mb-5 overflow-hidden rounded-[24px] text-white shadow-[0_20px_50px_rgba(15,23,42,.14)]" style={{ background: "linear-gradient(135deg, var(--rg-brand), var(--rg-accent))" }}>
      <div className="relative p-5 sm:p-6">
        <div className="pointer-events-none absolute -right-10 -top-14 h-36 w-36 rounded-full border-[26px] border-white/10" />
        <div className="relative flex items-start gap-4">
          <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15"><Icon size={23} /></div>
          <div className="min-w-0 flex-1">
            <h2 className="text-xl font-black tracking-tight">{title}</h2>
            <p className="mt-1 max-w-xl text-xs leading-5 text-white/70">{description}</p>
          </div>
        </div>
        <div className="relative mt-5 flex items-center justify-between border-t border-white/15 pt-4">
          <div><div className="text-[10px] font-bold uppercase tracking-[.14em] text-white/60">Wallet balance</div><div className="mt-1 text-lg font-black">₦{money(user.main_wallet)}</div></div>
          <div className="flex items-center gap-2 text-[11px] font-bold text-white/75"><ShieldCheck size={16} /> Secure checkout</div>
        </div>
      </div>
    </section>
  );
}
