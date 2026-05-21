import { Head, useForm, usePage, Link } from "@inertiajs/react";
import AuthLayout from "@/Layouts/AuthLayout";

export default function ForgotPassword() {
    const { props } = usePage();
    const { sitename = "Oresamsub", siteLogo, userDashboardPrimaryColor, flash = {} } = props;

    const { data, setData, post, processing, errors } = useForm({
        email: "",
    });

    function submit(e) {
        e.preventDefault();

        post(route("password.email"));
    }

    return (
        <AuthLayout title="Forgot Password">
            <Head title={`${sitename} | Forgot Password`} />

            <div className="relative min-h-screen w-full flex items-center justify-center overflow-hidden py-10">
                <div className="absolute inset-0 bg-[radial-gradient(circle,_rgba(59,130,246,0.08)_1px,_transparent_1px)] [background-size:22px_22px]" />
                <div className="relative z-10 w-full max-w-md mx-auto bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 p-8">
                    <div className="flex justify-center mb-6">
                        <img
                            src={siteLogo ? `/assets/landing_page_assets/img/site_logo/${siteLogo}` : "/assets/logo_imgs/oresamsublogo.jpeg"}
                            alt={sitename}
                            className="h-20 w-20 rounded-full shadow-md object-cover"
                        />
                    </div>

                    <h1 className="text-3xl font-bold text-center text-gray-900 dark:text-white mb-4">Forgot Password</h1>
                    <p className="text-sm text-center text-gray-500 dark:text-gray-400 mb-6">
                        Enter your email and we&apos;ll send you a secure password reset link.
                    </p>

                    {flash.success && (
                        <div className="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                            {flash.success}
                        </div>
                    )}

                    {flash.error && (
                        <div className="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            {flash.error}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <label htmlFor="email" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                Email Address
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="you@example.com"
                                className="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-900 focus:border-[var(--brand)] focus:ring-[var(--brand)] dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                required
                            />
                            {errors.email && (
                                <p className="mt-2 text-sm text-red-600">{errors.email}</p>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-lg transition hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-70"
                            style={{ backgroundColor: userDashboardPrimaryColor || '#2563eb' }}
                        >
                            {processing ? 'Sending...' : 'Send Password Reset Link'}
                        </button>
                    </form>

                    <div className="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        <Link href={route('login')} className="font-semibold text-[var(--brand)] hover:underline">
                            Back to login
                        </Link>
                    </div>
                </div>
            </div>
        </AuthLayout>
    );
}