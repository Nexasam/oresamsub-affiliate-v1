import { forwardRef, useCallback, useEffect, useImperativeHandle, useState } from "react";
import { CheckCircle2, Download, Share, X } from "lucide-react";
import { currentInstallEnvironment, resolveInstallMode } from "@/Components/V2/pwaInstallState";

const PwaInstallPopup = forwardRef(({ appName = "this app" }, ref) => {
  const [deferredPrompt, setDeferredPrompt] = useState(null);
  const [visible, setVisible] = useState(false);
  const [mode, setMode] = useState("install");
  const cooldownMs = 2 * 24 * 60 * 60 * 1000;

  const requestInstall = useCallback(async () => {
    if (deferredPrompt) {
      await deferredPrompt.prompt();
      await deferredPrompt.userChoice;
      setDeferredPrompt(null);
      setVisible(false);
      return;
    }

    setMode(resolveInstallMode(currentInstallEnvironment(false)));
    setVisible(true);
  }, [deferredPrompt]);

  useImperativeHandle(ref, () => ({ promptInstall: requestInstall }), [requestInstall]);

  useEffect(() => {
    if (!("serviceWorker" in navigator)) return undefined;

    const register = () => navigator.serviceWorker.register("/service-worker.js").catch((error) => {
      console.warn("PWA service worker registration failed.", error);
      return null;
    });
    if (document.readyState === "complete") register();
    else window.addEventListener("load", register, { once: true });

    return () => window.removeEventListener("load", register);
  }, []);

  useEffect(() => {
    const handleBeforeInstallPrompt = (event) => {
      event.preventDefault();
      setDeferredPrompt(event);
      setMode("install");

      const dismissedAt = Number(localStorage.getItem("installDismissedAt") || 0);
      if (!dismissedAt || Date.now() - dismissedAt >= cooldownMs) setVisible(true);
    };
    const handleManualRequest = () => requestInstall();
    const handleInstalled = () => {
      setDeferredPrompt(null);
      setVisible(false);
    };

    window.addEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
    window.addEventListener("pwa:request-install", handleManualRequest);
    window.addEventListener("appinstalled", handleInstalled);

    return () => {
      window.removeEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
      window.removeEventListener("pwa:request-install", handleManualRequest);
      window.removeEventListener("appinstalled", handleInstalled);
    };
  }, [requestInstall]);

  const dismiss = () => {
    localStorage.setItem("installDismissedAt", String(Date.now()));
    setVisible(false);
  };

  if (!visible) return null;

  const content = {
    install: {
      icon: <Download size={24} />,
      title: `Install ${appName}`,
      description: "Add the app to your home screen for faster access and a full-screen experience.",
    },
    ios: {
      icon: <Share size={24} />,
      title: `Add ${appName} to your iPhone`,
      description: "In Safari, tap the Share button, scroll down, then choose Add to Home Screen.",
    },
    installed: {
      icon: <CheckCircle2 size={24} />,
      title: `${appName} is already installed`,
      description: "Open it from your home screen or app launcher.",
    },
    manual: {
      icon: <Download size={24} />,
      title: "Install from your browser menu",
      description: "Chrome or Edge has not offered the automatic prompt yet. Open the browser menu and choose Install app or Add to Home screen.",
    },
    insecure: {
      icon: <Download size={24} />,
      title: "A secure connection is required",
      description: "Open this website using HTTPS before installing it as an app.",
    },
    unsupported: {
      icon: <Download size={24} />,
      title: "This browser cannot install the app",
      description: "Open the website in Chrome, Edge or Safari and add it from the browser menu.",
    },
  }[mode];

  return (
    <div className="fixed inset-0 z-[9999] flex items-end justify-center bg-slate-950/65 p-0 backdrop-blur-sm sm:items-center sm:p-5" role="dialog" aria-modal="true" aria-labelledby="pwa-install-title" onMouseDown={(event) => event.target === event.currentTarget && dismiss()}>
      <div className="relative w-full rounded-t-[28px] bg-white p-6 shadow-2xl dark:bg-[#0d1522] sm:max-w-sm sm:rounded-[28px]">
        <button type="button" onClick={dismiss} className="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300" aria-label="Close install prompt"><X size={18} /></button>
        <div className="flex h-12 w-12 items-center justify-center rounded-2xl text-white" style={{ background: "linear-gradient(135deg, var(--rg-brand, #2563eb), var(--rg-accent, #14b8a6))" }}>{content.icon}</div>
        <h2 id="pwa-install-title" className="mt-5 pr-8 text-xl font-black tracking-tight text-slate-950 dark:text-white">{content.title}</h2>
        <p className="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{content.description}</p>
        <div className="mt-6 flex gap-3">
          <button type="button" onClick={dismiss} className="min-h-11 flex-1 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">{mode === "install" ? "Not now" : "Close"}</button>
          {mode === "install" ? <button type="button" onClick={requestInstall} className="min-h-11 flex-1 rounded-xl px-4 text-sm font-black text-white" style={{ background: "linear-gradient(135deg, var(--rg-brand, #2563eb), var(--rg-accent, #14b8a6))" }}>Install app</button> : null}
        </div>
      </div>
    </div>
  );
});

PwaInstallPopup.displayName = "PwaInstallPopup";

export default PwaInstallPopup;
