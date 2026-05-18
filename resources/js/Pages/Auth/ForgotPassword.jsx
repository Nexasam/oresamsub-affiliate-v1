import { Head, useForm, usePage } from "@inertiajs/react";
import AuthLayout2 from "@/Layouts/AuthLayout2";

export default function ForgotPassword() {

    const props = usePage().props;

    const sitename = props.sitename || "Oresamsub";
    const flash = props.flash || {};

    const { data, setData, post, processing, errors } = useForm({
        email: "",
    });

    function submit(e) {
        e.preventDefault();

        post(route("password.email"));
    }

    return (
        <AuthLayout2 title="Forgot Password">

            <Head title={`${sitename} | Forgot Password`} />

            <div className="max-w-md mx-auto bg-white p-6 rounded-xl shadow">

                <h1 className="text-2xl font-bold mb-4">
                    Forgot Password
                </h1>

                {flash.success && (
                    <div className="bg-green-100 text-green-700 p-2 rounded mb-4">
                        {flash.success}
                    </div>
                )}

                {flash.error && (
                    <div className="bg-red-100 text-red-700 p-2 rounded mb-4">
                        {flash.error}
                    </div>
                )}

                <form onSubmit={submit} className="space-y-4">

                    <input
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        className="w-full border rounded p-2"
                        placeholder="Email Address"
                    />

                    {errors.email && (
                        <div className="text-red-500 text-sm">
                            {errors.email}
                        </div>
                    )}

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full bg-black text-white py-2 rounded"
                    >
                        {processing
                            ? 'Sending...'
                            : 'Send Password Reset Link'}
                    </button>

                </form>
            </div>

        </AuthLayout2>
    );
}