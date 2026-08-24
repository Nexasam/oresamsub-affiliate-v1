import { useState } from "react";
import { usePage, router } from "@inertiajs/react";
import { Landmark, Loader2 } from "lucide-react";
import DashboardLayout from "@/Layouts/CustomerLayout";
import WalletBalance from "@/Components/WalletBalance";
import PrimaryLink from "@/Components/PrimaryLink";


export default function VirtualAccounts() {
  const { props } = usePage();
  const { auth, virtualccts = [], fundingHistory = [] } = props;
  const user = auth.user;

  const [copiedAcct, setCopiedAcct] = useState(null);
  const [showBalance, setShowBalance] = useState(true);
  const [generating, setGenerating] = useState(false);
  const [generationNotice, setGenerationNotice] = useState(null);

  const generateVirtualAccount = () => {
    setGenerationNotice(null);
    router.post(route("user.virtual_accounts.generate"), {}, {
      preserveScroll: true,
      onStart: () => setGenerating(true),
      onSuccess: (page) => {
        const failure = page.props?.flash?.failure;
        setGenerationNotice({
          type: failure ? "error" : "success",
          message: failure || page.props?.flash?.success || "Virtual account generation completed.",
        });
      },
      onError: () => setGenerationNotice({
        type: "error",
        message: "The account could not be generated. Please try again or contact support.",
      }),
      onFinish: () => setGenerating(false),
    });
  };

  const handleCopy = (accountNumber) => {
    navigator.clipboard.writeText(accountNumber);
    setCopiedAcct(accountNumber);
    setTimeout(() => setCopiedAcct(null), 2000);
  };

  return (
    <DashboardLayout  title="Virtual Accounts">
       <WalletBalance
                        user={user}
                        balanceColor={ props.userDashboardPrimaryColor }
        />

      {/* Back Navigation */}
       <PrimaryLink href={route("dashboard")} primaryColor={props.userDashboardPrimaryColor}>
             Back to Dashboard
        </PrimaryLink>

      {/* Full Page Card */}
      <div className="bg-white dark:bg-gray-800 text-gray-700 dark:text-white mt-6 pb-16 rounded-xl shadow overflow-hidden font-inter">
        <div className="p-4 border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-white">
          My Virtual Accounts
        </div>

        <div className="p-4 space-y-4">
          {virtualccts && virtualccts.length > 0 ? (
            virtualccts.map((account, idx) => (
              <div
                key={idx}
                className="p-4 bg-white dark:bg-gray-900 rounded-xl shadow space-y-1 border border-emerald-100 dark:border-emerald-800"
              >
                <div className="flex items-center justify-between">
                  <div className="font-semibold text-emerald-600 dark:text-emerald-400">
                    {account.bank_name}
                  </div>
                  {copiedAcct === account.account_number && (
                    <span className="text-xs text-emerald-500">Copied ✅</span>
                  )}
                </div>
                <div className="text-sm text-gray-700 dark:text-gray-300">
                  Acct Name: {account.account_name}
                </div>
                <div className="flex justify-between items-center mt-1">
                  <div className="text-lg font-mono tracking-wide text-gray-900 dark:text-white">
                    {account.account_number}
                  </div>
                  <button
                    onClick={() => handleCopy(account.account_number)}
                    className="text-xs px-3 py-1 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white dark:bg-emerald-500 dark:hover:bg-emerald-600 transition"
                  >
                    Copy
                  </button>
                </div>
                <div className="text-xs text-gray-500 dark:text-gray-400">
                  Charge: {account.charge?.display ?? "Configured by provider"}
                </div>
              </div>
            ))
          ) : virtualccts.length === 0 ? (
            <div className="mx-auto flex max-w-md flex-col items-center rounded-2xl border border-dashed border-emerald-200 bg-emerald-50/60 px-5 py-8 text-center dark:border-emerald-800 dark:bg-emerald-950/20">
              <div className="mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/20">
                <Landmark size={24} />
              </div>
              <h3 className="font-semibold text-gray-900 dark:text-white">No virtual account yet</h3>
              <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Generate a dedicated account and use it to fund your wallet.
              </p>
              <button
                type="button"
                onClick={generateVirtualAccount}
                disabled={generating}
                className="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
              >
                {generating ? <Loader2 size={17} className="animate-spin" /> : <Landmark size={17} />}
                {generating ? "Generating account..." : "Generate virtual account"}
              </button>
              {generationNotice && (
                <p className={`mt-4 text-sm ${generationNotice.type === "success" ? "text-emerald-700 dark:text-emerald-400" : "text-red-600 dark:text-red-400"}`}>
                  {generationNotice.message}
                </p>
              )}
            </div>
          ) : null}
        </div>
      </div>

      <div className="bg-white dark:bg-gray-800 text-gray-700 dark:text-white mt-6 mb-16 rounded-xl shadow overflow-hidden font-inter">
        <div className="p-4 border-b border-gray-200 dark:border-gray-700 font-semibold">
          Funding History
        </div>
        {fundingHistory.length > 0 ? (
          <div className="divide-y divide-gray-100 dark:divide-gray-700">
            {fundingHistory.map((funding) => (
              <div key={funding.id} className="flex items-center justify-between gap-3 p-4 text-sm">
                <div className="min-w-0">
                  <div className="font-semibold text-gray-800 dark:text-gray-100">{funding.provider}</div>
                  <div className="truncate text-xs text-gray-500 dark:text-gray-400">{funding.reference ?? funding.description}</div>
                  <div className="text-xs text-gray-400">{funding.created_at ? new Date(funding.created_at).toLocaleString() : ""}</div>
                </div>
                <div className="text-right">
                  <div className="font-bold text-emerald-600">+₦{Number(funding.amount).toFixed(2)}</div>
                  <div className="text-xs text-emerald-600">{funding.status}</div>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <p className="p-4 text-center text-sm text-gray-500">No funding history yet.</p>
        )}
      </div>
    </DashboardLayout>
  );
}
