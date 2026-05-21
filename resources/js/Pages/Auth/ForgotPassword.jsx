import { Link, useForm, usePage } from "@inertiajs/react";
import AuthLayout2 from "@/Layouts/AuthLayout2";

export default function ForgotPassword() {
  const { props } = usePage();
  const {
    affiliate,
    flash = {},
    siteLogo,
    userDashboardPrimaryColor,
  } = props;

  const primaryColor = userDashboardPrimaryColor || "#0d6efd";
  const affiliateInfo = affiliate || {};

  const { data, setData, post, processing, errors } = useForm({
    email: "",
  });

  function submit(e) {
    e.preventDefault();
    post(route("password.email"));
  }

  return (
    <AuthLayout2 title="Forgot Password">
      <div className="relative min-h-screen w-full flex items-center justify-center overflow-hidden py-10">
        <div className="absolute inset-0 z-0 pointer-events-none">
          <div className="w-full h-full bg-gray-50 dark:bg-gray-900 bg-[radial-gradient(circle,_rgba(0,0,0,0.05)_1px,_transparent_1px)] dark:bg-[radial-gradient(circle,_rgba(255,255,255,0.05)_1px,_transparent_1px)] [background-size:22px_22px]" />
        </div>

        <div
          className="relative z-10 w-full max-w-md mx-auto bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-t-4"
          style={{ borderColor: primaryColor }}
        >
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

          <h2 className="text-2xl mt-3 font-bold text-center mb-2 text-gray-900 dark:text-white">
            Forgot your password?
          </h2>
          <p className="text-sm text-center mb-6 text-gray-500 dark:text-gray-400">
            Enter your email address and we will send you a reset link.
          </p>

          {flash.success && (
            <div className="bg-green-100 border border-green-300 text-green-700 text-sm p-2 rounded mb-4 text-center">
              {flash.success}
            </div>
          )}
          {flash.error && (
            <div className="bg-red-100 border border-red-300 text-red-700 text-sm p-2 rounded mb-4 text-center">
              {flash.error}
            </div>
          )}
          {flash.failure && (
            <div className="bg-red-100 border border-red-300 text-red-700 text-sm p-2 rounded mb-4 text-center">
              {flash.failure}
            </div>
          )}

          <form onSubmit={submit}>
            <div className="mb-4">
              <label htmlFor="email" className="block text-sm mb-1 text-gray-700 dark:text-gray-200">
                Email Address
              </label>
              <input
                type="email"
                id="email"
                value={data.email}
                onChange={(e) => setData("email", e.target.value)}
                required
                className="w-full px-4 py-2 rounded-lg bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="you@example.com"
              />
              {errors.email && (
                <div className="text-red-500 text-sm mt-1">{errors.email}</div>
              )}
            </div>

            <div className="mt-6">
              <button
                type="submit"
                disabled={processing}
                className="w-full py-2 px-4 text-white rounded-lg transition disabled:opacity-50 shadow hover:shadow-md"
                style={{
                  backgroundColor: primaryColor,
                  border: `2px solid ${primaryColor}`,
                  boxShadow: `0 0 6px ${primaryColor}40`,
                }}
              >
                {processing ? "Sending..." : "Send Password Reset Link"}
              </button>
            </div>
          </form>

          <p className="text-xs text-center mt-6 text-gray-500 dark:text-gray-400">
            Remembered your password?{" "}
            <Link
              href={route("login")}
              className="font-semibold transition-colors duration-200 hover:opacity-80"
              style={{ color: primaryColor }}
            >
              Login
            </Link>
          </p>
        </div>
      </div>
    </AuthLayout2>
  );
}
