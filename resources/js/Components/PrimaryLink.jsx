import React from "react";
import { Link } from "@inertiajs/react";

const PrimaryLink = ({ href, children, primaryColor = "#0C9246" }) => {
  const adjustColor = (col, amt) => {
    let usePound = false;
    if (col[0] === "#") {
      col = col.slice(1);
      usePound = true;
    }

    let num = parseInt(col, 16);
    let r = (num >> 16) + amt;
    let g = ((num >> 8) & 0x00ff) + amt;
    let b = (num & 0x0000ff) + amt;

    r = Math.max(Math.min(255, r), 0);
    g = Math.max(Math.min(255, g), 0);
    b = Math.max(Math.min(255, b), 0);

    return (usePound ? "#" : "") + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
  };

  const darker = adjustColor(primaryColor, -25);
  const hoverDarker = adjustColor(primaryColor, -40);

  return (
    <Link
      href={href}
      className="inline-flex items-center px-4 py-2 mb-2 mt-4 rounded-lg text-white text-sm font-medium shadow transition font-inter"
      style={{
        background: `linear-gradient(90deg, ${primaryColor}, ${darker})`,
      }}
      onMouseEnter={(e) => {
        e.currentTarget.style.background = `linear-gradient(90deg, ${darker}, ${hoverDarker})`;
      }}
      onMouseLeave={(e) => {
        e.currentTarget.style.background = `linear-gradient(90deg, ${primaryColor}, ${darker})`;
      }}
    >
      ← {children}
    </Link>
  );
};

export default PrimaryLink;
