import { useEffect, useState } from "react";
import { Head, usePage } from "@inertiajs/react";
import { Moon, ShieldCheck, Sun } from "lucide-react";

const initialDarkMode = () => {
  if (typeof window === "undefined") return false;
  const stored = window.localStorage.getItem("theme");
  return stored ? stored === "dark" : window.matchMedia("(prefers-color-scheme: dark)").matches;
};

export default function AuthLayout({ children, title }) {
  const { auth, sitename, userDashboardPrimaryColor, userDashboardSecondaryColor } = usePage().props;
  const [darkMode, setDarkMode] = useState(initialDarkMode);
  const [checkingAuth, setCheckingAuth] = useState(true);

  useEffect(() => {
    if (auth?.user) {
      window.location.href = "/dashboard";
      return;
    }
    setCheckingAuth(false);
  }, [auth]);

  useEffect(() => {
    document.documentElement.classList.toggle("dark", darkMode);
    window.localStorage.setItem("theme", darkMode ? "dark" : "light");
  }, [darkMode]);

  if (checkingAuth) {
    return (
      <div className="grid min-h-screen place-items-center bg-slate-50 dark:bg-slate-950">
        <span className="h-9 w-9 animate-spin rounded-full border-[3px] border-slate-200 border-t-blue-600 dark:border-slate-800 dark:border-t-blue-400" aria-label="Loading" />
      </div>
    );
  }

  const primary = userDashboardPrimaryColor || "#2563eb";
  const secondary = userDashboardSecondaryColor || "#14b8a6";

  return (
    <div
      className="relative min-h-screen overflow-hidden bg-slate-50 font-sans text-slate-950 dark:bg-[#070b12] dark:text-white"
      style={{ "--auth-primary": primary, "--auth-secondary": secondary }}
    >
      <Head title={`${sitename || "Customer account"} | ${title}`} />

      <div className="pointer-events-none fixed inset-0" aria-hidden="true">
        <div className="absolute -left-24 -top-28 h-80 w-80 rounded-full opacity-[.13] blur-3xl" style={{ background: primary }} />
        <div className="absolute -bottom-36 -right-24 h-96 w-96 rounded-full opacity-[.12] blur-3xl" style={{ background: secondary }} />
        <div className="absolute inset-0 opacity-[.025] dark:opacity-[.06] [background-image:linear-gradient(to_right,currentColor_1px,transparent_1px),linear-gradient(to_bottom,currentColor_1px,transparent_1px)] [background-size:32px_32px]" />
      </div>

      <button
        type="button"
        onClick={() => setDarkMode((current) => !current)}
        className="fixed right-4 top-4 z-30 inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200/80 bg-white/90 text-slate-600 shadow-sm backdrop-blur transition hover:-translate-y-0.5 hover:text-slate-950 dark:border-slate-800 dark:bg-slate-900/90 dark:text-slate-300 dark:hover:text-white sm:right-6 sm:top-6"
        aria-label={darkMode ? "Use light theme" : "Use dark theme"}
      >
        {darkMode ? <Sun size={19} /> : <Moon size={19} />}
      </button>

      <main className="relative z-10 mx-auto grid min-h-screen w-full max-w-[1120px] items-center gap-10 px-4 py-20 sm:px-7 lg:grid-cols-[.85fr_1.15fr] lg:px-10">
        <section className="hidden max-w-md lg:block">
          <div className="mb-7 inline-flex h-14 w-14 items-center justify-center rounded-[20px] text-white shadow-xl" style={{ background: `linear-gradient(135deg, ${primary}, ${secondary})` }}>
            <ShieldCheck size={27} strokeWidth={2.2} />
          </div>
          <h1 className="text-[42px] font-extrabold leading-[1.08] tracking-[-.045em] text-slate-950 dark:text-white">
            Quick purchases.<br />Secure payments.<br />One account.
          </h1>
          <p className="mt-6 max-w-sm text-[15px] leading-7 text-slate-600 dark:text-slate-400">
            Sign in to fund your wallet, purchase services and follow every transaction from one private workspace.
          </p>
          <div className="mt-9 flex items-center gap-3 text-sm font-semibold text-slate-500 dark:text-slate-400">
            <ShieldCheck size={18} style={{ color: primary }} /> Protected and private
          </div>
        </section>

        <section className="mx-auto w-full max-w-[500px]">{children}</section>
      </main>
    </div>
  );
}
