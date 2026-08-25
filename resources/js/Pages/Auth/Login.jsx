import { useState } from "react";
import { Link, useForm, usePage } from "@inertiajs/react";
import { ArrowRight, Eye, EyeOff, LockKeyhole, ShieldCheck, UserRound } from "lucide-react";
import AuthLayout from "@/Layouts/AuthLayout";

export default function Login() {
  const { props } = usePage();
  const [showPassword, setShowPassword] = useState(false);
  const primaryColor = props.userDashboardPrimaryColor || "#2563eb";
  const affiliate = props.affiliate || {};
  const businessName = affiliate?.name || props.sitename || "your account";
  const logo = props.siteLogo
    ? `/assets/landing_page_assets/img/site_logo/${props.siteLogo}`
    : affiliate?.logo || "/assets/logo_imgs/oresamsublogo.jpeg";

  const { data, setData, post, processing, errors } = useForm({ email: "", password: "" });

  const submit = (event) => {
    event.preventDefault();
    post(route("inertia.login.store"));
  };

  return (
    <AuthLayout title="Login">
      <div data-testid="customer-login-card" className="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/95 p-5 shadow-[0_28px_80px_rgba(15,23,42,.12)] backdrop-blur-xl dark:border-slate-800 dark:bg-[#0d1522]/95 sm:p-8">
        <div className="flex items-center gap-3">
          <img src={logo} alt={`${businessName} logo`} className="h-12 w-12 rounded-2xl object-cover shadow-md ring-1 ring-slate-200 dark:ring-slate-700" />
          <div className="min-w-0">
            <p className="truncate text-sm font-extrabold text-slate-900 dark:text-white">{businessName}</p>
            <p className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Secure customer account</p>
          </div>
        </div>

        <header className="mb-7 mt-8">
          <h2 className="text-[30px] font-extrabold tracking-[-.04em] text-slate-950 dark:text-white">Welcome back</h2>
          <p className="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Enter your details to continue to your account.</p>
        </header>

        {props.flash?.success && <div className="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">{props.flash.success}</div>}
        {(props.flash?.error || props.flash?.failure) && <div className="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300">{props.flash.error || props.flash.failure}</div>}

        <form onSubmit={submit} className="space-y-5">
          <div>
            <label htmlFor="email" className="mb-2 block text-[13px] font-bold text-slate-700 dark:text-slate-300">Email, username or phone</label>
            <div className="relative">
              <UserRound className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
              <input id="email" type="text" value={data.email} onChange={(event) => setData("email", event.target.value)} required autoComplete="username" placeholder="Enter your login detail" className="min-h-[52px] w-full rounded-2xl border-slate-300 bg-white py-3 pl-11 pr-4 text-[15px] text-slate-950 shadow-sm transition placeholder:text-slate-400 focus:border-[var(--auth-primary)] focus:ring-[var(--auth-primary)] dark:border-slate-700 dark:bg-slate-950/60 dark:text-white" />
            </div>
            {errors.email && <p className="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{errors.email}</p>}
          </div>

          <div>
            <div className="mb-2 flex items-center justify-between gap-4">
              <label htmlFor="password" className="text-[13px] font-bold text-slate-700 dark:text-slate-300">Password</label>
              <Link href={route("password.request")} className="text-xs font-bold hover:underline" style={{ color: primaryColor }}>Forgot password?</Link>
            </div>
            <div className="relative">
              <LockKeyhole className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
              <input id="password" type={showPassword ? "text" : "password"} value={data.password} onChange={(event) => setData("password", event.target.value)} required autoComplete="current-password" placeholder="Enter your password" className="min-h-[52px] w-full rounded-2xl border-slate-300 bg-white py-3 pl-11 pr-12 text-[15px] text-slate-950 shadow-sm transition placeholder:text-slate-400 focus:border-[var(--auth-primary)] focus:ring-[var(--auth-primary)] dark:border-slate-700 dark:bg-slate-950/60 dark:text-white" />
              <button type="button" onClick={() => setShowPassword((visible) => !visible)} className="absolute right-2.5 top-1/2 inline-flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-white" aria-label={showPassword ? "Hide password" : "Show password"}>
                {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
              </button>
            </div>
            {errors.password && <p className="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{errors.password}</p>}
          </div>

          <button type="submit" disabled={processing} className="inline-flex min-h-[52px] w-full items-center justify-center gap-2 rounded-2xl px-5 text-sm font-extrabold text-white shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl disabled:cursor-wait disabled:opacity-60 disabled:hover:translate-y-0" style={{ background: `linear-gradient(135deg, ${primaryColor}, var(--auth-secondary))`, boxShadow: `0 14px 30px ${primaryColor}30` }}>
            {processing ? <><span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" /> Signing in…</> : <>Sign in <ArrowRight size={17} /></>}
          </button>
        </form>

        <div className="mt-6 flex items-center justify-center gap-2 text-xs font-semibold text-slate-400 lg:hidden"><ShieldCheck size={15} style={{ color: primaryColor }} /> Protected and private</div>
        <p className="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">New here? <a href={route("register")} className="font-extrabold hover:underline" style={{ color: primaryColor }}>Create an account</a></p>
      </div>
    </AuthLayout>
  );
}
