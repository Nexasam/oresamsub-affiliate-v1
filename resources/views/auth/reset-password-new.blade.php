<!DOCTYPE html>
<html lang="en" dir="ltr" class="h-full">

<head>
    @if (env('APP_NAME') == 'FoxDataHub' )
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
     new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
     j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
     'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
     })(window,document,'script','dataLayer','GTM-NPMMTFT6');</script>
     <script async src="https://www.googletagmanager.com/gtag/js?id=G-NCKP7MH1KN"></script>
     <script>
     window.dataLayer = window.dataLayer || [];
     function gtag(){dataLayer.push(arguments);}
     gtag('js', new Date());
     gtag('config', 'G-NCKP7MH1KN');
     </script>
    @endif

    @if (env('APP_NAME') == 'OresamSub')
      <!-- Meta Pixel Code -->
      <script>
      !function(f,b,e,v,n,t,s)
      {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
      n.callMethod.apply(n,arguments):n.queue.push(arguments)};
      if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
      n.queue=[];t=b.createElement(e);t.async=!0;
      t.src=v;s=b.getElementsByTagName(e)[0];
      s.parentNode.insertBefore(t,s)}(window, document,'script',
      'https://connect.facebook.net/en_US/fbevents.js');
      fbq('init', '4058518677737855');
      fbq('track', 'PageView');
      </script>
      <noscript><img height="1" width="1" style="display:none"
      src="https://www.facebook.com/tr?id=4058518677737855&ev=PageView&noscript=1"
      /></noscript>
    @endif
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title> {{env('APP_NAME')}} - Reset Password </title>
    <meta name="description" content="Create a new password for your account - {{ env('APP_NAME') }}">

    <link rel="stylesheet" href="{{ asset(env('APP_ASSETS_BASE_URL').'css/style.css') }}">
    <link rel="stylesheet" href="{{ asset(env('APP_ASSETS_BASE_URL').'libs/simplebar/simplebar.min.css') }}">
    <link rel="stylesheet" href="{{ asset(env('APP_ASSETS_BASE_URL').'libs/@simonwep/pickr/themes/nano.min.css') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">

    @php
    $admin_site_color = App\Models\AdminColorSetting::where('color_name','admin_site_color')->first();
    $admin_site_color_value = $admin_site_color->color_value ?? (int) '90, 102, 241'; 
    @endphp

    <style>
        .montserrat2 {
          font-family: "Montserrat", sans-serif;
          font-optical-sizing: auto;
          font-weight: 400;
          font-style: normal;
        }

        :root {
            --color-primary: {{ $admin_site_color_value }};
        }
   </style>

</head>

<body class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 min-h-screen flex items-center justify-center py-8 montserrat2">

    @if (env('APP_NAME') == 'FoxDataHub')
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NPMMTFT6" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif

    <div class="w-full max-w-md px-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
            <!-- Header with Logo -->
            <div class="px-6 sm:px-8 pt-8">
                <a href="{{ route('login') }}" class="flex flex-col items-center mb-6">
                    @if (isset($site_logo))
                        <img 
                            src="{{ asset('assets/landing_page_assets/img/site_logo/'.$site_logo) }}" 
                            alt="{{ env('APP_NAME') }}" 
                            class="h-16 w-16 rounded-full shadow-md"
                        >     
                    @else
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ env('APP_NAME') }}</h1>
                    @endif
                </a>

                <!-- Title -->
                <h2 class="text-2xl font-bold text-center text-gray-900 dark:text-gray-100 mb-2">
                    {{ __('messages.Complete Password Reset') }}
                </h2>
                <p class="text-center text-sm text-gray-600 dark:text-gray-400 mb-6">
                    {{ __('messages.Enter your email and create a new password') }}.
                </p>

                <!-- Status Messages -->
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-400 rounded-lg text-sm">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('password.store') }}" class="px-6 sm:px-8 pb-8">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('messages.Email Address') }}
                    </label>
                    <input 
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email', $request->email) }}"
                        required
                        autofocus
                        placeholder="you@example.com"
                        class="w-full px-4 py-3 rounded-lg bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-blue-500 dark:focus:ring-blue-400 transition"
                    >
                    @error('email')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('messages.New Password') }}
                    </label>
                    <input 
                        type="password"
                        name="password"
                        id="password"
                        required
                        placeholder="••••••••"
                        class="w-full px-4 py-3 rounded-lg bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-blue-500 dark:focus:ring-blue-400 transition"
                    >
                    @error('password')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('messages.Confirm Password') }}
                    </label>
                    <input 
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        required
                        placeholder="••••••••"
                        class="w-full px-4 py-3 rounded-lg bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-0 focus:ring-blue-500 dark:focus:ring-blue-400 transition"
                    >
                    @error('password_confirmation')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit"
                    class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800"
                >
                    🔐 {{ __('messages.Reset Password') }}
                </button>
            </form>

            <!-- Footer Links -->
            <div class="px-6 sm:px-8 pb-6 text-center border-t border-gray-200 dark:border-gray-700 pt-4">
                <p class="text-xs text-gray-500 dark:text-gray-500">
                    <a href="{{ route('login') }}" class="text-blue-600 dark:text-blue-400 hover:underline font-semibold">
                        {{ __('messages.Return to login') }}
                    </a>
                </p>
            </div>
        </div>

        <!-- Info Box -->
        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800/30 rounded-lg">
            <p class="text-xs text-blue-800 dark:text-blue-400">
                <strong>🔒 Security:</strong> {{ __('messages.Use a strong, unique password with numbers, special characters and uppercase letters') }}.
            </p>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset(env('APP_ASSETS_BASE_URL').'libs/@popperjs/core/umd/popper.min.js') }}"></script>
    <script src="{{ asset(env('APP_ASSETS_BASE_URL').'js/custom-switcher.js') }}"></script>
    <script src="{{ asset(env('APP_ASSETS_BASE_URL').'libs/preline/preline.js') }}"></script>

</body>

</html>
