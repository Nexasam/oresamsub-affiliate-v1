export default function CommunityCard({ customerCategory, colorData }) {
  const { userDashboardPrimaryColor, color } = colorData;

  const link =
    customerCategory === "pos"
      ? "https://chat.whatsapp.com/GoIik4DCz0k1cH3zyEtFrk?mode=ac_t"
      : "https://chat.whatsapp.com/DnFkmQ9cCYF0DomvyThHLq";

  return (
    <div className="mt-4">
      <a
        href={link}
        target="_blank"
        rel="noopener noreferrer"
        className="block text-white p-6 rounded-2xl shadow-lg transition transform hover:scale-[1.02] hover:shadow-xl"
        style={{
          background: `linear-gradient(90deg, ${userDashboardPrimaryColor} 0%, ${userDashboardPrimaryColor}CC 50%, ${userDashboardPrimaryColor} 100%)`,
          color: color,
        }}
      >
        <div className="flex flex-col md:flex-row items-center justify-between gap-4">
          <div>
            <h2 className="text-lg font-bold flex items-center gap-2">
              🔥{" "}
              {customerCategory === "pos"
                ? "Join Reseller Community"
                : "Join Our Community"}
            </h2>
            <p className="text-sm text-white/90 mt-1">
              Get <span className="font-semibold">real-time updates</span>, promos & alerts directly in our WhatsApp group.
            </p>
          </div>
          <div className="flex-shrink-0 text-3xl">💬</div>
        </div>
      </a>
    </div>
  );
}
