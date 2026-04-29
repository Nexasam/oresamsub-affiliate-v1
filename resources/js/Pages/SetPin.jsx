// resources/js/Pages/SetPin.jsx
import { useState } from "react";
import { useForm, usePage, Link } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import WalletBalance from "@/Components/WalletBalance";
import Swal from "sweetalert2";
import axios from "axios";
import { router } from "@inertiajs/react";
import PrimaryLink from "@/Components/PrimaryLink";


export default function SetPin() {
  const { props } = usePage();
  const { auth } = props;
  const user = auth.user;

  const { data, setData, post, processing, errors, reset } = useForm({
    pin: "",
    confirm_pin: "",
  });

  const [showPin, setShowPin] = useState(false);
  const [showConfirmPin, setShowConfirmPin] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (data.pin.length !== 4 || data.confirm_pin.length !== 4) {
      Swal.fire("Invalid PIN", "PIN must be exactly 4 digits.", "warning");
      return;
    }

    if (data.pin !== data.confirm_pin) {
      Swal.fire("Mismatch", "PIN and Confirm PIN must match.", "error");
      return;
    }

    try {
      setSubmitting(true);
      const response = await axios.post(route("user.settings.store_set_pin"), data);

      if (response.data.status === 1) {
        await Swal.fire("✅ Success", response.data.message, "success");
        router.visit(route('dashboard'));
      } else {
        Swal.fire("⚠️ Failed", response.data.message, "error");
      }
    } catch (error) {
      console.error(error);
      Swal.fire("❌ Error", "Something went wrong. Please try again.", "error");
    } finally {
      setSubmitting(false);
    }
    
  };

  return (
    <DashboardLayout title="Set PIN">
      <div className="pt-6 max-w-full mx-auto pb-24">
            <WalletBalance
                   user={user}
                   balanceColor={ props.userDashboardPrimaryColor }
            />

            <PrimaryLink href={route("dashboard")} primaryColor={props.userDashboardPrimaryColor}>
                  Back to Dashboard
            </PrimaryLink>
            

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="mb-4 text-center">
            <h2 className="text-lg font-bold text-gray-800 dark:text-white">
              Set Your Transaction PIN
            </h2>
            <p className="text-sm text-gray-500 dark:text-gray-400">
              Enter a secure 4-digit PIN to authorize transactions.
            </p>
          </div>

            {/* PIN */}
            <div>
            <label className="block text-sm mb-1 text-gray-700 dark:text-gray-200">PIN</label>
            <div className="relative">
                <input
                type={showPin ? "text" : "password"}
                value={data.pin}
                onChange={(e) => setData("pin", e.target.value)}
                maxLength={4}
                inputMode="numeric"
                pattern="[0-9]*"
                placeholder="Enter 4-digit PIN"
                className="w-full px-4 py-2 pr-10 rounded-lg 
                            bg-white dark:bg-gray-900 
                            border border-gray-300 dark:border-gray-600 
                            text-gray-800 dark:text-white 
                            placeholder-gray-400 dark:placeholder-gray-500
                            focus:outline-none focus:ring-2 focus:ring-emerald-500"
                required
                />
                <button
                type="button"
                onClick={() => setShowPin(!showPin)}
                className="absolute inset-y-0 right-2 flex items-center text-sm text-gray-500 dark:text-gray-400"
                >
                {showPin ? "Hide" : "Show"}
                </button>
            </div>
            {errors.pin && <p className="text-xs text-red-500 mt-1">{errors.pin}</p>}
            </div>

            {/* Confirm PIN */}
            <div>
            <label className="block text-sm mb-1 text-gray-700 dark:text-gray-200">Confirm PIN</label>
            <div className="relative">
                <input
                type={showConfirmPin ? "text" : "password"}
                value={data.confirm_pin}
                onChange={(e) => setData("confirm_pin", e.target.value)}
                maxLength={4}
                inputMode="numeric"
                pattern="[0-9]*"
                placeholder="Re-enter 4-digit PIN"
                className="w-full px-4 py-2 pr-10 rounded-lg 
                            bg-white dark:bg-gray-900 
                            border border-gray-300 dark:border-gray-600 
                            text-gray-800 dark:text-white 
                            placeholder-gray-400 dark:placeholder-gray-500
                            focus:outline-none focus:ring-2 focus:ring-emerald-500"
                required
                />
                <button
                type="button"
                onClick={() => setShowConfirmPin(!showConfirmPin)}
                className="absolute inset-y-0 right-2 flex items-center text-sm text-gray-500 dark:text-gray-400"
                >
                {showConfirmPin ? "Hide" : "Show"}
                </button>
            </div>
            {errors.confirm_pin && (
                <p className="text-xs text-red-500 mt-1">{errors.confirm_pin}</p>
            )}
            </div>


            <button
              type="submit"
              disabled={submitting}
              className="w-full py-2 px-4 text-white rounded-lg transition disabled:opacity-50 shadow hover:shadow-md"
              style={{
                background: `linear-gradient(90deg, ${props.userDashboardPrimaryColor}, ${props.userDashboardPrimaryColor}CC)`,
                border: `2px solid ${props.userDashboardPrimaryColor}`,
                boxShadow: `0 0 6px ${props.userDashboardPrimaryColor}40`,
              }}
            >
              {submitting ? "Setting PIN..." : "🔐 Set PIN"}
            </button>

        </form>
      </div>
    </DashboardLayout>
  );
}
