import { useMemo, useState } from "react";
import { useForm, usePage } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import PrimaryLink from "@/Components/PrimaryLink";

const tabs = [
  { id: "profile", label: "View Profile" },
  { id: "password", label: "Change Password" },
  { id: "pin", label: "Change PIN" },
];

export default function Profile() {
  const { props } = usePage();
  const { auth, userDashboardPrimaryColor } = props;
  const user = auth.user;
  const primaryColor = userDashboardPrimaryColor || "#0d6efd";
  const [activeTab, setActiveTab] = useState("profile");
  const [successMessage, setSuccessMessage] = useState("");

  const passwordForm = useForm({
    current_password: "",
    new_password: "",
    new_password_confirmation: "",
  });

  const pinForm = useForm({
    current_pin: "",
    new_pin: "",
    new_pin_confirmation: "",
  });

  const profileRows = useMemo(
    () => [
      ["Full Name", `${user.first_name || ""} ${user.last_name || ""}`.trim() || user.name || "Not set"],
      ["Username", user.username || "Not set"],
      ["Email", user.email || "Not set"],
      ["Phone", user.phone_number || user.phone || "Not set"],
      ["Role", user.role?.role_name || "Not set"],
      ["Plan", user.user_plan?.plan_name || user.user_plan?.name || "Not set"],
      ["Wallet Balance", `NGN ${Number(user.main_wallet || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}`],
    ],
    [user]
  );

  function submitPassword(e) {
    e.preventDefault();
    setSuccessMessage("");

    passwordForm.post(route("inertia.profile.updatePassword"), {
      preserveScroll: true,
      onSuccess: () => {
        passwordForm.reset();
        setSuccessMessage("Password updated successfully.");
      },
    });
  }

  function submitPin(e) {
    e.preventDefault();
    setSuccessMessage("");

    pinForm.post(route("inertia.profile.updatePin"), {
      preserveScroll: true,
      onSuccess: () => {
        pinForm.reset();
        setSuccessMessage("Transaction PIN updated successfully.");
      },
    });
  }

  const inputClass =
    "w-full px-4 py-2 rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500";

  return (
    <DashboardLayout title="Profile Settings">
      <PrimaryLink href={route("dashboard")} primaryColor={primaryColor}>
        Back to Dashboard
      </PrimaryLink>

      <section className="bg-white dark:bg-gray-800 text-gray-700 dark:text-white mt-4 mb-24 rounded-xl shadow overflow-hidden">
        <div className="p-4 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-lg font-bold">Profile Settings</h2>
          <p className="text-sm text-gray-500 dark:text-gray-400">
            View your profile and manage your password or transaction PIN.
          </p>
        </div>

        <div className="grid grid-cols-3 gap-2 p-3 border-b border-gray-200 dark:border-gray-700">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              type="button"
              onClick={() => {
                setActiveTab(tab.id);
                setSuccessMessage("");
              }}
              className={`rounded-lg px-3 py-2 text-xs sm:text-sm font-semibold transition ${
                activeTab === tab.id
                  ? "text-white shadow"
                  : "bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200"
              }`}
              style={activeTab === tab.id ? { backgroundColor: primaryColor } : undefined}
            >
              {tab.label}
            </button>
          ))}
        </div>

        {successMessage && (
          <div className="mx-4 mt-4 rounded-lg border border-green-300 bg-green-100 p-3 text-sm text-green-700">
            {successMessage}
          </div>
        )}

        <div className="p-4">
          {activeTab === "profile" && (
            <div className="space-y-3">
              {profileRows.map(([label, value]) => (
                <div
                  key={label}
                  className="flex items-start justify-between gap-4 rounded-lg border border-gray-100 dark:border-gray-700 px-3 py-3"
                >
                  <span className="text-sm text-gray-500 dark:text-gray-400">{label}</span>
                  <span className="text-right text-sm font-semibold text-gray-800 dark:text-gray-100">
                    {value}
                  </span>
                </div>
              ))}
            </div>
          )}

          {activeTab === "password" && (
            <form onSubmit={submitPassword} className="space-y-4">
              <div>
                <label className="mb-1 block text-sm">Current Password</label>
                <input
                  type="password"
                  value={passwordForm.data.current_password}
                  onChange={(e) => passwordForm.setData("current_password", e.target.value)}
                  className={inputClass}
                  required
                />
                {passwordForm.errors.current_password && (
                  <p className="mt-1 text-sm text-red-500">{passwordForm.errors.current_password}</p>
                )}
              </div>

              <div>
                <label className="mb-1 block text-sm">New Password</label>
                <input
                  type="password"
                  value={passwordForm.data.new_password}
                  onChange={(e) => passwordForm.setData("new_password", e.target.value)}
                  className={inputClass}
                  required
                />
                {passwordForm.errors.new_password && (
                  <p className="mt-1 text-sm text-red-500">{passwordForm.errors.new_password}</p>
                )}
              </div>

              <div>
                <label className="mb-1 block text-sm">Confirm New Password</label>
                <input
                  type="password"
                  value={passwordForm.data.new_password_confirmation}
                  onChange={(e) => passwordForm.setData("new_password_confirmation", e.target.value)}
                  className={inputClass}
                  required
                />
              </div>

              <button
                type="submit"
                disabled={passwordForm.processing}
                className="w-full rounded-lg px-4 py-2 text-white shadow transition disabled:opacity-50"
                style={{ backgroundColor: primaryColor }}
              >
                {passwordForm.processing ? "Saving..." : "Save Password"}
              </button>
            </form>
          )}

          {activeTab === "pin" && (
            <form onSubmit={submitPin} className="space-y-4">
              <div>
                <label className="mb-1 block text-sm">Current PIN</label>
                <input
                  type="password"
                  inputMode="numeric"
                  maxLength="4"
                  value={pinForm.data.current_pin}
                  onChange={(e) => pinForm.setData("current_pin", e.target.value)}
                  className={inputClass}
                  required
                />
                {pinForm.errors.current_pin && (
                  <p className="mt-1 text-sm text-red-500">{pinForm.errors.current_pin}</p>
                )}
              </div>

              <div>
                <label className="mb-1 block text-sm">New PIN</label>
                <input
                  type="password"
                  inputMode="numeric"
                  maxLength="4"
                  value={pinForm.data.new_pin}
                  onChange={(e) => pinForm.setData("new_pin", e.target.value)}
                  className={inputClass}
                  required
                />
                {pinForm.errors.new_pin && (
                  <p className="mt-1 text-sm text-red-500">{pinForm.errors.new_pin}</p>
                )}
              </div>

              <div>
                <label className="mb-1 block text-sm">Confirm New PIN</label>
                <input
                  type="password"
                  inputMode="numeric"
                  maxLength="4"
                  value={pinForm.data.new_pin_confirmation}
                  onChange={(e) => pinForm.setData("new_pin_confirmation", e.target.value)}
                  className={inputClass}
                  required
                />
              </div>

              <button
                type="submit"
                disabled={pinForm.processing}
                className="w-full rounded-lg px-4 py-2 text-white shadow transition disabled:opacity-50"
                style={{ backgroundColor: primaryColor }}
              >
                {pinForm.processing ? "Saving..." : "Save PIN"}
              </button>
            </form>
          )}
        </div>
      </section>
    </DashboardLayout>
  );
}
