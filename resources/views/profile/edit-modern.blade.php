<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Profile Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Profile Header Card -->
            <div class="mb-6 p-6 sm:p-8 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 shadow sm:rounded-lg border border-blue-200 dark:border-gray-600">
                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-20 w-20 rounded-full bg-blue-600 text-white text-2xl font-bold">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                    </div>
                    <div class="flex-1 text-center sm:text-left">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ auth()->user()->name }}</h3>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ auth()->user()->email }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                            Member since {{ auth()->user()->created_at->format('F Y') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 dark:border-gray-700" x-data="{ activeTab: 'profile' }">
                <button 
                    @click="activeTab = 'profile'"
                    :class="activeTab === 'profile' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400'"
                    class="px-4 py-3 font-medium hover:text-blue-600 focus:outline-none transition"
                >
                    👤 {{ __('View Profile') }}
                </button>
                <button 
                    @click="activeTab = 'password'"
                    :class="activeTab === 'password' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400'"
                    class="px-4 py-3 font-medium hover:text-blue-600 focus:outline-none transition"
                >
                    🔐 {{ __('Change Password') }}
                </button>
                <button 
                    @click="activeTab = 'pin'"
                    :class="activeTab === 'pin' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400'"
                    class="px-4 py-3 font-medium hover:text-blue-600 focus:outline-none transition"
                >
                    🔑 {{ __('Transaction PIN') }}
                </button>
                <button 
                    @click="activeTab = 'delete'"
                    :class="activeTab === 'delete' ? 'border-b-2 border-red-600 text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400'"
                    class="px-4 py-3 font-medium hover:text-red-600 focus:outline-none transition"
                >
                    ⚠️ {{ __('Delete Account') }}
                </button>
            </div>

            <!-- Profile Information Tab -->
            <div x-show="activeTab === 'profile'" x-transition class="space-y-6">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <div class="max-w-2xl">
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('Profile Information') }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Update your account profile information and email address') }}.</p>
                        </div>

                        <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
                            @csrf
                            @method('patch')

                            <div>
                                <x-input-label for="name" :value="__('Name')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>

                            <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                                <x-input-error class="mt-2" :messages="$errors->get('email')" />

                                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                    <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                        <p class="text-sm text-yellow-800 dark:text-yellow-400">
                                            <strong>⚠️</strong> {{ __('Your email address is unverified.') }}
                                        </p>
                                        <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="mt-2">
                                            @csrf
                                            <button type="submit" class="text-sm text-yellow-700 dark:text-yellow-300 hover:underline font-semibold">
                                                {{ __('Click here to re-send the verification email') }}.
                                            </button>
                                        </form>

                                        @if (session('status') === 'verification-link-sent')
                                            <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                                                ✓ {{ __('A new verification link has been sent to your email address.') }}
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>{{ __('Save Changes') }}</x-primary-button>

                                @if (session('status') === 'profile-updated')
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm text-green-600 dark:text-green-400"
                                    >✓ {{ __('Saved successfully.') }}</p>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Change Password Tab -->
            <div x-show="activeTab === 'password'" x-transition class="space-y-6">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <div class="max-w-2xl">
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">🔐 {{ __('Update Password') }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Ensure your account is using a long, random password to stay secure.') }}</p>
                        </div>

                        <form method="post" action="{{ route('password.update') }}" class="space-y-6">
                            @csrf
                            @method('put')

                            <div>
                                <x-input-label for="update_password_current_password" :value="__('Current Password')" />
                                <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="update_password_password" :value="__('New Password')" />
                                <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
                                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>{{ __('Update Password') }}</x-primary-button>

                                @if (session('status') === 'password-updated')
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm text-green-600 dark:text-green-400"
                                    >✓ {{ __('Password updated successfully.') }}</p>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Change PIN Tab -->
            <div x-show="activeTab === 'pin'" x-transition class="space-y-6">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <div class="max-w-2xl">
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">🔑 {{ __('Transaction PIN') }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Update your 4-digit transaction PIN for secure transactions.') }}</p>
                        </div>

                        <form method="post" action="{{ route('profile.update-pin') }}" class="space-y-6">
                            @csrf

                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg mb-6">
                                <p class="text-sm text-blue-800 dark:text-blue-400">
                                    <strong>💡 Note:</strong> Your PIN is used to authorize transactions. Keep it confidential and don't share it with anyone.
                                </p>
                            </div>

                            <div>
                                <x-input-label for="current_pin" :value="__('Current PIN')" />
                                <x-text-input id="current_pin" name="current_pin" type="password" maxlength="4" placeholder="••••" class="mt-1 block w-full text-center tracking-widest text-2xl" autocomplete="off" />
                                <x-input-error :messages="$errors->get('current_pin')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="new_pin" :value="__('New PIN')" />
                                <x-text-input id="new_pin" name="new_pin" type="password" maxlength="4" placeholder="••••" class="mt-1 block w-full text-center tracking-widest text-2xl" autocomplete="off" required />
                                <x-input-error :messages="$errors->get('new_pin')" class="mt-2" />
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Must be 4 digits') }}</p>
                            </div>

                            <div>
                                <x-input-label for="pin_confirmation" :value="__('Confirm PIN')" />
                                <x-text-input id="pin_confirmation" name="pin_confirmation" type="password" maxlength="4" placeholder="••••" class="mt-1 block w-full text-center tracking-widest text-2xl" autocomplete="off" required />
                                <x-input-error :messages="$errors->get('pin_confirmation')" class="mt-2" />
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>{{ __('Update PIN') }}</x-primary-button>

                                @if (session('status') === 'pin-updated')
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm text-green-600 dark:text-green-400"
                                    >✓ {{ __('PIN updated successfully.') }}</p>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Account Tab -->
            <div x-show="activeTab === 'delete'" x-transition class="space-y-6">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg border-l-4 border-red-500">
                    <div class="max-w-2xl">
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-2">⚠️ {{ __('Delete Account') }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Once your account is deleted, there is no going back. Please be certain.') }}</p>
                        </div>

                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alpine.js for Tab Switching -->
    @if (!app()->environment('local'))
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endif
</x-app-layout>
