import { useRef, useState } from "react";
import { useForm, usePage, Link } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/CustomerLayout";
import axios from "axios";
import Swal from "sweetalert2";
import WalletBalance from "@/Components/WalletBalance";
import PrimaryLink from "@/Components/PrimaryLink";
import V2PurchaseIntro from "@/Components/V2/PurchaseIntro";



export default function BuyAirtime() {
  const { props } = usePage();
  const { auth, networks, customerUi } = props;
  const user = auth.user;
  const isV2 = customerUi?.version === "v2";

  const [showBalance, setShowBalance] = useState(true);
  const [plans, setPlans] = useState([]);
  const [loadingPlans, setLoadingPlans] = useState(false);
  const [planError, setPlanError] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const planRequest = useRef({ sequence: 0, controller: null });

  const { data, setData, post, errors } = useForm({
    phone_number: "",
    network_id: "",
    product_plan_id: "",
    amount: "",
    pin: "",
    product_slug: "airtime",
    wallet_category: "main_wallet",
    validatephonenetwork:1
  });

 // When network changes
  const handleNetworkChange = (networkId) => {
    setData("network_id", networkId);
    setData("product_plan_id", ""); // reset plan
    fetchPlans(networkId, data.amount);
  };
  
  // When amount changess
  const handleAmountChange = (amount) => {
    setData("amount", amount);
    setData("product_plan_id", "");
    fetchPlans(data.network_id, amount);
  };




  const fetchPlans = async (networkId, amount = "") => {
    const numericAmount = Number(amount);
    const sequence = planRequest.current.sequence + 1;
    planRequest.current.sequence = sequence;
    planRequest.current.controller?.abort();

    if (!networkId || !Number.isFinite(numericAmount) || numericAmount < 50) {
      planRequest.current.controller = null;
      setLoadingPlans(false);
      setPlanError("");
      setPlans([]);
      return;
    }

    const controller = new AbortController();
    planRequest.current.controller = controller;
    setLoadingPlans(true);
    setPlanError("");
    try {
      const response = await axios.get(route("user.fetch_product_plans"), {
        signal: controller.signal,
        params: {
          network_id: networkId,
          product_slug: "airtime",
          amount,
        },
      });
  
      if (sequence !== planRequest.current.sequence) return;

      const responsePlans = response.data.data ?? response.data.plans ?? response.data.message ?? [];
      const dataList = Array.isArray(responsePlans)
        ? responsePlans
        : Object.values(responsePlans || {});
  
      const mappedPlans = dataList.map((plan) => {
        return {
          ...plan,
          product_plan_id: String(plan.product_plan_id),
          label: `${plan.product_plan_name} - You are buying for: ₦${plan.selling_price}`,
          selected: true,
        };
      });
  
      setPlans(mappedPlans);
  
      if (mappedPlans.length > 0) {
        setData("product_plan_id", mappedPlans[0].product_plan_id);
      }
    } catch (err) {
      if (controller.signal.aborted || axios.isCancel(err) || sequence !== planRequest.current.sequence) return;
      console.error("Error fetching airtime plans:", err);
      setPlans([]);
      setPlanError(err.response?.data?.message || "Airtime plans could not be loaded. Please try again.");
    } finally {
      if (sequence === planRequest.current.sequence) {
        setLoadingPlans(false);
      }
    }
  };
  
  

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!data.product_plan_id) {
      Swal.fire("No Plan Selected", "Please select a plan first.", "warning");
      return;
    }

    const selectedPlan = plans.find((p) => p.product_plan_id === data.product_plan_id);

    const result = await Swal.fire({
      title: "Confirm Purchase",
      html: `
        Are you sure you want to purchase 
        <b>${selectedPlan.product_plan_name}</b> 
        for <b>₦${Number(selectedPlan.selling_price).toLocaleString("en-NG")}</b> 
        to <b>${data.phone_number}</b>?
      `,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Yes, Buy Now ✅",
      cancelButtonText: "Cancel ❌",
      confirmButtonColor: "#059669",
      cancelButtonColor: "#d33",
    });

    if (!result.isConfirmed) return;

    try {
      setSubmitting(true);
      const response = await axios.post(route("user.airtime.buy_airtime_action2"), data);
      if (response.data.status === 1) {
        await Swal.fire("✅ Success", response.data.message, "success");
       
        
        // 🔹 Clear form on success
        // setPlans([]);
        window.location.reload(); // reload the page

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

  const networkColors = {
    MTN: "bg-[#FFCC00] text-black border-[#FFCC00]",
    GLO: "bg-[#008751] text-white border-[#008751]",
    AIRTEL: "bg-gradient-to-r from-[#EE1C25] to-[#B50000] text-white border-[#B50000]",
    "9MOBILE": "bg-[#A6CE39] text-black border-[#A6CE39]",
  };

  return (
    <DashboardLayout  title="Buy Airtime">
      {/* Wallet */}
       {isV2 ? <V2PurchaseIntro service="airtime" title="Buy airtime" description="Top up any supported mobile network in a few taps." user={user} /> : <><WalletBalance
              user={user}
              balanceColor={ props.userDashboardPrimaryColor }
        />

      <PrimaryLink href={route("dashboard")} primaryColor={props.userDashboardPrimaryColor}>
       Back to Dashboard
      </PrimaryLink></>}


      {/* Buy Airtime Card */}
      <div className={isV2 ? "rg-v2-panel overflow-hidden text-slate-700 dark:text-white" : "bg-white dark:bg-gray-800 text-gray-700 dark:text-white mt-6 pb-16 rounded-xl shadow overflow-hidden font-inter"}>
        <div className={isV2 ? "border-b border-slate-100 px-5 py-4 text-sm font-black text-slate-950 dark:border-slate-800 dark:text-white" : "p-4 border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-white"}>
          Buy Airtime
        </div>

        <form autoComplete="off" data-lpignore="true" data-1p-ignore="true" onSubmit={handleSubmit} className={isV2 ? "rg-v2-purchase-form" : "p-4 space-y-4"}>
          {/* Phone Number */}
          <div>
            <label className="block text-sm mb-1">Phone Number</label>
            <input
              type="tel"
              inputMode="numeric"
              autoComplete="off"
              name="airtime_recipient_phone"
              className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"
              placeholder="e.g. 08012345678"
              value={data.phone_number}
              onChange={(e) => setData("phone_number", e.target.value)}
            />
            {errors.phone_number && <p className="text-xs text-red-500 mt-1">{errors.phone_number}</p>}
          </div>

            {/* Network Cards */}
           {/* Networks */}
           <div>
            <label className="block text-sm mb-2">Network</label>
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
              {networks.map((n) => {
                // Exact brand colors
                const networkColors = {
                  MTN: "bg-[#FFCC00] text-black border-[#FFCC00]",   
                  GLO: "bg-[#008751] text-white border-[#008751]",   
                  AIRTEL: "bg-gradient-to-r from-[#EE1C25] to-[#B50000] text-white border-[#B50000]",
                  "9MOBILE": "bg-[#A6CE39] text-black border-[#A6CE39]" 
                };
                
                const colorClass =
                  networkColors[n.network_name] ||
                  "bg-gray-200 text-gray-700 border-gray-300";

                return (
                  <div
                    key={n.id}
                    onClick={() => handleNetworkChange(n.id)}
                    className={`cursor-pointer rounded-lg border p-1 text-center font-semibold transition
                      ${data.network_id === n.id
                        ? `${colorClass} ring-2 ring-emerald-500 scale-105`
                        : `${colorClass} hover:shadow-md`}
                    `}
                  >
                    {n.network_name}
                  </div>
                );
              })}
            </div>
            {errors.network_id && (
              <p className="text-xs text-red-500 mt-1">{errors.network_id}</p>
            )}
          </div>

          

          {/* Amount */}
          <div>
            <label className="block text-sm mb-1">Amount (₦)</label>
            <input
              type="number"
              autoComplete="off"
              name="airtime_purchase_amount"
              className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"
              placeholder="e.g. 100"
              min={50}
              value={data.amount}
              onChange={(e) => handleAmountChange(e.target.value)}
            />
          </div>

      

          {/* Product Plan */}
          <div>
            <label className="block text-sm mb-1">Product Plan</label>
            {planError ? (
              <p className="mb-2 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600 dark:bg-red-950/40 dark:text-red-300">{planError}</p>
            ) : null}
            {loadingPlans ? (
              <p className="text-gray-500 text-sm">Loading plans...</p>
            ) : plans.length === 0 ? (
              <p className="text-gray-500 text-sm">No plans available.</p>
            ) : (
              <div className="max-h-64 overflow-y-auto pr-1 border-2 border-gray-400 rounded-xl p-3">
                <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                  {plans.map((plan) => (
                    <div
                      key={plan.product_plan_id}
                      onClick={() => setData("product_plan_id", plan.product_plan_id)}
                      className={`border rounded-lg p-3 text-center cursor-pointer transition ${
                        data.product_plan_id === plan.product_plan_id ? "border-emerald-600 bg-emerald-50 dark:bg-emerald-900/30" : "hover:border-emerald-400"
                      }`}
                    >
                      <div className="font-semibold text-gray-800 dark:text-white">{plan.product_plan_name}</div>
                      <div className="text-emerald-600 dark:text-emerald-400 font-bold">
                        ₦{Number(plan.selling_price).toLocaleString("en-NG")}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Transaction PIN */}
          <div>
            <label className="block text-sm mb-1">Transaction PIN</label>
            <input
              type="password"
              inputMode="numeric"
              autoComplete="off"
              name="airtime_transaction_pin"
              data-lpignore="true"
              data-1p-ignore="true"
              maxLength={4}
              className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500"
              placeholder="****"
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
            {submitting ? "Processing..." : "🔌 Buy Airtime"}
          </button>

        </form>
      </div>
    </DashboardLayout>
  );
}
