import { usePage, useForm } from "@inertiajs/react";
import AuthLayout2 from "@/Layouts/AuthLayout2";

export default function VerifyEmail() {
  const { props } = usePage();

  const {
    userDashboardPrimaryColor,
    affiliate,
    siteLogo,
  } = props;

  const primaryColor = userDashboardPrimaryColor || "#0d6efd";
  const affiliateInfo = affiliate || {};

  const { post, processing } = useForm({});

  function resend(e) {
    e.preventDefault();
  
    post(route("verification.send"), {
      preserveScroll: true,
  
      onSuccess: (response) => {
        alert("Verification email sent successfully!");
      },
  
      onError: (errors) => {
        alert("Something went wrong.");
      },
    });
  }

  return (
    <AuthLayout2 title="Verify Email">
      <div className="relative min-h-screen w-full flex items-center justify-center">
        
        {/* Card */}
        <div
          className="relative z-10 pt-10 pb-6 max-w-full w-full mx-auto bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-t-4"
          style={{ borderColor: primaryColor }}
        >
          
          {/* Logo */}
          <div className="flex justify-center mb-4">
            <img
              src={
                siteLogo
                  ? `/assets/landing_page_assets/img/site_logo/${siteLogo}`
                  : "/assets/logo_imgs/oresamsublogo.jpeg"
              }
              alt={affiliateInfo?.name || "Logo"}
              className="h-20 w-20 rounded-full shadow-md object-cover"
            />
          </div>

          {/* Title */}
          <h2 className="text-xl font-bold text-center mb-4 text-gray-900 dark:text-white">
            Verify your email for {affiliateInfo?.name}
          </h2>

          {/* Message */}
          <p className="text-sm text-center text-gray-600 dark:text-gray-300 mb-4">
            We’ve sent a verification link to your email address.
            Please check your inbox and click the link to continue.
          </p>

          {/* Success flash */}
          {props.flash?.success && (
            <div className="bg-green-100 border border-green-300 text-green-700 text-sm p-2 rounded mb-4 text-center">
              {props.flash.success}
            </div>
          )}

          {/* Resend */}
          <form onSubmit={resend}>
            <button
              type="submit"
              disabled={processing}
              className="w-full py-2 px-4 text-white rounded-lg transition disabled:opacity-50 shadow hover:shadow-md"
              style={{
                backgroundColor: primaryColor,
                border: `2px solid ${primaryColor}`,
              }}
            >
              {processing ? "Sending..." : "Resend Verification Email"}
            </button>
          </form>
        </div>
      </div>
    </AuthLayout2>
  );
}