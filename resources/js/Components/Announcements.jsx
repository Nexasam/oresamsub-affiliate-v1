import { useState, useEffect } from "react";
import { ChevronLeft, ChevronRight } from "lucide-react";

export default function Announcements({ announcements = [], colorData }) {
  const { userDashboardAnnouncementColor, color } = colorData;
  const [index, setIndex] = useState(0);

  if (!announcements || announcements.length === 0) return null;

  // Auto slide every 5s
  useEffect(() => {
    const interval = setInterval(() => {
      setIndex((prev) => (prev + 1) % announcements.length);
    }, 5000);
    return () => clearInterval(interval);
  }, [announcements.length]);

  const prevSlide = () => {
    setIndex((prev) => (prev - 1 + announcements.length) % announcements.length);
  };

  const nextSlide = () => {
    setIndex((prev) => (prev + 1) % announcements.length);
  };

  return (
    <div className="w-full my-2">
      <div
        className="relative flex items-center rounded-lg px-3 py-3 text-xs sm:text-sm overflow-hidden transition-colors duration-300"
        style={{
          backgroundColor: `${userDashboardAnnouncementColor}95`, // very light tint background
          border: `1px solid ${userDashboardAnnouncementColor}55`,
          color: color,
        }}
      >
        {/* Blinking dot */}
        <span
          className="absolute left-2 top-1/2 -translate-y-1/2 w-2.5 h-2.5 rounded-full animate-ping"
          style={{ backgroundColor: userDashboardAnnouncementColor }}
        />
        <span
          className="absolute left-2 top-1/2 -translate-y-1/2 w-2.5 h-2.5 rounded-full"
          style={{ backgroundColor: userDashboardAnnouncementColor }}
        />

        {/* Announcement text */}
        <div
          key={announcements[index].id}
          className="ml-6 flex-1 transition-all duration-500 ease-in-out"
          dangerouslySetInnerHTML={{ __html: announcements[index].description }}
        />

        {/* Controls */}
        {announcements.length > 1 && (
          <div className="flex gap-1 ml-2">
            <button
              onClick={prevSlide}
              className="p-1 rounded-full hover:bg-white/10"
              style={{ color }}
            >
              <ChevronLeft className="w-3 h-3" />
            </button>
            <button
              onClick={nextSlide}
              className="p-1 rounded-full hover:bg-white/10"
              style={{ color }}
            >
              <ChevronRight className="w-3 h-3" />
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
