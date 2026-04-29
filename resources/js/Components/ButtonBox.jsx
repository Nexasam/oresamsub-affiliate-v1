import { Link } from "@inertiajs/react";

// Utility function to slightly darken or lighten a color
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

export default function ButtonBox({ href, icon, label, primaryColor = "#0C9246" }) {
  const darker = adjustColor(primaryColor, -25);
  const gradientStyle = {
    background: `linear-gradient(90deg, ${primaryColor}, ${darker})`,
  };

  return (
    <Link
      href={href}
      className="group p-3 rounded-xl shadow hover:shadow-md transition transform hover:scale-[1.05] 
                 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 
                 flex flex-col items-center border"
      style={{
        border: `1.8px solid ${primaryColor}`,
      }}
    >
      <div
        className="w-10 h-10 mx-auto rounded-full flex items-center justify-center text-white text-xl shadow-sm"
        style={gradientStyle}
      >
        {icon}
      </div>
      <div className="mt-2 font-medium text-[13px] md:text-sm">{label}</div>
    </Link>
  );
}
