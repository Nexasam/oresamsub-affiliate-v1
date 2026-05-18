import { useState, useEffect } from "react";
import { usePage } from "@inertiajs/react";
import { Head } from "@inertiajs/react";



// Read theme outside component to avoid flash
const getInitialTheme = () => {
  if (typeof window !== "undefined") {
    const stored = localStorage.getItem("theme");
    if (stored) return stored === "dark";
    return window.matchMedia("(prefers-color-scheme: dark)").matches;
  }
  return false; // default light
};

export default function AuthLayout2({ children, title }) {
    const { props } = usePage();
    const { auth,sitename } = props;
  
    const [darkMode, setDarkMode] = useState(getInitialTheme());

     // Initialize dark mode
    useEffect(() => {
        const stored = localStorage.getItem("theme");
        if (
        stored === "dark" ||
        (!stored && window.matchMedia("(prefers-color-scheme: dark)").matches)
        ) {
        setDarkMode(true);
        document.documentElement.classList.add("dark");
        } else {
        setDarkMode(false);
        document.documentElement.classList.remove("dark");
        }
    }, []);
  
 

    return (
      <div className="min-h-screen text-gray-800 dark:text-gray-100 bg-gray-100 dark:bg-gray-900">
        <Head title={`${sitename || "Oresamsub"} | ${title}`} />

  
        <div className="max-w-full mx-auto border border-gray-300 dark:border-gray-700 rounded-xl shadow-lg overflow-hidden bg-white dark:bg-gray-900">
  
          {/* Header */}
          <div className="p-4 flex justify-between items-center border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <button onClick={() => setDarkMode(!darkMode)} className="text-xl">
              {darkMode ? "☀️" : "🌙"}
            </button>
          </div>
  
          {/* Content */}
          <main className="px-4 min-h-[calc(100vh-96px)]">
            {children}
          </main>
  
        </div>
      </div>
    );
  }
