import { Download } from "lucide-react";

export default function InstallAppButton({ className = "rg-v2-side-link", label = "Install app" }) {
  return (
    <button type="button" className={`${className} w-full`} onClick={() => window.dispatchEvent(new Event("pwa:request-install"))}>
      <Download size={19} strokeWidth={1.9} />
      <span>{label}</span>
    </button>
  );
}
