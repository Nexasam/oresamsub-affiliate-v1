import { Head, useForm, usePage } from "@inertiajs/react";
import AuthLayout from "@/Layouts/AuthLayout2";

export default function ResetPassword({ token, email }) {

    const { sitename = 'App', flash = {} } = usePage().props;

    const { data, setData, post, processing, errors } = useForm({
        token,
        email: email || "",
        password: "",
        password_confirmation: "",
    });

    function submit(e) {
        e.preventDefault();

        post(route("password.store"));
    }

    return (
        <AuthLayout title="Reset Password">

            <Head title={`${sitename} | Reset Password`} />

            <div className="max-w-md mx-auto bg-white p-6 rounded-xl shadow">

                <h1 className="text-2xl font-bold mb-4">
                    Reset Password
                </h1>

                {/* SUCCESS MESSAGE */}
                {flash.success && (
                    <div className="mb-4 p-3 rounded bg-green-100 text-green-700">
                        {flash.success}
                    </div>
                )}

                {/* ERROR MESSAGE */}
                {flash.error && (
                    <div className="mb-4 p-3 rounded bg-red-100 text-red-700">
                        {flash.error}
                    </div>
                )}

                <form onSubmit={submit} className="space-y-4">

                    <div>
                        <input
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="w-full border rounded p-2"
                            placeholder="Email"
                        />

                        {errors.email && (
                            <p className="text-red-500 text-sm mt-1">
                                {errors.email}
                            </p>
                        )}
                    </div>

                    <div>
                        <input
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="w-full border rounded p-2"
                            placeholder="New Password"
                        />

                        {errors.password && (
                            <p className="text-red-500 text-sm mt-1">
                                {errors.password}
                            </p>
                        )}
                    </div>

                    <div>
                        <input
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            className="w-full border rounded p-2"
                            placeholder="Confirm Password"
                        />
                    </div>

                    <button
                        disabled={processing}
                        className="w-full bg-black text-white py-2 rounded"
                    >
                        {processing
                            ? 'Resetting...'
                            : 'Reset Password'}
                    </button>

                </form>
            </div>

        </AuthLayout>
    );
}