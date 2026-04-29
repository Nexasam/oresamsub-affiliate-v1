import { useState, useEffect } from "react";
import { useForm, usePage } from "@inertiajs/react";
import axios from "axios";
import Swal from "sweetalert2";
import debounce from "lodash.debounce";
import DashboardLayout from "@/Layouts/DashboardLayout";
import WalletBalance from "@/Components/WalletBalance";
import PrimaryLink from "@/Components/PrimaryLink";

export default function BuyCable() {
  const { props } = usePage();
  const { auth, cableProviders = [] } = props;
  const user = auth.user;

  const [plans, setPlans] = useState([]);
  const [loadingPlans, setLoadingPlans] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [validating, setValidating] = useState(false);
  const [smartcardName, setSmartcardName] = useState("Not yet verified");

  const { data, setData, errors } = useForm({
    smart_card_number: "",
    validation_customer_name: "",
    cable_product_plan_category_id: "",
    cable_product_plan_id: "",
    pin: "",
    wallet_category: "main_wallet",
    no_of_slots: 1,
  });

  // 🔹 Fetch cable plans when provider changes
  const handleProviderChange = async (categoryId) => {
    setData("cable_product_plan_category_id", categoryId);
    setData("cable_product_plan_id", "");
    setSmartcardName("Not yet verified");

    if (!categoryId) {
      setPlans([]);
      return;
    }

    setLoadingPlans(true);
    try {
      const response = await axios.get(route("user.fetch_product_plans"), {
        params: {
          plan_category_id: categoryId,
          product_slug: "cable_subscription",
        },
      });
      setPlans(response.data.data || []);
    } catch (err) {
      console.error("Error fetching cable plans:", err);
      setPlans([]);
    } finally {
      setLoadingPlans(false);
    }
  };

  // 🔹 Debounced smartcard verification
  const verifySmartcard = debounce(async (smart_card_number, plan_id) => {
    if (!smart_card_number || !plan_id) {
      setSmartcardName("Not yet verified");
      setData("validation_customer_name", "");
      return;
    }

    setValidating(true);
    setSmartcardName("Validating...");

    try {
      const response = await axios.get(
        route("user.cable_subscription.validate_smart_card_number"),
        { params: { smart_card_number, plan_id } }
      );

      const name = response.data.name || "Unable to verify";
      setSmartcardName(name);
      setData("validation_customer_name", name);
    } catch (error) {
      console.error("Error verifying smartcard:", error);
      setSmartcardName("Verification failed ❌");
      setData("validation_customer_name", "");
    } finally {
      setValidating(false);
    }
  }, 800);

  // 🔹 Auto verify when smartcard or plan changes
  useEffect(() => {
    if (data.smart_card_number && data.cable_product_plan_id) {
      verifySmartcard(data.smart_card_number, data.cable_product_plan_id);
    } else {
      setSmartcardName("Not yet verified");
      setData("validation_customer_name", "");
    }
  }, [data.smart_card_number, data.cable_product_plan_id]);

  // 🔹 Submit subscription
  const handleSubmit = async (e) => {
    e.preventDefault();

    // Frontend validation to match backend rules
    if (!data.smart_card_number) {
      Swal.fire("Error", "Smartcard number is required", "error");
      return;
    }
    if (!data.validation_customer_name) {
      Swal.fire("Error", "Smartcard not validated. Please verify.", "error");
      return;
    }
    if (!data.cable_product_plan_id) {
      Swal.fire("Error", "Please select a plan", "error");
      return;
    }
    if (!data.wallet_category) {
      Swal.fire("Error", "Please select wallet category", "error");
      return;
    }
    if (!data.no_of_slots || Number(data.no_of_slots) < 1) {
      Swal.fire("Error", "Number of slots must be at least 1", "error");
      return;
    }
    if (!/^\d{4,5}$/.test(data.pin)) {
      Swal.fire("Error", "Transaction PIN must be 4–5 digits", "error");
      return;
    }

    const selectedPlan = plans.find(
      (p) => p.product_plan_id === data.cable_product_plan_id
    );
    if (!selectedPlan) {
      Swal.fire("Error", "Please select a valid plan", "error");
      return;
    }

    const result = await Swal.fire({
      title: "Confirm Subscription",
      html: `
        You are about to subscribe to <b>${selectedPlan.product_plan_name}</b> 
        for <b>₦${Number(selectedPlan.selling_price).toLocaleString("en-NG")}</b> 
        on Smartcard <b>${data.smart_card_number}</b>.
      `,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Yes, Proceed ✅",
      cancelButtonText: "Cancel ❌",
      confirmButtonColor: "#059669",
      cancelButtonColor: "#d33",
    });

    if (!result.isConfirmed) return;

    try {
      setSubmitting(true);
      const payload = {
        ...data,
        no_of_slots: Number(data.no_of_slots),
      };

      const response = await axios.post(route("user.cable_subscription.buy_cable_subscription_action"), payload);

      if (response.data.status === 1) {
        await Swal.fire("✅ Success", response.data.message, "success");
        window.location.reload();
      } else {
        Swal.fire("⚠️ Failed", response.data.message, "error");
      }
    } catch (error) {
      console.error("Error submitting cable subscription:", error);
      Swal.fire("❌ Error", "Something went wrong. Please try again.", "error");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <DashboardLayout title="Buy Cable Subscription">
      <WalletBalance user={user} balanceColor={props.userDashboardPrimaryColor} />

      <PrimaryLink href={route("dashboard")} primaryColor={props.userDashboardPrimaryColor}>
        Back to Dashboard
      </PrimaryLink>

      <div className="bg-white dark:bg-gray-800 text-gray-700 dark:text-white mt-6 pb-16 rounded-xl shadow overflow-hidden">
        <div className="p-4 border-b border-gray-200 dark:border-gray-700 font-semibold">
          Buy Cable Subscription
        </div>

        <form onSubmit={handleSubmit} className="p-4 space-y-4">
          {/* Provider */}
          <div>
            <label className="block text-sm mb-1">Cable Provider</label>
            <select
              className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"
              value={data.cable_product_plan_category_id}
              onChange={(e) => handleProviderChange(e.target.value)}
            >
              <option value="">Select</option>
              {cableProviders.map((p) => (
                <option key={p.id} value={p.id}>
                  {p.product_plan_category_name}
                </option>
              ))}
            </select>
          </div>

          {/* Plans */}
          <div>
            <label className="block text-sm mb-1">Available Plans</label>
            {loadingPlans ? (
              <p className="text-gray-500 text-sm">Loading plans...</p>
            ) : plans.length === 0 ? (
              <p className="text-gray-500 text-sm">No plans available.</p>
            ) : (
              <div className="max-h-64 overflow-y-auto border rounded-lg p-3">
                <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                  {plans.map((plan) => (
                    <div
                      key={plan.product_plan_id}
                      onClick={() => setData("cable_product_plan_id", plan.product_plan_id)}
                      className={`border rounded-lg p-3 text-center cursor-pointer transition ${
                        data.cable_product_plan_id === plan.product_plan_id
                          ? "border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30"
                          : "hover:border-emerald-400"
                      }`}
                    >
                      <div className="font-semibold">{plan.product_plan_name}</div>
                      <div className="text-emerald-600 dark:text-emerald-400 font-bold">
                        ₦{Number(plan.selling_price).toLocaleString("en-NG")}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Smartcard Number */}
          <div>
            <label className="block text-sm mb-1">Smartcard Number</label>
            <input
              type="text"
              placeholder="Enter smartcard number"
              className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"
              value={data.smart_card_number}
              onChange={(e) => setData("smart_card_number", e.target.value)}
            />
          </div>

          {/* Smartcard Name */}
          <div>
            <label className="block text-sm mb-1">Name on Card</label>
            <div className="w-full px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 border text-gray-600 dark:text-gray-300">
              {smartcardName}
            </div>
          </div>

          {/* Slots */}
          <div>
            <label className="block text-sm mb-1">Number of Slots</label>
            <input
              type="number"
              min="1"
              className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"
              value={data.no_of_slots}
              onChange={(e) => setData("no_of_slots", e.target.value)}
            />
          </div>

          {/* PIN */}
          <div>
            <label className="block text-sm mb-1">Transaction PIN</label>
            <input
              type="password"
              maxLength={5}
              placeholder="****"
              className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"
              value={data.pin}
              onChange={(e) => setData("pin", e.target.value)}
            />
          </div>

          {/* Submit */}
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
            {submitting ? "Processing..." : "📺 Subscribe"}
          </button>
        </form>
      </div>
    </DashboardLayout>
  );
}
