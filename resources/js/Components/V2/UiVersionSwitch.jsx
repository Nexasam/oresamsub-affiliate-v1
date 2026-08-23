import { router, usePage } from "@inertiajs/react";
import { Layers2 } from "lucide-react";

export default function UiVersionSwitch({ compact = false }) {
  const { customerUi } = usePage().props;

  if (!customerUi?.canSwitch) return null;

  const nextVersion = customerUi.version === "v2" ? "v1" : "v2";

  return (
    <button
      type="button"
      onClick={() => router.patch(customerUi.updateUrl, { version: nextVersion }, {
        preserveScroll: true,
      })}
      className="rg-v2-icon-button gap-2 px-3"
      aria-label={`Switch to interface ${nextVersion.toUpperCase()}`}
      title={`Switch to ${nextVersion.toUpperCase()}`}
    >
      <Layers2 size={17} strokeWidth={1.9} />
      {compact ? null : <span>{nextVersion === "v1" ? "Classic UI" : "New UI"}</span>}
    </button>
  );
}
