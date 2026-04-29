import { useState, useEffect, useMemo } from "react";
import { useForm, usePage } from "@inertiajs/react";
import axios from "axios";
import Swal from "sweetalert2";
import debounce from "lodash.debounce";
import DashboardLayout from "@/Layouts/DashboardLayout";
import WalletBalance from "@/Components/WalletBalance";
import PrimaryLink from "@/Components/PrimaryLink";

export default function BuyElectricity() {
  const { props } = usePage();
  const { auth, electricityProviders = [] } = props;
  const user = auth.user;

  const [plans, setPlans] = useState([]);
  const [loadingPlans, setLoadingPlans] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [validating, setValidating] = useState(false);
  const [customerName, setCustomerName] = useState("Not yet verified");
  const [customerAddress, setCustomerAddress] = useState("Not yet verified");

  const { data, setData, errors } = useForm({
    metre_number: "",
    electricity_product_plan_category_id: "",
    electricity_product_plan_id: "",
    amount: "",
    pin: "",
    product_slug: "utility_bills",
    wallet_category: "main_wallet",
    no_of_slots: 1,
  });

  // 🔹 Fetch plans with debounce
  const fetchPlans = async (categoryId, amount) => {
    if (!categoryId || !amount || Number(amount) < 50) {
      setPlans([]);
      return;
    }

    setLoadingPlans(true);
    try {
      const response = await axios.get(route("user.fetch_product_plans"), {
        params: {
          plan_category_id: categoryId,
          product_slug: "utility_bills",
          amount,
        },
      });
      setPlans(response.data.data || []);
    } catch (err) {
      console.error("Error fetching electricity plans:", err);
      setPlans([]);
    } finally {
      setLoadingPlans(false);
    }
  };

  // ✅ Debounce the plan fetch to avoid too many API calls
  const debouncedFetchPlans = useMemo(() => debounce(fetchPlans, 800), []);

  const handleProviderChange = (categoryId) => {
    setData("electricity_product_plan_category_id", categoryId);
    setData("electricity_product_plan_id", "");
    setCustomerName("Not yet verified");
    setCustomerAddress("Not yet verified");

    if (data.amount && Number(data.amount) >= 50) {
      debouncedFetchPlans(categoryId, data.amount);
    } else {
      setPlans([]);
    }
  };

  const handleAmountChange = (amount) => {
    setData("amount", amount);
    if (data.electricity_product_plan_category_id && Number(amount) >= 50) {
      debouncedFetchPlans(data.electricity_product_plan_category_id, amount);
    } else {
      setPlans([]);
    }
  };

  // ✅ Debounced meter validation
  const verifyMeter = useMemo(
    () =>
      debounce(async (metre_number, plan_id) => {
        if (!metre_number || metre_number.length < 6 || !plan_id) {
          setCustomerName("Not yet verified");
          setCustomerAddress("Not yet verified");
          return;
        }

        setValidating(true);
        setCustomerName("🔄 Validating...");
        setCustomerAddress("🔄 Validating...");

        try {
          const { data: response } = await axios.get(
            route("user.electricity.validate_metre_number"),
            { params: { smart_card_number: metre_number, plan_id } }
          );

          if (response.status === 1) {
            setCustomerName(response.name || "No name found");
            setCustomerAddress(response.address || "No address found");
          } else {
            setCustomerName("❌ Invalid Meter");
            setCustomerAddress("❌ Unable to verify");
          }
        } catch (error) {
          console.error("Meter validation error:", error);
          setCustomerName("❌ Verification failed");
          setCustomerAddress("❌ Verification failed");
        } finally {
          setValidating(false);
        }
      }, 700),
    []
  );

  // 🔹 Auto-verify meter when number or plan changes
  useEffect(() => {
    if (data.metre_number && data.electricity_product_plan_id) {
      verifyMeter(data.metre_number, data.electricity_product_plan_id);
    } else {
      setCustomerName("Not yet verified");
      setCustomerAddress("Not yet verified");
    }
  }, [data.metre_number, data.electricity_product_plan_id, verifyMeter]);

  // Cleanup debounced functions on unmount
  useEffect(() => {
    return () => {
      debouncedFetchPlans.cancel();
      verifyMeter.cancel();
    };
  }, [debouncedFetchPlans, verifyMeter]);

  // 🔹 Handle purchase submission
  const handleSubmit = async (e) => {
    e.preventDefault();

    const selectedPlan = plans.find(
      (p) => p.product_plan_id === data.electricity_product_plan_id
    );

    if (!selectedPlan) {
      Swal.fire("⚠️ No Plan Selected", "Please select an electricity plan first.", "warning");
      return;
    }

    if (!data.amount || Number(data.amount) < 50) {
      Swal.fire("⚠️ Invalid Amount", "Please enter a valid amount (minimum ₦50).", "warning");
      return;
    }

    const result = await Swal.fire({
      title: "Confirm Purchase",
      html: `
        You are about to buy electricity token for <b>${customerName}</b><br>
        Meter Number: <b>${data.metre_number}</b><br>
        Amount: <b>₦${Number(data.amount).toLocaleString("en-NG")}</b>.
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

      // 🔸 Include validation info in the payload
      const payload = {
        ...data,
        amount: Number(data.amount),
        validation_extra_info: customerName,
        validation_address: customerAddress,
      };

      const response = await axios.post(
        route("user.electricity.buy_electricity_subscription_action"),
        payload
      );

      if (response.data.status === 1) {
        await Swal.fire("✅ Success", response.data.message, "success");
        window.location.reload();
      } else {
        Swal.fire("⚠️ Failed", response.data.message, "error");
      }
    } catch (error) {
      console.error("Error submitting electricity purchase:", error);
      Swal.fire("❌ Error", "Something went wrong. Please try again.", "error");
    } finally {
      setSubmitting(false);
    }
  };


  return (
    <DashboardLayout title="Buy Electricity Token">
      <WalletBalance user={user} balanceColor={props.userDashboardPrimaryColor} />

      <PrimaryLink href={route("dashboard")} primaryColor={props.userDashboardPrimaryColor}>
        Back to Dashboard
      </PrimaryLink>

      <div className="bg-white dark:bg-gray-800 text-gray-700 dark:text-white mt-6 pb-16 rounded-xl shadow overflow-hidden">
        <div className="p-4 border-b border-gray-200 dark:border-gray-700 font-semibold">
          Buy Electricity Token ⚡
        </div>

        <form onSubmit={handleSubmit} className="p-4 space-y-4">
          {/* Electricity Provider */}
          <div>
            <label className="block text-sm mb-1">Electricity Provider</label>
            <select
              className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"
              value={data.electricity_product_plan_category_id}
              onChange={(e) => handleProviderChange(e.target.value)}
            >
              <option value="">Select</option>
              {electricityProviders.map((p) => (
                <option key={p.id} value={p.id}>
                  {p.product_plan_category_name}
                </option>
              ))}
            </select>
            {errors.electricity_product_plan_category_id && (
              <p className="text-xs text-red-500 mt-1">
                {errors.electricity_product_plan_category_id}
              </p>
            )}
          </div>

          {/* Amount */}
          <div>
            <label className="block text-sm mb-1">Amount (₦)</label>
            <input
              type="number"
              min={50}
              placeholder="Enter amount e.g. 1000"
              className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"
              value={data.amount}
              onChange={(e) => handleAmountChange(e.target.value)}
            />
            {errors.amount && (
              <p className="text-xs text-red-500 mt-1">{errors.amount}</p>
            )}
          </div>

          {/* Plans */}
          <div>
            <label className="block text-sm mb-1">Available Plans</label>
            {loadingPlans ? (
              <p className="text-gray-500 text-sm">Loading plans...</p>
            ) : plans.length === 0 ? (
              <p className="text-gray-500 text-sm">No plans available.</p>
            ) : (
              <div className="max-h-64 overflow-y-auto pr-1 border rounded-lg p-3">
                <div className="grid grid-cols-3 gap-2 text-xs">
                  {plans.map((plan) => (
                    <div
                      key={plan.product_plan_id}
                      onClick={() => setData("electricity_product_plan_id", plan.product_plan_id)}
                      className={`border rounded-lg p-3 text-center cursor-pointer transition ${
                        data.electricity_product_plan_id === plan.product_plan_id
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

          {/* Meter Number */}
          <div>
            <label className="block text-sm mb-1">Meter Number</label>
            <input
              type="text"
              placeholder="Enter meter number"
              className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"
              value={data.metre_number}
              onChange={(e) => setData("metre_number", e.target.value)}
            />
          </div>

          {/* Customer Info */}
          <div>
            <label className="block text-sm mb-1">Customer Name</label>
            <div className="w-full px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 border text-gray-600 dark:text-gray-300">
              {customerName}
            </div>
          </div>
          <div>
            <label className="block text-sm mb-1">Customer Address</label>
            <div className="w-full px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 border text-gray-600 dark:text-gray-300">
              {customerAddress}
            </div>
          </div>

          {/* PIN */}
          <div>
            <label className="block text-sm mb-1">Transaction PIN</label>
            <input
              type="password"
              maxLength={4}
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
            {submitting ? "Processing..." : "⚡ Buy Token"}
          </button>
        </form>
      </div>
    </DashboardLayout>
  );
}
