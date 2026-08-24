export function resolveInstallMode({
  standalone = false,
  ios = false,
  secureContext = true,
  serviceWorkerSupported = true,
  promptAvailable = false,
} = {}) {
  if (standalone) return "installed";
  if (ios) return "ios";
  if (!secureContext) return "insecure";
  if (!serviceWorkerSupported) return "unsupported";
  return promptAvailable ? "install" : "manual";
}

export function currentInstallEnvironment(promptAvailable = false) {
  return {
    standalone: window.matchMedia("(display-mode: standalone)").matches || window.navigator.standalone === true,
    ios: /iphone|ipad|ipod/i.test(window.navigator.userAgent),
    secureContext: window.isSecureContext,
    serviceWorkerSupported: "serviceWorker" in navigator,
    promptAvailable,
  };
}
