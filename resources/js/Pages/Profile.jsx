import { useState } from "react";
import { usePage, Link } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import PrimaryLink from "@/Components/PrimaryLink";
import axios from "axios";




export default function Profile() {
  const { props } = usePage();
  const { auth } = props;
  const user = auth.user;

  const [showPasswordForm, setShowPasswordForm] = useState(false);
  const [showPinForm, setShowPinForm] = useState(false);
  const [statusMessage, setStatusMessage] = useState("");
  const [serverErrors, setServerErrors] = useState({});

  // Form state
  const [passwordData, setPasswordData] = useState({ current_password: "", new_password: "", new_password_confirmation: "" });
  const [pinData, setPinData] = useState({ current_pin: "", new_pin: "", new_pin_confirmation: "" });

  const handlePasswordChange = (e) => {
    setPasswordData({ ...passwordData, [e.target.name]: e.target.value });
  };

  const handlePinChange = (e) => {
    setPinData({ ...pinData, [e.target.name]: e.target.value });
  };


  // Submit password
  const submitPassword = async (e) => {
    e.preventDefault();
    try {
      const response = await axios.post(route("inertia.profile.updatePassword"), passwordData);
      setShowPasswordForm(false);
      setPasswordData({ current_password: "", new_password: "", new_password_confirmation: "" });
      setServerErrors({});
      setStatusMessage(response.data.message || "Password updated successfully");
    } catch (error) {
      if (error.response?.data?.errors) {
        setServerErrors(error.response.data.errors);
        setStatusMessage(error.response.data.message || "Please fix the highlighted errors.");
      } else {
        console.error(error);
        setStatusMessage("An unexpected error occurred.");
      }
    }
  };
  
  // Submit PIN
  const submitPin = async (e) => {
    e.preventDefault();
    try {
      const response = await axios.post(route("inertia.profile.updatePin"), pinData);
      setShowPinForm(false);
      setPinData({ current_pin: "", new_pin: "", new_pin_confirmation: "" });
      setServerErrors({});
      setStatusMessage(response.data.message || "PIN updated successfully");
    } catch (error) {
      if (error.response?.data?.errors) {
        setServerErrors(error.response.data.errors);
        setStatusMessage(error.response.data.message || "Please fix the highlighted errors.");
      } else {
        console.error(error);
        setStatusMessage("An unexpected error occurred.");
      }
    }
  };
  




  return (
    <DashboardLayout title="Profile">
      {/* Back Navigation */}
      <PrimaryLink href={route("dashboard")} primaryColor={props.userDashboardPrimaryColor}>
        Back to Dashboard
      </PrimaryLink>

      {statusMessage && (
        <div className="mt-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-blue-800 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200">
          {statusMessage}
        </div>
      )}

      {/* User Info Card */}
      <div className="bg-white dark:bg-gray-800 text-gray-700 dark:text-white mt-4 pb-8 rounded-xl shadow overflow-hidden font-inter">
        <div className="p-4 border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-white">
          Your Infosss
        </div>

        <div className="p-4 space-y-3">
          <div className="flex justify-between">
            <span>Name:</span>
            <span className="font-semibold">{user.first_name} {user.last_name}</span>
          </div>
          <div className="flex justify-between">
            <span>Email:</span>
            <span className="font-semibold">{user.email}</span>
          </div>
          <div className="flex justify-between">
            <span>Phone:</span>
            <span className="font-semibold">{user.phone_number ?? "Not set"}</span>
          </div>

          {/* Change Password */}
          <div className="mt-4">
            <button
              onClick={() => setShowPasswordForm(!showPasswordForm)}
              className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md shadow text-sm"
            >
              {showPasswordForm ? "Cancel Password Change" : "Change Password"}
            </button>

            {showPasswordForm && (
              <form onSubmit={submitPassword} className="mt-3 space-y-2">
                <input
                  type="password"
                  name="current_password"
                  placeholder="Current Password"
                  value={passwordData.current_password}
                  onChange={handlePasswordChange}
                  className="w-full p-2 border rounded-md dark:bg-gray-700 dark:text-white"
                  required
                />
                {serverErrors.current_password && (
                  <p className="text-sm text-red-500 mt-1">{serverErrors.current_password[0]}</p>
                )}
                <input
                  type="password"
                  name="new_password"
                  placeholder="New Password"
                  value={passwordData.new_password}
                  onChange={handlePasswordChange}
                  className="w-full p-2 border rounded-md dark:bg-gray-700 dark:text-white"
                  required
                />
                {serverErrors.new_password && (
                  <p className="text-sm text-red-500 mt-1">{serverErrors.new_password[0]}</p>
                )}
                <input
                  type="password"
                  name="new_password_confirmation"
                  placeholder="Confirm New Password"
                  value={passwordData.new_password_confirmation}
                  onChange={handlePasswordChange}
                  className="w-full p-2 border rounded-md dark:bg-gray-700 dark:text-white"
                  required
                />
                <button
                  type="submit"
                  className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md shadow text-sm"
                >
                  Save Password
                </button>
              </form>
            )}
          </div>

          {/* Change PIN */}
          <div className="mt-4">
            <button
              onClick={() => setShowPinForm(!showPinForm)}
              className="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-md shadow text-sm"
            >
              {showPinForm ? "Cancel PIN Change" : "Change PIN"}
            </button>

            {showPinForm && (
              <form onSubmit={submitPin} className="mt-3 space-y-2">
                <input
                  type="password"
                  name="current_pin"
                  placeholder="Current PIN"
                  value={pinData.current_pin}
                  onChange={handlePinChange}
                  className="w-full p-2 border rounded-md dark:bg-gray-700 dark:text-white"
                  required
                />
                {serverErrors.current_pin && (
                  <p className="text-sm text-red-500 mt-1">{serverErrors.current_pin[0]}</p>
                )}
                <input
                  type="password"
                  name="new_pin"
                  placeholder="New PIN"
                  value={pinData.new_pin}
                  onChange={handlePinChange}
                  className="w-full p-2 border rounded-md dark:bg-gray-700 dark:text-white"
                  required
                />
                {serverErrors.new_pin && (
                  <p className="text-sm text-red-500 mt-1">{serverErrors.new_pin[0]}</p>
                )}
                <input
                  type="password"
                  name="new_pin_confirmation"
                  placeholder="Confirm New PIN"
                  value={pinData.new_pin_confirmation}
                  onChange={handlePinChange}
                  className="w-full p-2 border rounded-md dark:bg-gray-700 dark:text-white"
                  required
                />
                <button
                  type="submit"
                  className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md shadow text-sm"
                >
                  Save PIN
                </button>
              </form>
            )}
          </div>
        </div>
      </div>
    </DashboardLayout>
  );
}
