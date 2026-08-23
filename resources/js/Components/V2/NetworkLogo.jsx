import { useState } from "react";
import { resolveNetworkBrand } from "./customerUiPresentation";

export default function NetworkLogo({ name }) {
  const [imageFailed, setImageFailed] = useState(false);
  const brand = resolveNetworkBrand(name);

  return (
    <span className="flex min-w-0 items-center justify-center gap-2">
      {brand.logo && !imageFailed ? (
        <span className="flex h-10 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white p-1 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
          <img
            src={brand.logo}
            alt=""
            aria-hidden="true"
            className="h-full w-full object-contain"
            onError={() => setImageFailed(true)}
          />
        </span>
      ) : (
        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xs font-black text-slate-700 dark:bg-slate-800 dark:text-slate-200">
          {brand.label.slice(0, 3).toUpperCase()}
        </span>
      )}
      <span className="truncate text-xs font-black text-slate-800 dark:text-slate-100">{brand.label}</span>
    </span>
  );
}

